<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Course extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function insert($lang_code_0, $lang_code_1, $course_name = null, $timeframe = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return self::set_error_description("Session user has not reauthenticated.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$languages_join = "languages AS languages_0 CROSS JOIN languages AS languages_1";
		
		$language_0_matches = sprintf("languages_0.lang_code = '%s'",
			$mysqli->escape_string($lang_code_0)
		);
		$language_1_matches = sprintf("languages_1.lang_code = '%s'",
			$mysqli->escape_string($lang_code_1)
		);
		$language_codes_match = "$language_0_matches AND $language_1_matches";
		
		$language_ids = "languages_0.lang_id AS lang_id_0, languages_1.lang_id AS lang_id_1";
		
		$course_name = ($course_name !== null && strlen($course_name) > 0)
			? "'".$mysqli->escape_string($course_name)."'"
			: "NULL";
		
		$mysqli->query(sprintf("INSERT INTO courses (user_id, lang_id_0, lang_id_1, course_name) %s",
			"SELECT " . Session::get()->get_user()->get_user_id() . ", $language_ids, $course_name FROM $languages_join ON $language_codes_match"
		));
		
		if ($mysqli->error)
		{
			return self::set_error_description("Failed to insert course: " . $mysqli->error);
		}
		
		if (!($course = self::select_by_id($mysqli->insert_id)))
		{
			return null;
		}
		
		$mysqli->query(sprintf("INSERT INTO course_instructors (course_id, user_id) VALUES (%d, %d)",
			$course->get_course_id(),
			Session::get()->get_user()->get_user_id()
		));
		
		return $course;
	}
	
	public static function select_by_id($course_id)
	{
		return parent::select_by_id("courses", "course_id", $course_id);
	}
	
	/***    INSTANCE    ***/

	private $course_id = null;
	public function get_course_id()
	{
		return $this->course_id;
	}
	
	private $user_id = null;
	public function get_user_id()
	{
		return $this->user_id;
	}
	public function get_owner()
	{
		return User::select_by_id($this->get_user_id());
	}
	public function session_user_is_owner()
	{
		return !!Session::get() && !!Session::get()->get_user()
			&& Session::get()->get_user()->equals($this->get_owner());
	}
	
	private $course_name = null;
	public function get_course_name()
	{
		return $this->course_name;
	}
	public function set_course_name($course_name)
	{
		if (!self::update_this($this, "courses", array ("course_name", $course_name), "course_id", $this->get_course_id()))
		{
			return null;
		}
		$this->course_name = $course_name;
		return $this;
	}
	
	private $lang_id_0 = null;
	public function get_lang_id_0()
	{
		return $this->lang_id_0;
	}
	public function get_lang_code_0()
	{
		return Dictionary::get_lang_code($this->get_lang_id_0());
	}
	
	private $lang_id_1 = null;
	public function get_lang_id_1()
	{
		return $this->lang_id_1;
	}
	public function get_lang_code_1()
	{
		return Dictionary::get_lang_code($this->get_lang_id_1());
	}
	
	private $public = null;
	public function is_public()
	{
		return !!$this->public;
	}
	
	//  $message
	
	private $instructors;
	public function get_instructors()
	{
		$table = "course_instructors LEFT JOIN users USING (user_id)";
		return self::get_cached_collection($this->instructors, "User", $table, "course_id", $this->get_course_id());
	}
	public function session_user_is_instructor()
	{
		return !!Session::get() && Session::get()->get_user()->in_array($this->get_instructors());
	}
	
	private $students;
	public function get_students()
	{
		$table = "course_students LEFT JOIN users USING (user_id)";
		return self::get_cached_collection($this->students, "User", $table, "course_id", $this->get_course_id());
	}
	public function session_user_is_student()
	{
		return !!Session::get() && Session::get()->get_user()->in_array($this->get_students());
	}
	
	private $units;
	public function get_units()
	{
		$table = "course_students LEFT JOIN users USING (user_id)";
		return self::get_cached_collection($this->units, "Unit", "course_units", "course_id", $this->get_course_id());
	}
	public function get_lists()
	{
		$lists = array ();
		foreach ($this->get_units() as $unit)
		{
			foreach ($unit->get_lists() as $list)
			{
				if (!in_array($list, $lists))
				{
					array_push($lists, $list);
				}
			}
		}
		return $lists;
	}
	
	private function __construct($course_id, $user_id, $lang_id_0, $lang_id_1, $course_name = null, $public = false)
	{
		$this->course_id = intval($course_id, 10);
		$this->user_id = intval($user_id, 10);
		$this->lang_id_0 = $lang_id_0;
		$this->lang_id_1 = $lang_id_1;
		$this->course_name = $course_name;
		$this->public = intval($public, 10);
		
		self::register($this->course_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"course_id",
			"user_id",
			"lang_id_0",
			"lang_id_1",
			"course_name",
			"public"
		);
		
		if (!self::assoc_contains_keys($result_assoc, $mysql_columns)) return null;
		
		return new Course(
			$result_assoc["course_id"],
			$result_assoc["user_id"],
			$result_assoc["lang_id_0"],
			$result_assoc["lang_id_1"],
			$result_assoc["course_name"],
			$result_assoc["public"]
		);
	}
	
	public function session_user_can_write()
	{
		return $this->session_user_is_instructor()
			|| $this->session_user_is_owner();
	}
	
	public function session_user_can_read()
	{
		return $this->session_user_can_write()
			|| $this->session_user_is_student();
	}
	
	public function delete()
	{
		return self::delete_this($this, "courses", "course_id", $this->get_course_id());
	}
	
	private function users_add(&$array, $table, $user)
	{
		if (!$this->session_user_can_write())
		{
			return self::set_error_description("Session user cannot edit course.");
		}
		
		if ($user->in_array($array))
		{
			return self::set_error_description("Course cannot add user.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT IGNORE INTO $table (course_id, user_id) VALUES (%d, %d)",
			$this->get_course_id(),
			$user->get_user_id()
		));
		
		array_push($array, $user);
		
		return $this;
	}
	
	public function instructors_add($user)
	{
		if (!$this->session_user_is_owner())
		{
			return self::set_error_description("Session user is not course owner.");
		}
		return $this->users_add($this->get_instructors(), "course_instructors", $user);
	}
	
	public function students_add($user)
	{
		return $this->users_add($this->get_students(), "course_students", $user);
	}
	
	private function users_remove(&$array, $table, $user)
	{
		if (!$this->session_user_can_write())
		{
			return self::set_error_description("Session user cannot edit course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM $table WHERE course_id = %d AND user_id = %d",
			$this->get_course_id(),
			$user->get_user_id()
		));
		
		unset($array);
		
		return $this;
	}
	
	public function instructors_remove($user)
	{
		if (!$this->session_user_is_owner())
		{
			return self::set_error_description("Session user is not course owner.");
		}
		return $this->users_remove($this->get_instructors(), "course_instructors", $user);
	}
	
	public function students_remove($user)
	{
		return $this->users_remove($this->get_students(), "course_students", $user);
	}
	
	public function assoc_for_json($privacy = null)
	{
		$omniscience = $this->session_user_is_owner();
		
		if ($omniscience) $privacy = false;
		else if ($privacy === null) $privacy = !$this->session_user_can_read();
		
		return array (
			"courseId" => $this->get_course_id(),
			"courseName" => !$privacy ? $this->get_course_name() : null,
			"owner" => $this->get_owner()->assoc_for_json(),
			"isPublic" => !$privacy ? $this->is_public() : null,
			"timeframe" => null // Not yet implemented
		);
	}
}

?>