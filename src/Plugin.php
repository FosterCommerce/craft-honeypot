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
use yii\web\BadRequestHttpException;

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
		if ($value === false) {
			return false;
		}

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

					/** @var ?string $timetrapValue */
					$timetrapValue = $request->getBodyParam($settings->timetrapFieldName);

					if ($honeypotValue === null && $timetrapValue === null) {
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

					if ($isSpamSubmission) {
						$userIp = $request->getUserIP();
						$userAgent = $request->getUserAgent();
						$action = implode('/', $request->getActionSegments());

						if ($settings->logSpamSubmissions !== false) {
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

						if ($settings->spamDetectedRedirect === null && $settings->spamDetectedTemplate === null) {
							$firstReason = reset($spamReasons);
							throw new BadRequestHttpException(sprintf('Invalid form submission: %s', $firstReason));
						}

						/** @var Response $response */
						$response = Craft::$app->getResponse();
						if ($settings->spamDetectedRedirect !== null) {
							$response->redirect($settings->spamDetectedRedirect);
						} else {
							/** @var string $template */
							$template = $settings->spamDetectedTemplate;
							$response->content = Craft::$app->view->renderTemplate($template, [
								'reasons' => $spamReasons,
								'action' => $action,
								'ip' => $userIp,
								'userAgent' => $userAgent,
							]);
						}

						Craft::$app->end();
					}
				}
			}
		);
	}
}
