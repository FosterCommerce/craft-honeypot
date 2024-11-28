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
	 *
	 * If this is null, then no honeypot field will be set.
	 */
	public ?string $honeypotFieldName = 'enter_a_password';

	/**
	 * If set, the honeypot will include a timetrap field.
	 */
	public ?string $timetrapFieldName = 'honeypot_timestamp';

	/**
	 * The timeout to be used with the timetrap.
	 */
	public null|int|string $timetrapTimeout = 2000;

	/**
	 * If set, a hidden input field will be included and extra JavaScript to set the value after a timeout ({@see jsHoneypotTimeout})
	 */
	public ?string $jsHoneypotFieldName = 'verified_submission';

	/**
	 * The timeout to be used before the hidden input field is set on the client.
	 */
	public null|int|string $jsHoneypotTimeout = 2000;

	/**
	 * Set to a string value to enable a response on non-dev environment.
	 *
	 * Setting {@see spamDetectedRedirect} will override this value.
	 */
	public ?string $spamDetectedResponse = null;

	/**
	 * Set to a path to enable redirecting a client if their submission is flagged as spam.
	 *
	 * Setting this will override {@see spamDetectedResponse}.
	 */
	public ?string $spamDetectedRedirect = null;

	/**
	 * Whether to log every spam submission.
	 *
	 * `false` to disable logs.
	 *
	 * A string value of log-level to enable and generate a log with the desired level.
	 */
	public bool|string $logSpamSubmissions = true;
}
