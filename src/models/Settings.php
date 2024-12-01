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
	public ?string $honeypotFieldName = null;

	/**
	 * If set, the honeypot will include a hidden timetrap field.
	 *
	 * This should be unique so that it does not conflict with any of your form inputs.
	 */
	public string $timetrapFieldName = 'honeypot_timestamp';

	/**
	 * The timeout to be used with the timetrap.
	 */
	public null|int|string $timetrapTimeout = 2000;

	/**
	 * Whether the timetrap fields value is set using a JS timeout.
	 */
	public bool $setTimetrapWithJs = true;

	/**
	 * The timeout to be used before the hidden input field is set on the client.
	 */
	public null|int|string $jsTimeout = 2000;

	/**
	 * Set to a string value to render a template if their submission is flagged as spam.
	 *
	 * Setting {@see spamDetectedRedirect} will override this value.
	 */
	public ?string $spamDetectedTemplate = null;

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
