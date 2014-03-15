<?php

require_once "./backend/connection.php";
require_once "./backend/support.php";

class User
{
	private $user_id = null;
	private $handle = null;
	private $email = null;
	private $name_family = null;
	private $name_given = null;
	
	public function __construct($user_id, $handle, $email = null, $name_family = null, $name_given = null)
	{
		$this->user_id = intval($user_id, 10);
		$this->handle = $handle;
		$this->email = $email;
		$this->name_family = $name_family;
		$this->name_given = $name_given;
	}
	
	//  Creates a User object from an associative array fetched from a mysql_result
	public static function from_mysql_result_assoc($result_assoc)
	{
		return new User(
			$result_assoc["user_id"],
			$result_assoc["handle"],
			$result_assoc["email"],
			$result_assoc["name_family"],
			$result_assoc["name_given"]
		);
	}
	
	//  Creates a User object by selecting from the database
	public static function select($user_id)
	{
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query(sprintf("SELECT * FROM users WHERE user_id = %d",
			intval($user_id)
		));
		
		if (!!($result_assoc = $result->fetch_assoc()))
		{
			return User::from_mysql_result_assoc($result_assoc);
		}
		
		return null;
	}
	
	//  Inserts a row into users table and returns corresponding User object
	public static function insert($email, $handle, $password, $name_family = "", $name_given = "")
	{
		$mysqli = Connection::get_shared_instance();
		
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
		
		$result = $mysqli->query(sprintf("SELECT user_id, handle, email, name_given, name_family FROM users WHERE handle = '%s'",
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
		$privacy = ($this != Session::get_user());
		return array(
			"userId" => $this->user_id,
			"handle" => $this->handle,
			"email" => $privacy ? null : $this->email,
			"nameGiven" => $privacy ? null : $this->name_given,
			"nameFamily" => $privacy ? null : $this->name_family
		);
	}
}

?>