<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class User extends DatabaseRow
{
	/***    CLASS/STATIC    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	private static function random_alphanumeric($length)
	{
		$random_alphanumeric = "";
		for ($i = 1; $i <= $length; $i ++)
		{
			$r = rand(0, 61);
			$r += ord('0');
			if ($r > ord('9')) $r += 7;
			if ($r > ord('Z')) $r += 6;
			$random_alphanumeric .= chr($r);
		}
		return $random_alphanumeric;
	}
	
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
		
		if (is_array($query) && !is_string($query))
		{
			foreach ($query as &$q)
			{
				$q = self::validate_email($q) || self::validate_handle($q) ? $mysqli->escape_string($q) : "";
			}
			
			$query = implode("','", $query);
		}
		else $query = self::validate_email($query) || self::validate_handle($query)
			? $mysqli->escape_string($query) : "";
		
		$result = $mysqli->query("SELECT * FROM users WHERE email IN ('$query') OR handle IN ('$query')");
		
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
			return static::errors_push("Email must conform to the standard pattern.");
		}
		
		if (!self::validate_password($password))
		{
			return static::errors_push("Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character.");
		}
		
		if (!self::validate_handle($handle))
		{
			return static::errors_push("Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
		}

		//  Check whether requested handle conflicts with any existing handle
		$existing_users = self::find($handle);
		if (count($existing_users) > 0)
		{
			return static::errors_push("The requested handle is already in use.");
		}

		//  Check whether requested email conflicts with any existing email
		$existing_users = self::find($email);
		if (count($existing_users) > 0)
		{
			return static::errors_push("The requested email is already in use.");
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
			return static::errors_push("Failed to insert user: " . $mysqli->error . ".");
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
	
	public function set_password($password)
	{
		$mysqli = Connection::get_shared_instance();
			
		//  See whether we can authenticate with the handle and password posted
		$mysqli->query(sprintf("UPDATE users SET pswd_hash = PASSWORD('%s') WHERE handle = '%s' AND email = '%s' LIMIT 1",
			$mysqli->escape_string($password),
			$mysqli->escape_string($this->get_handle()),
			$mysqli->escape_string($this->get_email(false))
		));
		
		if (!!$mysqli->error)
		{
			return static::errors_push("User failed to set password: " . $mysqli->error . ".");
		}
		
		return $this;
	}
	
	private $status_id = null;
	public function get_status_id()
	{
		$this->status_id;
	}
	public function get_status($status)
	{
		if (!$this->get_status_id()) return null;
		
		if (($status = Status::select_by_id($this->get_status_id())))
		{
			return $status;
		}
		else return static::errors_push("Failed to get user status: " . Status::errors_unset());
	}
	public function set_status($status)
	{
		if (!self::update_this($this, "users", array ("status_id" => ($status_id = $status->get_status_id())), "user_id", $this->get_user_id()))
		{
			return null;
		}
		$this->status_id = $status_id;
		return $this;
	}
	
	public function check_password($password)
	{
		$mysqli = Connection::get_shared_instance();
			
		//  See whether we can authenticate with the handle and password posted
		$result = $mysqli->query(sprintf("SELECT * FROM users WHERE (handle = '%s' AND email = '%s') AND pswd_hash = PASSWORD('%s')",
			$mysqli->escape_string($this->get_handle()),
			$mysqli->escape_string($this->get_email(false)),
			$mysqli->escape_string($password)
		));
		
		if (!!$mysqli->error)
		{
			return static::errors_push("User failed to check password: " . $mysqli->error . ".");
		}
		
		return $result->num_rows === 1;
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
			return static::errors_push("Email failed to conform to standard pattern.");
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
		//Arunabha: Commented this. Why an instructor in session cannot get the name of a student?
		//if (!($this->is_session_user())) return null;
		
		return sprintf("%s %s",
			$family_first ? $this->get_name_family() : $this->get_name_given(),
			$family_first ? $this->get_name_given() : $this->get_name_family()
		);
	}
	
	public function __construct($user_id, $status_id, $handle, $email = null, $name_family = null, $name_given = null)
	{
		$this->user_id = intval($user_id, 10);
		$this->status_id = intval($status_id, 10);
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
			"status_id",
			"handle",
			"email",
			"name_family",
			"name_given"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["user_id"],
				$result_assoc["status_id"],
				$result_assoc["handle"],
				$result_assoc["email"],
				$result_assoc["name_family"],
				$result_assoc["name_given"]
			)
			: null;
	}
	
	public function delete()
	{
		return static::errors_push("Failed to delete user.");
	}
	
	public function uncache_lists()
	{
		if (isset($this->lists)) unset($this->lists);
	}
	public function uncache_all_courses()
	{
		$this->uncache_courses();
		$this->uncache_courses_instructed();
		$this->uncache_courses_studied();
		$this->uncache_courses_researched();
	}
	public function uncache_courses()
	{
		if (isset($this->courses)) unset($this->courses);
	}
	public function uncache_courses_instructed()
	{
		if (isset($this->courses_instructed)) unset($this->courses_instructed);
	}
	public function uncache_courses_studied()
	{
		if (isset($this->courses_studied)) unset($this->courses_studied);
	}
	public function uncache_courses_researched()
	{
		if (isset($this->courses_researched)) unset($this->courses_researched);
	}
	public function uncache_all()
	{
		$this->uncache_lists();
		$this->uncache_all_courses();
	}
	
	private $lists;
	public function lists($course_ids = null)
	{
		if ($course_ids === null)
		{
			return self::cache($this->lists, "EntryList", "lists", "user_id", $this->get_user_id());
		}
		else
		{
			foreach ($course_ids as &$course_id)
			{
				$course_id = intval($course_id, 10);
			}
			
			$course_ids_string = implode(",", $course_ids);
			
			$mysqli = Connection::get_shared_instance();
			
			$course_units = "(courses CROSS JOIN course_units USING (course_id))";
			$unit_lists = "($course_units CROSS JOIN course_unit_lists USING (unit_id))";
			
			$result = $mysqli->query("SELECT lists.* FROM lists CROSS JOIN $unit_lists USING (list_id) WHERE lists.user_id = %d AND course_id IN ($course_ids_string)");
			
			if (!!$mysqli->error)
			{
				return static::errors_push("User failed to get lists by course id: " . $mysqli->error . ".");
			}
			
			$lists = array ();
			while (($result_assoc = $result->fetch_assoc()))
			{
				array_push($lists, EntryList::from_mysql_result_assoc($result_assoc));
			}
			
			return $lists;
		}
	}
	
	private $courses;
	public function courses()
	{
		return self::cache($this->courses, "Course", "courses", "user_id", $this->get_user_id());
	}
	
	private $sittings;
	public function sittings()
	{
		return self::cache($this->sittings, "Sitting", "course_students CROSS JOIN course_unit_test_sittings USING (student_id)", "user_id", $this->get_user_id());
	}
	
	private $languages;
	public function languages()
	{
		$table = "user_languages LEFT JOIN languages USING (lang_id)";
		return self::cache($this->languages, "Language", $table, "user_id", $this->get_user_id());
	}
	public function languages_add($language, $years = null)
	{
		if (!$language)
		{
			return static::errors_push("Failed to add null user language.");
		}
		
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit user.");
		}
		
		if ($years === null)
		{
			$years = "NULL";
		}
		else if (($years = intval($years, 10)) < 0)
		{
			return static::errors_push("User cannot add language with negative years.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO user_languages " .
							   "(user_id, lang_id, years) VALUES (%d, %d, $years)",
			$this->get_user_id(),
			$language->get_lang_id()
		));
		
		if ($mysqli->error)
		{
			return static::errors_push("Failed to add user language: " . $mysqli->error . ".");
		}
		
		if (isset($this->languages)) array_push($this->languages, $language);
		
		return $this;
	}
	public function languages_remove($language)
	{
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit user.");
		}
		
		if (!$language)
		{
			return static::errors_push("User cannot remove null user language.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM user_languages " .
							   "WHERE user_id = %d AND lang_id = %d",
			$this->get_user_id(),
			$language->get_lang_id()
		));
		
		if ($mysqli->error)
		{
			return static::errors_push("Failed to remove user language: " . $mysqli->error . ".");
		}
		
		if (isset($this->languages)) array_drop($this->languages, $language);
		
		return $this;
	}
	public function set_language_years($language, $years)
	{
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit user.");
		}
		
		if (!$language)
		{
			return static::errors_push("User cannot update null user language.");
		}
		
		if ($years === null)
		{
			$years = "NULL";
		}
		else if (($years = intval($years, 10)) < 0)
		{
			return static::errors_push("User cannot set language years to negative integer.");
		}
		
		if (!in_array($language, $this->languages()))
		{
			return static::errors_push("User cannot set language years for language not already associated with user.");
		}
			
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("UPDATE user_languages SET years = $years WHERE user_id = %d AND language_id = %d", $this->get_user_id(), $language->get_language_id()));
		
		if ($mysqli->error) return static::errors_push("User Modification", "User failed to set language years: " . $mysqli->error . ".");
		
		return $this;
	}
	
	public function get_language_years_json_assoc()
	{
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query(sprintf("SELECT lang_code, years FROM user_languages LEFT JOIN languages USING (lang_id) WHERE user_id = %d", $this->get_user_id()));
		
		if (!!$mysqli->error)
		{
			return static::errors_push("Failed to select from user_languages LEFT JOIN languages where user_id = " . $this->get_user_id() . ": " . $mysqli->error . ".");
		}
		
		$language_years_assoc = array ();
		
		while (($result_assoc = $result->fetch_assoc()))
		{
			array_push($language_years_assoc,
				array ($result_assoc["lang_code"] => $result_assoc["years"]));
		}
		
		return $language_years_assoc;
	}
	
	private $courses_instructed;
	public function courses_instructed()
	{
		$table = "course_instructors LEFT JOIN courses USING (course_id)";
		return self::cache($this->courses_instructed, "Course", $table, "course_instructors.user_id", $this->get_user_id(), "courses.*");
	}
	
	private $courses_studied;
	public function courses_studied()
	{
		$table = "course_students LEFT JOIN courses USING (course_id)";
		return self::cache($this->courses_studied, "Course", $table, "course_students.user_id", $this->get_user_id(), "courses.*");
	}
	
	private $courses_researched;
	public function courses_researched()
	{
		$table = "course_researchers LEFT JOIN courses USING (course_id)";
		return self::cache($this->courses_researched, "Course", $table, "course_researchers.user_id", $this->get_user_id(), "courses.*");
	}
	
	public function in($array)
	{
		foreach ($array as $user)
		{
			if ($user->equals($this)) return true;
		}
		
		return false;
	}
	
	public function user_can_read($user)
	{
		return parent::user_can_read($user)
			|| $this->user_can_read_via_some_course($user);
	}
	
	public function user_can_read_via_some_course($user)
	{
		foreach (array_merge($this->courses(), $this->courses_instructed(), $this->courses_studied()) as $course)
		{
			if ($course->user_can_read($user)) return true;
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
	
	public function courses_owned_count()
	{
		return self::count("courses", "user_id", $this->get_user_id());
	}
	
	public function courses_instructed_count()
	{
		return self::count("course_instructors", "user_id", $this->get_user_id());
	}
	
	public function courses_studied_count()
	{
		return self::count("course_students", "user_id", $this->get_user_id());
	}
	
	public function courses_researched_count()
	{
		return self::count("course_researchers", "user_id", $this->get_user_id());
	}
	
	public function lists_count()
	{
		return self::count("lists", "user_id", $this->get_user_id());
	}
	
	public function json_assoc_condensed($privacy = null)
	{
		return $this->privacy_mask(array (
			"userId" => $this->user_id,
			"isSessionUser" => $this->is_session_user(),
			"handle" => $this->get_handle(),
			"email" => $this->get_email($privacy)
		), array ("userId", "handle", "isSessionUser"), $privacy);
	}
	
	public function json_assoc($privacy = null)
	{
		$assoc = $this->json_assoc_condensed($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["languageYears"] = $this->get_language_years_json_assoc();
		$assoc["nameGiven"] = $this->get_name_given($privacy);
		$assoc["nameFamily"] = $this->get_name_family($privacy);
		$assoc["coursesOwnedCount"] = $this->courses_owned_count();
		$assoc["coursesInstructedCount"] = $this->courses_instructed_count();
		$assoc["coursesStudiedCount"] = $this->courses_studied_count();
		$assoc["coursesResearchedCount"] = $this->courses_researched_count();
		$assoc["listsCount"] = $this->lists_count();
		
		return $this->privacy_mask($assoc, $public_keys, !$this->is_session_user());
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		$assoc = $this->json_assoc($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["coursesOwned"] = self::json_array($this->courses());
		$assoc["coursesInstructed"] = self::json_array($this->courses_instructed());
		$assoc["coursesStudied"] = self::json_array($this->courses_studied());
		$assoc["coursesResearched"] = self::json_array($this->courses_researched());
		$assoc["lists"] = self::json_array($this->lists());
		
		return $this->privacy_mask($assoc, $public_keys, !$this->is_session_user());
	}
}

?>