<?php

return [
	/**
	 * Whether the honeypot is enabled
	 */
	'enabled' => true,

	/**
	 * The name to give the hidden input field.
	 *
	 * This should be unique so that it does not conflict with any of your form inputs.
	 */
	'honeypotFieldName' => 'my_password',

	'timeTrapFieldName' => 'honeypot_timetrap',

	'timeTrapTimeout' => 2000,

	/**
	 * Set to a string value to enable a response on non-dev environment.
	 *
	 * Setting {@see spamDetectedRedirect} will override this value.
	 */
	'spamDetectedResponse' => 'Spam submission recorded',

	/**
	 * Set to a path to enable redirecting a client if their submission is flagged as spam.
	 *
	 * Setting this will override {@see spamDetectedResponse}.
	 */
	'spamDetectedRedirect' => '/to/the/naughty/corner',

	/**
	 * Whether to log every spam submission.
	 *
	 * `false` to disable logs.
	 *
	 * A string value of log-level to enable and generate a log with the desired level.
	 */
	'logSpamSubmissions' => 'debug',
];
