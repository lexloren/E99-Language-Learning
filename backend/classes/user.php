<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/backend/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/backend/support.php';

class User
{
	public $user_id = null;
	public $handle = null;
	public $email = null;
	public $name_family = null;
	public $name_given = null;
	
	public function __construct($user_id, $handle, $email = null, $name_family = null, $name_given = null)
	{
		$this->user_id = intval($user_id, 10);
		$this->handle = $handle;
		$this->email = $email;
		$this->name_family = $name_family;
		$this->name_given = $name_given;
	}
	
	public static function insert($email, $handle, $password, $name_family = "", $name_given = "")
	{
		global $mysqli;
		
		if (!validate_email($email))
		{
			exit_with_error("Invalid Email", "Email must conform to the standard pattern.");
		}
		
		if (!validate_password($password))
		{
			exit_with_error("Invalid Password", "Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character.");
		}
		
		if (!validate_handle($handle))
		{
			exit_with_error("Invalid Handle", "Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
		}
		
		//  Check whether requested handle conflicts with any existing handle
		$result = $mysqli->query(sprintf("SELECT * FROM users WHERE handle = '%s'",
			$mysqli->escape_string($handle)
		));
		
		if ($existing_user = $result->fetch_assoc())
		{
			exit_with_error("Handle Conflict", "The requested handle is already taken.");
		}
		$result->close();
		
		
		//  Good to go, so insert the new user
		$mysqli->query(sprintf("INSERT INTO users (handle, email, pswd_hash) VALUES ('%s', '%s', PASSWORD('%s'))",
			$mysqli->escape_string($handle),
			$mysqli->escape_string($email),
			$mysqli->escape_string($password)
		));
		
		$result = $mysqli->query(sprintf("SELECT user_id AS id, handle, email, name_given AS nameGiven, name_family AS nameFamily FROM users WHERE handle = '%s'",
			$mysqli->escape_string($handle)
		));
		
		//  Just make sure we actually created the user
		if (!$result)
		{
			exit_with_error("Unknown Error", "The back end unexpectedly failed to create the user.");
		}
		$user_assoc = $result->fetch_assoc();
		$result->close();
		
		return new User(
			$user_assoc["user_id"],
			$user_assoc["handle"],
			$user_assoc["email"]
		);
	}
	
	public function assoc_for_json()
	{
		return array(
			"userId" => $this->user_id,
			"handle" => $this->handle,
			"email" => $this->email,
			"nameGiven" => $this->name_given,
			"nameFamily" => $this->name_family
		);
	}
}

?>