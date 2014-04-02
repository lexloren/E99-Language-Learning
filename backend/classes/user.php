<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class User extends DatabaseRow
{
	/***    CLASS/STATIC    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function validate_email($string_in_question)
	{
		$string_in_question = strtolower($string_in_question);
		return strlen($string_in_question) < 64
			&& !!preg_match("/^[a-z](\+|-|_|\.)?([a-z\d]+(\+|-|_|\.)?)*@([a-z][a-z\d]*\.){1,3}[a-z]{2,3}$/", $string_in_question);
	}

	//  Valid handle consists of between 4 and 63 (inclusive) alphanumeric characters
	//      beginning with a letter.
	public static function validate_handle($string_in_question)
	{
		$string_in_question = strtolower($string_in_question);
		return strlen($string_in_question) >= 4
			&& strlen($string_in_question) < 64
			&& !!preg_match("/^[a-z]+[a-z\d]*$/", $string_in_question);
	}

	//  Valid password consists of between 6 and 31 (inclusive) characters
	//      and contains at least one letter, one number, and one non-alphanumeric character.
	public static function validate_password($string_in_question)
	{
		return strlen($string_in_question) >= 6
			&& strlen($string_in_question) < 32
			&& !!preg_match("/[\d]/", $string_in_question)
			&& !!preg_match("/[A-Za-z]/", $string_in_question)
			&& !!preg_match("/[^\dA-Za-z]/", $string_in_question)
			&& !!preg_match("/^.*$/", $string_in_question);
	}
	
	//  Creates a User object by selecting from the database
	public static function select_by_id($user_id)
	{
		return parent::select("users", "user_id", $user_id);
	}
	
	public static function find($query)
	{
		$mysqli = Connection::get_shared_instance();
		
		$query = $mysqli->escape_string($query);
		
		$result = $mysqli->query("SELECT * FROM users WHERE email LIKE '$query' OR handle LIKE '$query'");
		
		$users = array ();
		while (($result_assoc = $result->fetch_assoc()))
		{
			array_push($users, User::from_mysql_result_assoc($result_assoc));
		}
		return $users;
	}
	
	//  Inserts a row into users table and returns corresponding User object
	public static function insert($email, $handle, $password, $name_family = "", $name_given = "")
	{
		$mysqli = Connection::get_shared_instance();
		
		if (!self::validate_email($email))
		{
			return static::set_error_description("Email must conform to the standard pattern.");
		}
		
		if (!self::validate_password($password))
		{
			return static::set_error_description("Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character.");
		}
		
		if (!self::validate_handle($handle))
		{
			return static::set_error_description("Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
		}

		//  Check whether requested handle conflicts with any existing handle
		$existing_users = self::find($handle);
		if (count($existing_users) > 0)
		{
			return static::set_error_description("The requested handle is already in use.");
		}

		//  Check whether requested email conflicts with any existing email
		$existing_users = self::find($email);
		if (count($existing_users) > 0)
		{
			return static::set_error_description("The requested email is already in use.");
		}
		
		//  Good to go, so insert the new user
		$mysqli->query(sprintf("INSERT INTO users (handle, email, pswd_hash, name_given, name_family) VALUES ('%s', '%s', PASSWORD('%s'), '%s', '%s')",
			$mysqli->escape_string($handle),
			$mysqli->escape_string($email),
			$mysqli->escape_string($password),
			$mysqli->escape_string($name_given),
			$mysqli->escape_string($name_family)
		));
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Failed to insert user: " . $mysqli->error);
		}
		
		return self::select_by_id($mysqli->insert_id);
	}
	
	/***    INSTANCE    ***/
	
	private $user_id = null;
	public function get_user_id()
	{
		return $this->user_id;
	}
	protected function get_owner()
	{
		return $this;
	}
	
	private $handle = null;
	public function get_handle()
	{
		return $this->handle;
	}
	
	private $email = null;
	public function get_email($privacy = false)
	{
		return $privacy ? null : $this->email;
	}
	public function set_email($email)
	{
		$email = strtolower($email);
		
		if (!self::validate_email($email))
		{
			return static::set_error_description("Email failed to conform to standard pattern.");
		}
		
		if (!self::update_this($this, "users", array ("email" => $email), "user_id", $this->get_user_id()))
		{
			return null;
		}
		$this->email = $email;
		return $this;
	}
	
	private $name_family = null;
	public function get_name_family($privacy = false)
	{
		return $privacy ? null : $this->name_family;
	}
	public function set_name_family($name_family)
	{
		if (!self::update_this($this, "users", array ("name_family" => $name_family), "user_id", $this->get_user_id()))
		{
			return null;
		}
		$this->name_family = $name_family;
		return $this;
	}
	
	private $name_given = null;
	public function get_name_given($privacy = false)
	{
		return $privacy ? null : $this->name_given;
	}
	public function set_name_given($name_given)
	{
		if (!self::update_this($this, "users", array ("name_given" => $name_given), "user_id", $this->get_user_id()))
		{
			return null;
		}
		$this->name_given = $name_given;
		return $this;
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
		
		self::register($this->user_id, $this);
	}
	
	//  Creates a User object from an associative array fetched from a mysql_result
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"user_id",
			"handle",
			"email",
			"name_family",
			"name_given"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new User(
				$result_assoc["user_id"],
				$result_assoc["handle"],
				$result_assoc["email"],
				$result_assoc["name_family"],
				$result_assoc["name_given"]
			)
			: null;
	}
	
	public function delete()
	{
		return static::set_error_description("Failed to delete user.");
	}
	
	private $lists;
	public function get_lists()
	{
		return self::get_cached_collection($this->lists, "EntryList", "lists", "user_id", $this->get_user_id());
	}
	
	private $courses;
	public function get_courses()
	{
		return self::get_cached_collection($this->courses, "Course", "courses", "user_id", $this->get_user_id());
	}
	
	
	private $instructor_courses;
	public function get_instructor_courses()
	{
		$table = "course_instructors LEFT JOIN courses USING (course_id)";
		return self::get_cached_collection($this->instructor_courses, "Course", $table, "course_instructors.user_id", $this->get_user_id(), "courses.*");
	}
	
	private $student_courses;
	public function get_student_courses()
	{
		$table = "course_students LEFT JOIN courses USING (course_id)";
		return self::get_cached_collection($this->student_courses, "Course", $table, "course_students.user_id", $this->get_user_id(), "courses.*");
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
		return !!Session::get() && $this->equals(Session::get()->get_user());
	}
	
	public function assoc_for_json($privacy = null)
	{
		if ($privacy === null) $privacy = !($this->is_session_user());
		
		return array (
			"userId" => $this->user_id,
			"isSessionUser" => $this->is_session_user(),
			"handle" => $this->get_handle(),
			"email" => $this->get_email($privacy),
			"nameGiven" => $this->get_name_given($privacy),
			"nameFamily" => $this->get_name_family($privacy)
		);
	}
}

?>