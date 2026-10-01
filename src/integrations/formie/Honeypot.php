<?php

namespace fostercommerce\honeypot\integrations\formie;

use Craft;
use craft\helpers\Html;
use fostercommerce\honeypot\Plugin;
use fostercommerce\honeypot\web\twig\Honeypot as HoneypotRenderer;
use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;

class Honeypot extends Captcha
{
	public ?string $handle = 'craftHoneypot';

	public function getName(): string
	{
		return Craft::t('honeypot', 'Craft Honeypot');
	}

	public function getIconUrl(): string
	{
		return (string) Craft::$app->getAssetManager()->getPublishedUrl(Plugin::getInstance()->getBasePath() . '/icon.svg', true);
	}

	public function getDescription(): string
	{
		return Craft::t('honeypot', 'Check submissions using the Craft Honeypot plugin.');
	}

	public function getSettingsHtml(): string
	{
		return Html::tag('p', Craft::t('honeypot', 'Settings are managed by config/honeypot.php.'));
	}

	/**
	 * @param FieldLayoutPage|null $page
	 */
	public function getFrontEndHtml(Form $form, $page = null): string
	{
		return (new HoneypotRenderer())->render() ?: '';
	}

	public function validateSubmission(Submission $submission): bool
	{
		$plugin = Plugin::getInstance();
		if (! $plugin->getSettings()->enabled) {
			return true;
		}

		$spamReasons = $plugin->getSpamReasons();
		$this->spamReason = implode('; ', $spamReasons);

		return $spamReasons === [];
	}
}
