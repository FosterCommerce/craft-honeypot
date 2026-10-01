<?php

namespace fostercommerce\honeypot;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\web\Application;
use craft\web\Request;
use craft\web\Response;
use fostercommerce\honeypot\integrations\formie\Honeypot as FormieHoneypot;
use fostercommerce\honeypot\models\Settings;
use fostercommerce\honeypot\web\twig\Honeypot;
use verbb\formie\elements\Form;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\Formie;
use verbb\formie\services\Integrations;
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

	/**
	 * @return string[]
	 */
	public function getSpamReasons(): array
	{
		/** @var Request $request */
		$request = Craft::$app->getRequest();
		$settings = $this->getSettings();

		$honeypotValue = null;

		if ($settings->honeypotFieldName !== null) {
			$honeypotValue = $request->getBodyParam($settings->honeypotFieldName);
		}

		/** @var ?string $timetrapValue */
		$timetrapValue = $request->getBodyParam($settings->timetrapFieldName);

		if ($honeypotValue === null && $timetrapValue === null) {
			return [];
		}

		$spamReasons = [];

		if (! empty($honeypotValue)) {
			$spamReasons[] = 'Honeypot value was set';
		}

		if ($timetrapValue !== null) {
			$timetrapValue = $this->decodeTimestamp($timetrapValue);
			if ($timetrapValue === false) {
				$spamReasons[] = 'Tampered timetrap value';
			}

			if ($spamReasons === []) {
				$currentTimestamp = (new \DateTimeImmutable())->format('Uv');

				if ($currentTimestamp - $timetrapValue <= (int) ($settings->timetrapTimeout ?? self::DEFAULT_TIMETRAP_TIMEOUT)) {
					$spamReasons[] = 'Form submission was quicker than timeout value';
				}
			}
		}

		if ($spamReasons !== []) {
			$userIp = $request->getUserIP();
			$userAgent = $request->getUserAgent();
			$action = implode('/', $request->getActionSegments() ?? []);

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
		}

		return $spamReasons;
	}

	protected function createSettingsModel(): ?Model
	{
		return Craft::createObject(Settings::class);
	}

	private function isFormieCaptchaSubmission(Request $request): bool
	{
		if ($request->getActionSegments() !== ['formie', 'submissions', 'submit'] || ! Craft::$app->getPlugins()->isPluginEnabled('formie')) {
			return false;
		}

		$handle = $request->getBodyParam('handle');
		if (! is_string($handle)) {
			return false;
		}

		$formie = Formie::getInstance();
		if (! $formie instanceof Formie) {
			return false;
		}

		$form = $formie->getForms()->getFormByHandle($handle);
		if (! $form instanceof Form) {
			return false;
		}

		// Formie decides which pages to validate and how to handle spam responses.
		return collect($formie->getIntegrations()->getAllEnabledCaptchasForForm($form, null, true))
			->contains(static fn ($captcha): bool => $captcha instanceof FormieHoneypot);
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
		if (Craft::$app->getPlugins()->isPluginEnabled('formie')) {
			Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, static function (RegisterIntegrationsEvent $event): void {
				$event->captchas[] = FormieHoneypot::class;
			});
		}

		Event::on(
			Application::class,
			Application::EVENT_BEFORE_REQUEST,
			function (Event $event): void {
				/** @var Request $request */
				$request = Craft::$app->getRequest();
				if (($request->getIsPost() || $request->getIsPut()) && ! $this->isFormieCaptchaSubmission($request)) {
					$settings = $this->getSettings();

					$spamReasons = $this->getSpamReasons();

					if ($spamReasons !== []) {
						$userIp = $request->getUserIP();
						$userAgent = $request->getUserAgent();
						$action = implode('/', $request->getActionSegments() ?? []);

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
