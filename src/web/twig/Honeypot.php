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
						$encryptedTimestamp = base64_encode(Craft::$app->getSecurity()->encryptByKey($timestamp));

						if ($settings->setTimetrapWithJs) {
							$jsInputId = sprintf('%s_%s', $idPrefix, $settings->timetrapFieldName);
							$inputs[] = Html::hiddenInput(
								$settings->timetrapFieldName,
								'',
								[
									'id' => $jsInputId,
								],
							);

							$jsTimeout = $settings->jsTimeout ?? Plugin::DEFAULT_JS_TIMEOUT;

							$inputs[] = <<<EOJS
<script type="text/javascript">
	setTimeout(function () {
		document.getElementById('{$jsInputId}').value = '{$encryptedTimestamp}';
	}, {$jsTimeout});
</script>
EOJS;
						} else {
							$inputs[] = Html::hiddenInput(
								$settings->timetrapFieldName,
								$encryptedTimestamp,
								[
									'id' => sprintf('%s_%s', $idPrefix, $settings->timetrapFieldName),
								],
							);
						}
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
