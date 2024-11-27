<?php

namespace fostercommerce\honeypot;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\helpers\App;
use craft\web\Application;
use craft\web\Request;
use fostercommerce\honeypot\models\Settings;
use fostercommerce\honeypot\web\twig\Honeypot;
use yii\base\Event;

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
					$honeypotValue = $request->getBodyParam($settings->honeypotFieldName);
					if ($honeypotValue === null) {
						// A bot simply has to remove the input field altogether to bypass this check.
						return;
					}

					if (! empty($honeypotValue)) {
						if ($settings->logSpamSubmissions !== false) {
							$userIp = $request->getUserIP();
							$userAgent = $request->getUserAgent();
							$action = implode('/', $request->getActionSegments());
							$message = sprintf('Spam submission blocked. IP: %s, Action: %s, User Agent: %s', $userIp, $action, $userAgent);

							if (in_array($settings->logSpamSubmissions, self::LOG_LEVELS, true)) {
								Craft::{$settings->logSpamSubmissions}($message);
							} else {
								Craft::debug($message);
							}
						}

						if ($settings->spamDetectedResponse !== false || App::devMode()) {
							ob_start();

							if ($settings->spamDetectedResponse !== false) {
								echo $settings->spamDetectedResponse;
							} else {
								echo 'Spam submission detected';
							}
						}

						exit(0);
					}
				}
			}
		);
	}
}
