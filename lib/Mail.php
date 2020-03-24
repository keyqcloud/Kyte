<?php

namespace Kyte;

class Mail {
	private static $sendgridAPIKey;

	/*
	 * Sets API key for SendGrid
	 *
	 * @param string $dbName
	 */
	public static function setSendGridAPIKey($key)
	{
		self::$sendgridAPIKey = $key;
	}

	/*
	 * Send email via SendGrid
	 *
	 * @param array $to[email=>name]
	 * @param array $from[email=>name]
	 * @param string $subject
	 * @param string $body
	 */
	public static function email($to, $from, $subject, $body)
	{
		$sg = new \SendGrid(self::$sendgridAPIKey);

		$email = new \SendGrid\Mail\Mail();
		$email->setFrom($from['address'], $from['name']);
		$email->setSubject($subject);
		foreach ($to as $address => $name) {
			$email->addTo($address, $name);
		}
		$email->addContent("text/plain", $body);

		$response = $sg->send($email);
	}
}

?>
