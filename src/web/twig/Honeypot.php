<?php

namespace fostercommerce\honeypot\web\twig;

use craft\helpers\Html;
use fostercommerce\honeypot\Plugin;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension
 */
class Honeypot extends AbstractExtension
{
	public function getFunctions()
	{
		return [
			new TwigFunction(
				'honeypot',
				static function () {
					$settings = Plugin::getInstance()->getSettings();
					if (! $settings->enabled) {
						return false;
					}

					return Html::textInput(
						$settings->honeypotFieldName,
						'',
						[
							'id' => $settings->honeypotFieldName,
							'autocomplete' => 'off',
							'tabindex' => '-1',
							'style' => 'display:none; visibility:hidden; position:absolute; left:-9999px;',
						],
					);
				},
				[
					'is_safe' => ['html'],
				]
			),
		];
	}
}
