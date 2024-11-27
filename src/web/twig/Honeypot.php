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

					$inputs = [];

					if ($settings->timetrapFieldName !== null) {
						$timestamp = (new \DateTimeImmutable())->format('Uv');
						$inputs[] = Html::hiddenInput(
							$settings->timetrapFieldName,
							base64_encode(Craft::$app->getSecurity()->encryptByKey((string) $timestamp)),
							[
								'id' => $settings->timetrapFieldName,
							]
						);
					}

					if ($settings->honeypotFieldName !== null) {
						$inputs[] = Html::textInput(
							$settings->honeypotFieldName,
							'',
							[
								'id' => $settings->honeypotFieldName,
								'autocomplete' => 'off',
								'tabindex' => '-1',
								'style' => 'display:none; visibility:hidden; position:absolute; left:-9999px;',
							],
						);
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
