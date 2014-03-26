<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Course extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	
	private static $courses_by_id = array ();
	
	public static function insert($lang_code_0, $lang_code_1, $course_name = null)
	{
		if (!Session::get()->get_user())
		{
			return Course::set_error_description("Session user has not reauthenticated.");
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
		
		$course = self::select_by_id($mysqli->insert_id);
		
		$mysqli->query(sprintf("INSERT INTO course_instructors (course_id, user_id) VALUES (%d, %d)",
			$course->get_course_id(),
			Session::get()->get_user()->get_user_id()
		));
		
		return $course;
	}
	
	public static function select_by_id($course_id)
	{
		$course_id = intval($course_id, 10);
		
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query("SELECT * FROM courses WHERE course_id = $course_id");
		
		if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
		{
			return Course::from_mysql_result_assoc($result_assoc);
		}
		
		return Course::set_error_description("Failed to select course where course_id = $course_id.");
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
	
	private $course_name = null;
	public function get_course_name()
	{
		return $this->course_name;
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
	
	private $instructors;
	public function get_instructors()
	{
		if (!isset($this->instructors))
		{
			$this->instructors = array ();
			
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query(sprintf("SELECT users.* FROM course_instructors LEFT JOIN users USING (user_id) WHERE course_id = %d",
				$this->get_course_id()
			));
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				array_push($this->instructors, User::from_mysql_result_assoc($result_assoc));
			}
		}
		return $this->instructors;
	}
	public function session_user_is_instructor()
	{
		if (!Session::get()->get_user()) return false;
		
		foreach ($this->get_instructors() as $instructor)
		{
			if (Session::get()->get_user()->equals($instructor)) return true;
		}
		
		return false;
	}
	
	private $students;
	public function get_students()
	{
		if (!isset($this->students))
		{
			$this->students = array ();
			
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query(sprintf("SELECT users.* FROM course_students LEFT JOIN users USING (user_id) WHERE course_id = %d",
				$this->get_course_id()
			));
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				array_push($this->students, User::from_mysql_result_assoc($result_assoc));
			}
		}
		
		return $this->students;
	}
	public function session_user_is_student()
	{
		if (!Session::get()->get_user()) return false;
		
		foreach ($this->get_students() as $student)
		{
			if (Session::get()->get_user()->equals($student)) return true;
		}
		
		return false;
	}
	
	private $units;
	public function get_units()
	{
		if (!isset($this->units))
		{
			$this->units = array();
			
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT * FROM course_units WHERE course_id = %d",
				intval($this->get_course_id(), 10)
			));

			while (($unit_assoc = $result->fetch_assoc()))
			{
				array_push($this->units, Unit::from_mysql_result_assoc($unit_assoc["unit_id"]));
			}
		}
		
		return $this->units;
	}
	public function get_lists()
	{
		$lists = array ();
		foreach ($this->get_units() as $unit)
		{
			$lists = array_merge(array_diff($unit->get_lists(), $lists));
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
		
		Course::$courses_by_id[$this->course_id] = $this;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		if (!$result_assoc)
		{
			return Course::set_error_description("Invalid result_assoc.");
		}
		
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
		return $this->session_user_is_instructor() || $this->get_owner()->equals(Session::get()->get_user());
	}
	
	public function session_user_can_read()
	{
		return $this->session_user_can_write() || $this->session_user_is_instructor() || $this->session_user_is_student();
	}
	
	public function delete()
	{
		if (!$this->get_owner()->equals(Session::get()->get_user()))
		{
			return Course::set_error_description("Session user is not owner of course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM courses WHERE course_id = %d",
			$this->get_course_id()
		));
		
		return $this;
	}
	
	private function users_add(&$array, $table, $user)
	{
		if (!$this->session_user_can_write())
		{
			return Course::set_error_description("Session user cannot edit course.");
		}
		
		if ($user->in_array($array))
		{
			return Course::set_error_description("Course cannot add user.");
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
			return Course::set_error_description("Session user cannot edit course.");
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
		return $this->users_remove($this->get_instructors(), "course_instructors", $user);
	}
	
	public function students_remove($user)
	{
		return $this->users_remove($this->get_students(), "course_students", $user);
	}
	
	public function assoc_for_json($privacy = null)
	{
		$omniscience = $this->get_owner()->equals(Session::get()->get_user());
		
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