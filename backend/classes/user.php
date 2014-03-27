<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class User extends DatabaseRow
{
	/***    CLASS/STATIC    ***/
	
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
		return self::select_by_id("users", "user_id", $user_id);
	}
	
	//  Inserts a row into users table and returns corresponding User object
	public static function insert($email, $handle, $password, $name_family = "", $name_given = "")
	{
		$mysqli = Connection::get_shared_instance();
		
		if (!self::validate_email($email))
		{
			Session::get()->set_error_assoc("Invalid Email", "Email must conform to the standard pattern.");
		}
		else if (!self::validate_password($password))
		{
			Session::get()->set_error_assoc("Invalid Password", "Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character.");
		}
		else if (!self::validate_handle($handle))
		{
			Session::get()->set_error_assoc("Invalid Handle", "Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
		}
		else
		{
			//  Check whether requested handle conflicts with any existing handle
			$result = $mysqli->query(sprintf("SELECT * FROM users WHERE handle = '%s'",
				$mysqli->escape_string($handle)
			));
			
			if ($existing_user = $result->fetch_assoc())
			{
				Session::get()->set_error_assoc("Handle Conflict", "The requested handle is already taken.");
				return null;
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
			if (!$result || !($result_assoc = $result->fetch_assoc()))
			{
				Session::get()->set_error_assoc("Unknown Error", "The back end unexpectedly failed to create the user.");
				return null;
			}
			
			return self::from_mysql_result_assoc($result_assoc);
		}
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
		
		if (!self::assoc_contains_keys($result_assoc, $mysql_columns)) return null;
		
		return new User(
			$result_assoc["user_id"],
			$result_assoc["handle"],
			$result_assoc["email"],
			$result_assoc["name_family"],
			$result_assoc["name_given"]
		);
	}
	
	//  To be completed....
	//      This function will factor out get_lists(), get_student_courses(), and get_instructor_courses()
	/*
	private function populate(&$collection, $class, $table_relational, $table_informative, $id_column)
	{
		if (!($this->is_session_user())) return null;
		
		if (!isset($collection))
		{
			$collection = array ();
			
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query("SELECT $table_informative.* FROM $table_relational LEFT JOIN $table_informative USING ($id_column) WHERE $table_relational.user_id = " . $this->get_user_id());
			
			//  Unknown error
			if (!$result) return null;
			
			while (!!($result_assoc = $result->fetch_assoc()))
			{
				if (!!($instance = $class::from_mysql_result_assoc($result_assoc)))
				{
					array_push($collection, $instance);
				}
			}
		}
		
		return $collection;
	}
	*/
	
	private $lists;
	public function get_lists()
	{
		//  return $this->populate($this->lists, EntryList, "user_lists", "lists", "list_id");
		
		if (!($this->is_session_user())) return null;
		
		if (!isset($this->lists))
		{
			$this->lists = array ();
			
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT * FROM lists WHERE user_id = %d",
				$this->get_user_id()
			));
			
			while (!!($result_assoc = $result->fetch_assoc()))
			{
				if (!!($list = EntryList::from_mysql_result_assoc($result_assoc)))
				{
					array_push($this->lists, $list);
				}
			}
		}
		
		return $this->lists;
	}
	
	private $courses;
	public function get_courses()
	{
		//  return $this->populate($this->instructor_courses, Course, "course_instructors", "courses", "course_id");
		
		if (!($this->is_session_user())) return null;
		
		if (!isset($this->courses))
		{
			$this->courses = array ();
			
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT * FROM courses WHERE user_id = %d",
				$this->get_user_id()
			));
			
			while (!!($result_assoc = $result->fetch_assoc()))
			{
				if (!!($course = Course::from_mysql_result_assoc($result_assoc)))
				{
					array_push($this->courses, $course);
				}
			}
		}
		
		return $this->courses;
	}
	
	
	private $instructor_courses;
	public function get_instructor_courses()
	{
		//  return $this->populate($this->instructor_courses, Course, "course_instructors", "courses", "course_id");
		
		if (!($this->is_session_user())) return null;
		
		if (!isset($this->instructor_courses))
		{
			$this->instructor_courses = array ();
			
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT courses.* FROM course_instructors LEFT JOIN courses USING (course_id) WHERE course_instructors.user_id = %d",
				$this->get_user_id()
			));
			
			while (!!($result_assoc = $result->fetch_assoc()))
			{
				if (!!($course = Course::from_mysql_result_assoc($result_assoc)))
				{
					array_push($this->instructor_courses, $course);
				}
			}
		}
		
		return $this->instructor_courses;
	}
	
	private $student_courses;
	public function get_student_courses()
	{
		//  return $this->populate($this->student_courses, Course, "course_students", "courses", "course_id");
		
		if (!($this->is_session_user())) return null;
		
		if (!isset($this->student_courses))
		{
			$this->student_courses = array ();
			
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT courses.* FROM course_students LEFT JOIN courses USING (course_id) WHERE course_students.user_id = %d",
				$this->get_user_id()
			));
			
			while (!!($result_assoc = $result->fetch_assoc()))
			{
				if (!!($course = Course::from_mysql_result_assoc($result_assoc)))
				{
					array_push($this->student_courses, $course);
				}
			}
		}
		
		return $this->student_courses;
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
			"handle" => $this->handle,
			"email" => $privacy ? null : $this->email,
			"nameGiven" => $privacy ? null : $this->name_given,
			"nameFamily" => $privacy ? null : $this->name_family
		);
	}
}

?>