<?php

require_once "./backend/connection.php";
require_once "./backend/support.php";
require_once "./backend/classes.php";

class User extends DatabaseRow
{
	/***    CLASS/STATIC    ***/
	
	//  Creates a User object by selecting from the database
	public static function select($user_id)
	{
		$user_id = intval($user_id, 10);
		
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query("SELECT * FROM users WHERE user_id = $user_id");
		
		if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
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
			Session::exit_with_error("Invalid Email", "Email must conform to the standard pattern.");
		}
		
		if (!validate_password($password))
		{
			Session::exit_with_error("Invalid Password", "Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character.");
		}
		
		if (!validate_handle($handle))
		{
			Session::exit_with_error("Invalid Handle", "Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
		}
		
		//  Check whether requested handle conflicts with any existing handle
		$result = $mysqli->query(sprintf("SELECT * FROM users WHERE handle = '%s'",
			$mysqli->escape_string($handle)
		));
		
		if ($existing_user = $result->fetch_assoc())
		{
			Session::exit_with_error("Handle Conflict", "The requested handle is already taken.");
		}
		
		//  Good to go, so insert the new user
		$mysqli->query(sprintf("INSERT INTO users (handle, email, pswd_hash, name_given, name_family) VALUES ('%s', '%s', PASSWORD('%s'), '%s', '%s')",
			$mysqli->escape_string($handle),
			$mysqli->escape_string($email),
			$mysqli->escape_string($password),
			$mysqli->escape_string($name_given),
			$mysqli->escape_string($name_family)
		));
		
		$result = $mysqli->query(sprintf("SELECT user_id, handle, email, name_given, name_family FROM users WHERE handle = '%s'",
			$mysqli->escape_string($handle)
		));
		
		//  Just make sure we actually created the user
		if (!$result)
		{
			Session::exit_with_error("Unknown Error", "The back end unexpectedly failed to create the user.");
		}
		$user_assoc = $result->fetch_assoc();
		$result->close();
		
		return User::from_mysql_result_assoc($user_assoc);
	}
	
	/***    INSTANCE    ***/
	
	private $user_id = null;
	public function get_user_id()
	{
		return $this->user_id;
	}
	
	private $handle = null;
	public function get_handle()
	{
		return $this->handle;
	}
	
	private $email = null;
	public function get_email()
	{
		if (!($this->is_session_user())) return null;
		
		return $this->email;
	}
	
	private $name_family = null;
	public function get_name_family()
	{
		if (!($this->is_session_user())) return null;
		
		return $this->name_family;
	}
	
	private $name_given = null;
	public function get_name_given()
	{
		if (!($this->is_session_user())) return null;
		
		return $this->name_given;
	}
	
	//  Returns the full name, formatted with given/family names in the right places
	public function get_name_full($family_first = false)
	{
		if (!($this->is_session_user())) return null;
		
		return sprintf("%s %s",
			$family_first ? $this->get_name_family() : $this->get_name_given(),
			$family_first ? $this->get_name_given() : $this->get_name_family()
		);
	}
	
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
		if (!$result_assoc) return null;
		
		return new User(
			$result_assoc["user_id"],
			$result_assoc["handle"],
			$result_assoc["email"],
			$result_assoc["name_family"],
			$result_assoc["name_given"]
		);
	}
	
	private $lists;
	public function get_lists()
	{
		//  Don't let the session user get lists for other users
		if (!($this->is_session_user())) return null;
		
		if (!isset ($this->lists))
		{
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT * FROM lists WHERE user_id = %d",
				$this->get_user_id()
			));
			
			//  Unknown error
			if (!$result) return null;
			
			$lists = array ();
			while (!!($result_assoc = $result->fetch_assoc()))
			{
				if (!!($list = EntryList::select($result_assoc["list_id"]))) array_push($lists, $list);
			}
			
			$this->lists = $lists;
		}
		
		return $this->lists;
	}
	
	private $instructor_courses;
	public function get_instructor_courses()
	{
		//  STUB
		return array ();
	}
	
	private $student_courses;
	public function get_student_courses()
	{
		//  STUB
		return array ();
	}
	
	public function in_array($array)
	{
		foreach ($array as $user)
		{
			if ($user->equals($this)) return true;
		}
		
		return false;
	}
	
	public function equals($user)
	{
		return $this === $user || (!!$user && $user->get_user_id() === $this->get_user_id());
	}
	
	public function is_session_user()
	{
		return $this->equals(Session::get_user());
	}
	
	public function assoc_for_json($privacy = null)
	{
		if ($privacy === null) $privacy = !($this->is_session_user());
		
		return array (
			"userId" => $this->user_id,
			"handle" => $this->handle,
			"email" => $privacy ? null : $this->email,
			"nameGiven" => $privacy ? null : $this->name_given,
			"nameFamily" => $privacy ? null : $this->name_family
		);
	}
}

?>