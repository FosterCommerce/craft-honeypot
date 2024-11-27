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
	 * @var string[]
	 */
	private const LOG_LEVELS = ['debug', 'info', 'error', 'warning'];

	/**
	 * @var int
	 */
	private const DEFAULT_TIMETRAP_TIMEOUT = 2000;

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

					if ($settings->honeypotFieldName !== null) {
						$honeypotValue = $request->getBodyParam($settings->honeypotFieldName);
					}

					if ($settings->timetrapFieldName !== null) {
						/** @var ?string $timetrapValue */
						$timetrapValue = $request->getBodyParam($settings->timetrapFieldName);
					}

					if ($honeypotValue === null && $timetrapValue === null) {
						// A bot simply has to remove the input fields altogether to bypass this check.
						return;
					}

					$isSpamSubmission = false;
					$spamReasons = [];

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

					if (! empty($honeypotValue)) {
						$isSpamSubmission = true;
						$spamReasons[] = 'Honeypot value was set';
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
