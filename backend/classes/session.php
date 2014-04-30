<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Session
{
	private $user = null;
	private $result_assoc = null;
	private $allow_email = true;
	private static $instance = null;

	public static function set($instance)
	{
		self::$instance = $instance;
	}
	
	public static function get()
	{
		if (!isset(self::$instance))
			self::$instance = new Session();
		return self::$instance;
	}
	
	public function get_allow_email()
	{
		return $this->allow_email;
	}
	
	public function set_allow_email($allow)
	{
		$this->allow_email = $allow;
	}
	
	public function get_user()
	{
		return $this->user;
	}
	
	//Used by unit tests
	public function set_user($user)
	{
		return ($this->user = $user);
	}
	
	public function has_error()
	{
		return !$this->result_assoc || !!$this->result_assoc["isError"];
	}
	
	public function set_mixed_assoc($error_title, $errors, $result, $result_information = null)
	{
		$this->result_assoc = self::result_assoc($result, $result_information);
		
		foreach (self::error_assoc($error_title, $errors) as $key => $value)
		{
			if (!!$value && !$this->result_assoc[$key])
			{
				$this->result_assoc[$key] = $value;
			}
		}
	}
	
	// Sets result_assoc
	public function set_error_assoc($title, $description)
	{
		$this->result_assoc = self::error_assoc($title, $description);
	}
	
	public function unset_result_assoc()
	{
		return ($this->result_assoc = null);
	}

	//  Sets result_assoc
	public function set_result_assoc($result, $result_information = null)
	{
		$this->result_assoc = self::result_assoc($result, $result_information);
	}
	
	public function get_result_assoc()
	{
		return $this->result_assoc;
	}

	//This will be called from router.php
	public function echo_json()
	{
		require_once "./backend/headers.php";
		echo json_encode($this->result_assoc);
	}
	
	//  Opens a session.
	//!!!!  Exits the current script, returning to the front end in case of error.
	public function authenticate($handle, $password)
	{
		self::deauthenticate();
		
		$handle = strtolower($handle);
		
		//  See whether we can authenticate with the handle and password posted
		$result = Connection::query(sprintf("SELECT * FROM users WHERE (handle = '%s' OR email = '%s') AND pswd_hash = PASSWORD('%s')",
			Connection::escape($handle),
			Connection::escape($handle),
			Connection::escape($password)
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			self::set_error_assoc("Authentication Failure", "The database failed to authenticate the session user.");
		}
		
		if (($user_assoc = $result->fetch_assoc()) && $result->num_rows === 1)
		{
			$this->user = User::from_mysql_result_assoc($user_assoc);
			
			$session = $this->session_start();
			
			$_SESSION["handle"] = $this->user->get_handle();
			
			//  Start a PHP session (using cookies), and update the database accordingly
			Connection::query(sprintf("UPDATE users SET session = '%s' WHERE user_id = %d",
				Connection::escape($session),
				$this->user->get_user_id()
			));
			
			if (!!($error = Connection::query_error_clear()))
			{
				self::set_error_assoc("Authentication Failure", "The database failed to authenticate the session user.");
			}
			
			Session::get()->set_result_assoc($this->user->json_assoc());
		}
		else
		{
			self::set_error_assoc("Credentials Invalidity", "The handle and password entered match no users in the database.");
		}
	}
	
	//  Reauthenticates the current session and refreshes the timestamp.
	//!!!!  If authentication fails, exits the script with an error.
	//!!!!  Must be called before starting into any script that requires a session.
	public function reauthenticate()
	{
		if (!!($session_id_old = $this->session_start()) && isset($_SESSION["handle"]))
		{
			$session = $this;
			return Connection::transact(
				function () use ($session)
				{
					$result = Connection::query(sprintf("SELECT * FROM users WHERE session = '%s' AND handle = '%s' AND TIMESTAMPDIFF(MINUTE, timestamp, CURRENT_TIMESTAMP) < 60",
						Connection::escape($session_id_old),
						Connection::escape($_SESSION["handle"])
					));
					
					if (!!Connection::query_error_clear() || !$result || !($result_assoc = $result->fetch_assoc()))
					{
						$session->session_end();
						return $session->unset_result_assoc();
					}
					
					$session_id_new = $session_id_old; //$this->session_regenerate_id();
					
					Connection::query(sprintf("UPDATE users SET session = '%s' WHERE session = '%s' AND handle = '%s'",
						Connection::escape($session_id_new),
						Connection::escape($session_id_old),
						Connection::escape($_SESSION["handle"])
					));
					
					if (!!Connection::query_error_clear())
					{
						$session->session_end();
						return $session->unset_result_assoc();
					}
					
					return $session->set_user(User::from_mysql_result_assoc($result_assoc));
				}
			);
		}
		
		return ($this->result_assoc = null);
		//self::set_error_assoc("Invalid Session", "The user session is not valid. Please authenticate.");
	}
	
	//  Destroys the current session both in the browser and on the server.
	public function deauthenticate()
	{
		$session_id = $this->session_start();
		
		if (!!$session_id && strlen($session_id) > 0)
		{
			Connection::query(sprintf("UPDATE users SET session = NULL WHERE session = '%s'",
				Connection::escape($session_id)
			));
			
			if (!!Connection::query_error_clear())
			{
				exit("Application failed to end session.");
			}

			$this->session_end();
		}
	}
	
	//  Returns new PHP associative array for returning to front end.
	private function new_return_template()
	{
		return array (
			"isError" => false,
			"errorTitle" => NULL,
			"errorDescription" => NULL,
			"result" => NULL,
			"resultInformation" => NULL
		);
	}
	
	private function new_database_result_template()
	{
		return array (
			"didInsert" => false,
			"didDelete" => false,
			"didUpdate" => false
		);
	}

	//  Formats an error as a PHP associative array.
	private function error_assoc($title, $description)
	{
		$return = self::new_return_template();
		
		$return["isError"] = true;
		$return["errorTitle"] = $title;
		$return["errorDescription"] = $description;
		
		return $return;
	}

	//  Formats a result as a PHP associative array.
	private function result_assoc($result, $result_information = NULL)
	{
		$return = self::new_return_template();
		
		$return["result"] = $result;
		$return["resultInformation"] = $result_information;
		
		return $return;
	}
	
	private function database_result_assoc($database_result_assoc)
	{
		foreach (array_keys(($return = self::new_database_result_template())) as $key)
		{
			if (isset($database_result_assoc[$key]) && $database_result_assoc[$key] !== null)
			{
				$return[$key] = !!$database_result_assoc[$key];
			}
		}
		
		return $return;
	}
	
	public function session_regenerate_id()
	{
		session_regenerate_id(true);
		$session_id_new = session_id();
		return $session_id_new;
	}
	
	//Arunabha : This method is stubbed in unit tests. Do not directly call php session_start()
	public function session_start()
	{
		session_start();
		$session = session_id();
		return $session;
	}
	
	//Arunabha : This method is stubbed in unit tests. Do not directly call php session_end()
	public function session_end()
	{
		session_destroy();
		session_unset();
	}
}

?>