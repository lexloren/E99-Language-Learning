<?php

require_once "../connect.php";
require_once "../support.php";

class Session
{
	private static $shared_instance;
	
	//  Opens a session.
	//!!!!  Exits the current script, returning to the front end with either the authentication result or an error.
	public static authenticate($email, $password)
	{
		global $mysqli;
		
		deauthenticate();
		
		if (!validate_password($password))
		{
			exit_with_error("Invalid Password", "Password must consist of between 6 and 31 (inclusive) characters containing at least one non-alphanumeric character.");
		}
		
		if (!validate_handle($handle))
		{
			exit_with_error("Invalid Handle", "Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
		}
		
		//  See whether we can authenticate with the handle and password posted
		$result = $mysqli->query(sprintf("SELECT user_id AS id, handle, email, name_given AS nameGiven, name_family AS nameFamily FROM users WHERE handle = '%s' AND pswd_hash = PASSWORD('%s')",
			$mysqli->escape_string($handle),
			$mysqli->escape_string($password)
		));
		
		if (($user_assoc = $result->fetch_assoc()))
		{
			$result->close();
			
			session_start();
			$session = session_id();
			
			$user_assoc["id"] = intval($user_assoc["id"]);
			
			$_SESSION["handle"] = $user_assoc["handle"];
			
			//  Start a PHP session (using cookies), and update the database accordingly
			$mysqli->query(sprintf("UPDATE users SET session = '%s' WHERE user_id = %d",
				$mysqli->escape_string($session),
				$user_assoc["id"]
			));
			
			exit_with_result($user_assoc);
		}
		else
		{
			exit_with_error("Invalid Credentials", "The handle and password entered match no users in the database.");
		}
	}
	
	//  Reauthenticates the current session and refreshes the timestamp.
	//!!!!  If authentication fails, exits the script with an error.
	//!!!!  Must be called before starting into any script that requires a session.
	public static reauthenticate()
	{
		global $mysqli;
		
		if (!!session_id() && isset ($_SESSION["handle"]))
		{
			$result = $mysqli->query(sprintf("SELECT * FROM users WHERE session = '%s' AND handle = '%s'",
				$mysqli->escape_string(session_id()),
				$mysqli->escape_string($_SESSION["handle"])
			));
			
			if (!$result || !($result->fetch_assoc()))
			{
				session_destroy();
				session_unset();
				exit_with_error("Invalid Session", "The user session is not valid. Please authenticate.");
			}
			
			$mysqli->query(sprintf("UPDATE users SET last_activity = CURRENT_TIMESTAMP WHERE session = '%s' AND handle = '%s'",
				$mysqli->escape_string(session_id()),
				$mysqli->escape_string($_SESSION["handle"])
			));
		}
		else
		{
			exit_with_error("No Session", "The user is not logged in. Please authenticate.");
		}
	}
	
	//  Destroys the current session both in the browser and on the server.
	public static deauthenticate()
	{
		global $mysqli;
		
		if (!!session_id() && strlen(session_id()) > 0)
		{
			$mysqli->query(sprintf("UPDATE users SET session = '' WHERE session = '%s'",
				$mysqli->escape_string(session_id())
			));

			session_destroy();
			session_unset();
		}
	}
}

?>