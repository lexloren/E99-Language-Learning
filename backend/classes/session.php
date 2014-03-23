<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Session
{
	private $user = null;
	private $result_assoc = null;
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
	
	public function get_user()
	{
		return $this->user;
	}
	
	//Used by unit tests
	public function set_user($user)
	{
		$this->user = $user;
	}
	
	public function has_error()
	{
		return !!$this->result_assoc && !!$this->result_assoc["isError"];
	}
	
	// Sets result_assoc
	public function set_error_assoc($title, $description)
	{
		$this->result_assoc = self::error_assoc($title, $description);
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
				
		if (!User::validate_password($password))
		{
			self::set_error_assoc("Invalid Password", "Password must consist of between 6 and 31 (inclusive) characters containing at least one non-alphanumeric character.");
		}
		else if (!User::validate_handle($handle))
		{
			self::set_error_assoc("Invalid Handle", "Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
		}
		else
		{
			$mysqli = Connection::get_shared_instance();
			
			//  See whether we can authenticate with the handle and password posted
			$result = $mysqli->query(sprintf("SELECT user_id, handle, email, name_given, name_family FROM users WHERE handle = '%s' AND pswd_hash = PASSWORD('%s')",
				$mysqli->escape_string($handle),
				$mysqli->escape_string($password)
			));
			
			if (($user_assoc = $result->fetch_assoc()))
			{
				$session = $this->session_start();
				
				$user_assoc["user_id"] = intval($user_assoc["user_id"], 10);
				
				$_SESSION["handle"] = $user_assoc["handle"];
				
				//  Start a PHP session (using cookies), and update the database accordingly
				$mysqli->query(sprintf("UPDATE users SET session = '%s' WHERE user_id = %d",
					$mysqli->escape_string($session),
					$user_assoc["user_id"]
				));
				
				$this->user = User::from_mysql_result_assoc($user_assoc);
				
				Session::get()->set_result_assoc($this->user->assoc_for_json());
			}
			else
			{
				self::set_error_assoc("Invalid Credentials", "The handle and password entered match no users in the database.");
			}
		}
	}
	
	//  Reauthenticates the current session and refreshes the timestamp.
	//!!!!  If authentication fails, exits the script with an error.
	//!!!!  Must be called before starting into any script that requires a session.
	public function reauthenticate()
	{
		if (!!($session_id_old = $this->session_start()) && isset($_SESSION["handle"]))
		{
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT * FROM users WHERE session = '%s' AND handle = '%s'",
				$mysqli->escape_string($session_id_old),
				$mysqli->escape_string($_SESSION["handle"])
			));
			
			if (!$result || !($result_assoc = $result->fetch_assoc()))
			{
				$this->session_end();
				self::set_error_assoc("Invalid Session", "The user session is not valid. Please authenticate.");
				return null;
			}
			
			$session_id_new = $this->session_regenerate_id();
			
			$mysqli->query(sprintf("UPDATE users SET session = '%s' WHERE session = '%s' AND handle = '%s'",
				$mysqli->escape_string($session_id_new),
				$mysqli->escape_string($session_id_old),
				$mysqli->escape_string($_SESSION["handle"])
			));
			
			return ($this->user = User::from_mysql_result_assoc($result_assoc));
		}
		
		self::set_error_assoc("Invalid Session", "The user session is not valid. Please authenticate.");
	}
	
	//  Destroys the current session both in the browser and on the server.
	public function deauthenticate()
	{
		$session_id = $this->session_start();
		
		if (!!$session_id && strlen($session_id) > 0)
		{
			$mysqli = Connection::get_shared_instance();
			$mysqli->query(sprintf("UPDATE users SET session = NULL WHERE session = '%s'",
				$mysqli->escape_string($session_id)
			));

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