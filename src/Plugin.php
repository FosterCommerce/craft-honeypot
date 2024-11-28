<?php

namespace fostercommerce\honeypot;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\web\Application;
use craft\web\Request;
use craft\web\Response;
use fostercommerce\honeypot\models\Settings;
use fostercommerce\honeypot\web\twig\Honeypot;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
	/**
	 * @var int
	 */
	public const DEFAULT_TIMETRAP_TIMEOUT = 2000;

	/**
	 * @var int
	 */
	public const DEFAULT_JS_TIMEOUT = 2000;

	/**
	 * @var string
	 */
	public const DEFAULT_JS_TEXT = 'verified';

	/**
	 * @var string[]
	 */
	private const LOG_LEVELS = ['debug', 'info', 'error', 'warning'];

	public bool $hasCpSettings = true;

	public function init(): void
	{
		parent::init();

		$this->attachEventHandlers();

		Craft::$app->view->registerTwigExtension(new Honeypot());
	}

	protected function createSettingsModel(): ?Model
	{
		return Craft::createObject(Settings::class);
	}

	protected function settingsHtml(): ?string
	{
		return Craft::$app->view->renderTemplate('honeypot/_settings.twig', [
			'plugin' => $this,
			'settings' => $this->getSettings(),
		]);
	}

	/**
	 * @throws Exception
	 * @throws InvalidConfigException
	 */
	private function decodeTimestamp(string $value): false|int
	{
		$value = base64_decode($value, true);
		if ($value === false) {
			return false;
		}

		$value = Craft::$app->getSecurity()->decryptByKey($value);
		return (int) $value;
	}

	private function attachEventHandlers(): void
	{
		Event::on(
			Application::class,
			Application::EVENT_BEFORE_REQUEST,
			function (Event $event): void {
				/** @var Request $request */
				$request = Craft::$app->getRequest();
				if ($request->getIsPost() || $request->getIsPut()) {
					$settings = $this->getSettings();

					$honeypotValue = null;
					$timetrapValue = null;
					$jsHoneypotValue = null;

					if ($settings->honeypotFieldName !== null) {
						$honeypotValue = $request->getBodyParam($settings->honeypotFieldName);
					}

					if ($settings->timetrapFieldName !== null) {
						/** @var ?string $timetrapValue */
						$timetrapValue = $request->getBodyParam($settings->timetrapFieldName);
					}

					if ($settings->jsHoneypotFieldName !== null) {
						/** @var ?string $jsHoneypotValue */
						$jsHoneypotValue = $request->getBodyParam($settings->jsHoneypotFieldName);
					}

					if ($honeypotValue === null && $timetrapValue === null && $jsHoneypotValue === null) {
						// A bot simply has to remove the input fields altogether to bypass this check.
						return;
					}

					$isSpamSubmission = false;
					$spamReasons = [];

					// Honeypot test
					if (! empty($honeypotValue)) {
						$isSpamSubmission = true;
						$spamReasons[] = 'Honeypot value was set';
					}

					// Timetrap test
					if ($timetrapValue !== null) {
						$timetrapValue = $this->decodeTimestamp($timetrapValue);
						if ($timetrapValue === false) {
							// Timetrap value was tampered with. Mark as spam.
							$isSpamSubmission = true;
							$spamReasons[] = 'Tampered timetrap value';
						}

						if (! $isSpamSubmission) {
							$currentTimestamp = (new \DateTimeImmutable())->format('Uv');

							if ($currentTimestamp - $timetrapValue <= (int) ($settings->timetrapTimeout ?? self::DEFAULT_TIMETRAP_TIMEOUT)) {
								$isSpamSubmission = true;
								$spamReasons[] = 'Form submission was quicker than timeout value';
							}
						}
					}

					// Js honeypot test
					if ($jsHoneypotValue !== null && $jsHoneypotValue !== self::DEFAULT_JS_TEXT) {
						$isSpamSubmission = true;
						$spamReasons[] = 'JavaScript honeypot value was not set';
					}

					if ($isSpamSubmission) {
						if ($settings->logSpamSubmissions !== false) {
							$userIp = $request->getUserIP();
							$userAgent = $request->getUserAgent();
							$action = implode('/', $request->getActionSegments());
							$message = sprintf(
								'Spam submission blocked. Reasons: %s, IP: %s, Action: %s, User Agent: %s',
								implode('; ', $spamReasons),
								$userIp,
								$action,
								$userAgent
							);

							if (in_array($settings->logSpamSubmissions, self::LOG_LEVELS, true)) {
								Craft::{$settings->logSpamSubmissions}($message);
							} else {
								Craft::debug($message);
							}
						}

						/** @var Response $response */
						$response = Craft::$app->getResponse();
						if ($settings->spamDetectedRedirect !== null) {
							$response->redirect($settings->spamDetectedRedirect);
						} elseif ($settings->spamDetectedResponse !== null) {
							$response->content = $settings->spamDetectedResponse;
						}

						Craft::$app->end();
					}
				}
			}
		);
	}
}
