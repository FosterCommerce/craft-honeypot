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

	/**
	 * `false` to disable responses on non-dev environments.
	 *
	 * Set to a string value to enable a response on non-dev environment.s
	 */
	'spamDetectedResponse' => 'Spam submission recorded',

	/**
	 * Whether to log every spam submission.
	 *
	 * `false` to disable logs.
	 *
	 * A string value of log-level to enable and generate a log with the desired level.
	 */
	'logSpamSubmissions' => 'debug',
];
