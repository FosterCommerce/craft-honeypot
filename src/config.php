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
	'honeypotFieldName' => 'set_my_password',

	/**
	 * If set, the honeypot will include a timetrap field.
	 *
	 * This should be unique so that it does not conflict with any of your form inputs.
	 */
	'timetrapFieldName' => 'honeypot_timetrap',

	/**
	 * The timeout to be used with the timetrap.
	 */
	'timetrapTimeout' => 2000,


	/**
	 * Whether the timetrap fields value is set using a JS timeout.
	 */
	'setTimetrapWithJs' => true,

	/**
	 * The timeout to be used before the hidden input field is set on the client.
	 */
	'jsTimeout' => 2000,

	/**
	 * Set to a string value to enable a response on non-dev environment.
	 *
	 * Setting {@see spamDetectedRedirect} will override this value.
	 */
	'spamDetectedTemplate' => 'path/to/spam-template.twig',

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
