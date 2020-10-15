<?php

namespace Kyte;

/*
 * Class Session
 *
 * @package RizeCreate
 *
 */

class SessionManager
{
	private $session;
	private $user;
	private $username_field;
	private $password_field;

	public function __construct($session_model, $account_model, $username_field = 'email', $password_field = 'password', $multilogon = false, $timeout = 3600) {
		$this->session = new \Kyte\ModelObject($session_model);
		$this->user = new \Kyte\ModelObject($account_model);
		$this->username_field = $username_field;
		$this->password_field = $password_field;
		$this->timeout = $timeout;
		$this->multilogon = $multilogon;
	}

	protected function generateTxToken($time, $exp_time, $string) {
		return hash_hmac('sha256', $string.'-'.$time, $exp_time);
	}

	protected function generateSessionToken($string) {
		$bytes = random_bytes(5);
		return hash_hmac('sha256', $string, bin2hex($bytes));
	}

	public function create($username, $password, $conditions = null)
	{
		if (isset($username, $password)) {

			// verify user
			if (!$this->user->retrieve($this->username_field, $username, $conditions)) {
				throw new \Kyte\SessionException("Invalid username or password.");
			}

			if (!password_verify($password, $this->user->getParam($this->password_field))) {
				throw new \Kyte\SessionException("Invalid username or password.");
			}

			// delete existing session
			if (!$this->multilogon && $this->session->retrieve('uid', $this->user->getParam('id'))) {
				$this->session->delete();
			}

			$time = time();
			$exp_time = $time+$this->timeout;
			// create new session
			$res = $this->session->create([
				'uid' => $this->user->getParam('id'),
				'exp_date' => $exp_time,
				'sessionToken' => $this->generateSessionToken($this->user->getParam($this->username_field)),
				'txToken' => $this->generateTxToken($time, $exp_time, $this->user->getParam($this->username_field)),
			]);
			if (!$res) {
				throw new \Kyte\SessionException("Unable to create session.");
			}

			// return params for new session after successful creation
			return $this->session->getAllParams();
		} else throw new \Kyte\SessionException("Session name was not specified.");
		
	}

	public function validate($sessionToken)
	{
		// get current time
		$time = time();

		// check if session token exists and retrieve session object
		if (!$this->session->retrieve('sessionToken', $sessionToken)) {
			throw new \Kyte\SessionException("No valid session.");
		}
		
		// check if use is still active
		if (!$this->user->retrieve('id', $this->session->getParam('uid'))) {
			throw new \Kyte\SessionException("Invalid session.");
		}

		// check for expriation
		if ($time > $this->session->getParam('exp_date')) {
			throw new \Kyte\SessionException("Session expired.");
		}
		
		// create new expiration
		$exp_time = $time+$this->timeout;
		
		// update session with new expiration
		$this->session->save([
			'exp_date' => $exp_time,
		]);

		// return session variable
		return $this->session->getAllParams();
	}

	public function destroy() {
		if (!$this->session) {
			throw new \Kyte\SessionException("No valid session.");
		}
		$this->session->delete();
		return true;
	}
}

?>
