<?php

require_once "./backend/connection.php";
require_once "./backend/support.php";
require_once "./backend/classes.php";

class Session
{
	private static $user = null;
	private static $result_assoc = null;
	public static function get_user()
	{
		return self::$user;
	}
	
	//Used by unit tests
	public static function set_user($user)
	{
		self::$user = $user;
	}
	
	public static function has_error()
	{
		return isset(self::$result_assoc) && self::$result_assoc['isError'];
	}
	
	// Sets result_assoc
	public static function set_error_assoc($title, $description)
	{
		self::$result_assoc = error_assoc($title, $description);
	}

	//  Sets result_assoc
	public static function set_result_assoc($result, $result_information = NULL)
	{
		self::$result_assoc = result_assoc($result, $result_information);
	}
	
	//This will be called from router.php
	public static function echo_result_assoc()
	{
		global $headers;
		if (!isset ($headers)) require_once "./backend/headers.php";
		
		echo json_encode(self::$result_assoc);
	}
	
	//  Opens a session.
	//!!!!  Exits the current script, returning to the front end in case of error.
	public static function authenticate($handle, $password)
	{
		self::deauthenticate();
		
		$mysqli = Connection::get_shared_instance();
		
		if (!validate_password($password))
		{
			self::set_error_assoc("Invalid Password", "Password must consist of between 6 and 31 (inclusive) characters containing at least one non-alphanumeric character.");
		}
		else if (!validate_handle($handle))
		{
			self::set_error_assoc("Invalid Handle", "Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
		}
		else
		{
			//  See whether we can authenticate with the handle and password posted
			$result = $mysqli->query(sprintf("SELECT user_id, handle, email, name_given, name_family FROM users WHERE handle = '%s' AND pswd_hash = PASSWORD('%s')",
				$mysqli->escape_string($handle),
				$mysqli->escape_string($password)
			));
			
			if (($user_assoc = $result->fetch_assoc()))
			{
				$result->close();
				
				session_start();
				$session = session_id();
				
				$user_assoc["user_id"] = intval($user_assoc["user_id"]);
				
				$_SESSION["handle"] = $user_assoc["handle"];
				
				//  Start a PHP session (using cookies), and update the database accordingly
				$mysqli->query(sprintf("UPDATE users SET session = '%s' WHERE user_id = %d",
					$mysqli->escape_string($session),
					$user_assoc["user_id"]
				));
				
				self::$user = User::from_mysql_result_assoc($user_assoc);
				
				Session::set_result_assoc(self::$user->assoc_for_json());
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
	public static function reauthenticate()
	{
		session_start();
		
		if (!!session_id() && isset ($_SESSION["handle"]))
		{
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT * FROM users WHERE session = '%s' AND handle = '%s'",
				$mysqli->escape_string(session_id()),
				$mysqli->escape_string($_SESSION["handle"])
			));
			
			if (!$result || !($result_assoc = $result->fetch_assoc()))
			{
				session_destroy();
				session_unset();
				self::set_error_assoc("Invalid Session", "The user session is not valid. Please authenticate.");
				return null;
			}
			
			$mysqli->query(sprintf("UPDATE users SET last_activity = CURRENT_TIMESTAMP WHERE session = '%s' AND handle = '%s'",
				$mysqli->escape_string(session_id()),
				$mysqli->escape_string($_SESSION["handle"])
			));
			
			self::$user = User::from_mysql_result_assoc($result_assoc);
			
			return self::$user;
		}
		
		self::set_error_assoc("Invalid Session", "The user session is not valid. Please authenticate.");
	}
	
	//  Destroys the current session both in the browser and on the server.
	public static function deauthenticate()
	{
		$mysqli = Connection::get_shared_instance();
		
		if (!!session_id() && strlen(session_id()) > 0)
		{
			$mysqli->query(sprintf("UPDATE users SET session = '' WHERE session = '%s'",
				$mysqli->escape_string(session_id())
			));

			session_destroy();
			session_unset();
		}
	}
	
	//  Returns new PHP associative array for returning to front end.
	private static function new_return_template()
	{
		return array (
			"isError" => false,
			"errorTitle" => NULL,
			"errorDescription" => NULL,
			"result" => NULL,
			"resultInformation" => NULL
		);
	}
	
	private static function new_database_result_template()
	{
		return array (
			"didInsert" => false,
			"didDelete" => false,
			"didUpdate" => false
		);
	}

	//  Formats an error as a PHP associative array.
	private static function error_assoc($title, $description)
	{
		$return = self::new_return_template();
		
		$return["isError"] = true;
		$return["errorTitle"] = $title;
		$return["errorDescription"] = $description;
		
		return $return;
	}

	//  Formats a result as a PHP associative array.
	private static function result_assoc($result, $result_information = NULL)
	{
		$return = self::new_return_template();
		
		$return["result"] = $result;
		$return["resultInformation"] = $result_information;
		
		return $return;
	}
	
	public static function database_result_assoc($database_result_assoc)
	{
		$return = self::new_database_result_template();
		foreach (array_keys($return) as $key)
		{
			if (isset ($database_result_assoc[$key]) && $database_result_assoc[$key] !== null)
			{
				$return[$key] = !!$database_result_assoc[$key];
			}
		}
		
		return $return;
	}

	//  Outputs a JSON representation of an error.
	private static function echo_error($title, $description)
	{
		echo json_encode(error_assoc($title, $description));
	}

	//  Outputs a JSON representation of a result.
	private static function echo_result($result, $result_information = NULL)
	{
		echo json_encode(result_assoc($result, $result_information));
	}
	
	//  Outputs a JSON representation of an error.
	public static function set_error($title, $description)
	{
		echo json_encode(error_assoc($title, $description));
	}

	//  Outputs a JSON representation of a result.
	public static function set_result($result, $result_information = NULL)
	{
		echo json_encode(result_assoc($result, $result_information));
	}

	//  Exits the executing script, outputting an error formatted in JSON.
	public static function exit_with_error($title, $description)
	{
		global $headers;
		if (!isset ($headers)) require_once "./backend/headers.php";
		
		echo_error($title, $description);
		exit;
	}

	//  Exits the executing script, outputting a result formatted in JSON.
	public static function exit_with_result($result, $result_information = NULL)
	{
		global $headers;
		if (!isset ($headers)) require_once "./backend/headers.php";
		
		echo_result($result, $result_information);
		exit;
	}
}

?>