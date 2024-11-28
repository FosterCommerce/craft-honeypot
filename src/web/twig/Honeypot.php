<?php

namespace fostercommerce\honeypot\web\twig;

use Craft;
use craft\helpers\Html;
use fostercommerce\honeypot\Plugin;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Honeypot extends AbstractExtension
{
	public function getFunctions()
	{
		return [
			new TwigFunction(
				'honeypot',
				static function (): false|string {
					$settings = Plugin::getInstance()->getSettings();
					if (! $settings->enabled) {
						return false;
					}

					$idPrefix = Craft::$app->getSecurity()->generateRandomString(12);

					$inputs = [];

					if ($settings->honeypotFieldName !== null) {
						$inputs[] = Html::textInput(
							$settings->honeypotFieldName,
							'',
							[
								'id' => sprintf('%s_%s', $idPrefix, $settings->honeypotFieldName),
								'autocomplete' => 'off',
								'tabindex' => '-1',
								'style' => 'display:none; visibility:hidden; position:absolute; left:-9999px;',
							],
						);
					}

					if ($settings->timetrapFieldName !== null) {
						$timestamp = (new \DateTimeImmutable())->format('Uv');
						$inputs[] = Html::hiddenInput(
							$settings->timetrapFieldName,
							base64_encode(Craft::$app->getSecurity()->encryptByKey($timestamp)),
							[
								'id' => sprintf('%s_%s', $idPrefix, $settings->timetrapFieldName),
							],
						);
					}

					if ($settings->jsHoneypotFieldName !== null) {
						$jsInputId = sprintf('%s_%s', $idPrefix, $settings->jsHoneypotFieldName);
						$inputs[] = Html::hiddenInput(
							$settings->jsHoneypotFieldName,
							'',
							[
								'id' => $jsInputId,
							],
						);

						$jsTimeout = $settings->jsHoneypotTimeout ?? Plugin::DEFAULT_JS_TIMEOUT;
						$jsVerifiedText = Plugin::DEFAULT_JS_TEXT; // TODO add config for this

						$inputs[] = <<<EOJS
<script type="text/javascript">
	setTimeout(function () {
		document.getElementById('{$jsInputId}').value = '{$jsVerifiedText}';
	}, {$jsTimeout});
</script>
EOJS;
					}

					return implode('', $inputs);
				},
				[
					'is_safe' => ['html'],
				],
			),
		];
	}
}
