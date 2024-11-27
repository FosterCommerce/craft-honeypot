<?php

namespace fostercommerce\honeypot\models;

use craft\base\Model;

class Settings extends Model
{
	/**
	 * Whether the honeypot is enabled
	 */
	public bool $enabled = true;

	/**
	 * The name to give the hidden input field
	 *
	 * This should be unique so that it does not conflict with any of your form inputs.
	 */
	public string $honeypotFieldName = 'enter_a_password';

	/**
	 * `false` to disable responses on non-dev environments.
	 *
	 * Set to a string value to enable a response on non-dev environment.s
	 */
	public bool|string $spamDetectedResponse = false;

	/**
	 * Whether to log every spam submission.
	 *
	 * `false` to disable logs.
	 *
	 * A string value of log-level to enable and generate a log with the desired level.
	 */
	public bool|string $logSpamSubmissions = true;
}
