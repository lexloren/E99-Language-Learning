<?php

require_once "./backend/connection.php";
require_once "./backend/support.php";

class Course
{
	/***    STATIC/CLASS    ***/
	
	private static $courses_by_id = array ();
	
	public static function insert($lang_code_0, $lang_code_1, $course_name = null)
	{
		if (!Session::get_user()) return null;
		
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
		
		$mysqli->query(sprintf("INSERT INTO courses (lang_id_0, lang_id_1, course_name) %s",
			"SELECT $language_ids, $course_name FROM $languages_join ON $language_codes_match"
		));
		
		$course_id = self::select($mysqli->insert_id);
		
		$mysqli->query(sprintf("INSERT INTO course_instructors (course_id, user_id) VALUES (%d, %d)",
			$course_id,
			Session::get_user()->get_user_id()
		));
		
		return self::select($mysqli->insert_id);
	}
	
	public static function select($course_id)
	{
		$course_id = intval($course_id, 10);
		
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query("SELECT * FROM courses WHERE course_id = $course_id");
		
		if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
		{
			return Course::from_mysql_result_assoc($result_assoc);
		}
		
		return null;
	}
	
	/***    INSTANCE    ***/

	private $course_id;
	public function get_course_id()
	{
		return $this->course_id;
	}
	
	private $course_name;
	public function get_course_name()
	{
		return $this->course_name;
	}
	
	private $lang_id_0;
	public function get_lang_id_0()
	{
		return $this->lang_id_0;
	}
	public function get_lang_code_0()
	{
		return Dictionary::get_lang_code($this->get_lang_id_0());
	}
	
	private $lang_id_1;
	public function get_lang_id_1()
	{
		return $this->lang_id_1;
	}
	public function get_lang_code_1()
	{
		return Dictionary::get_lang_code($this->get_lang_id_1());
	}
	
	private $public;
	public function is_public()
	{
		return !!$this->public;
	}
	
	private $instructors;
	public function get_instructors()
	{
		if (!isset ($this->instructors))
		{
			$this->instructors = array ();
			
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query(sprintf("SELECT * FROM course_instructors WHERE course_id = %d",
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
		if (!Session::get_user()) return false;
		
		foreach ($this->get_instructors() as $instructor)
		{
			if (Session::get_user()->equals($instructor)) return true;
		}
		
		return false;
	}
	
	private $students;
	public function get_students()
	{
		if (!isset ($this->students))
		{
			$this->students = array ();
			
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query(sprintf("SELECT * FROM course_students WHERE course_id = %d",
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
		if (!Session::get_user()) return false;
		
		foreach ($this->get_students() as $student)
		{
			if (Session::get_user()->equals($student)) return true;
		}
		
		return false;
	}
	
	private $units;
	public function get_units()
	{
		if (!isset ($this->units))
		{
			$this->units = array();
			
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT * FROM course_units WHERE course_id = %d",
				intval($this->get_course_id())
			));

			while (($unit_assoc = $result->fetch_assoc()))
			{
				array_push($this->units, Unit::from_mysql_result_assoc($unit_assoc["unit_id"]));
			}
		}
		
		return $this->units;
	}
	
	private function __construct($course_id, $lang_id_0, $lang_id_1, $course_name = null, $public = false)
	{
		$this->$course_id = intval($course_id, 10);
		$this->lang_id_0 = $lang_id_0;
		$this->lang_id_1 = $lang_id_1;
		$this->course_name = $course_name;
		$this->public = intval($public, 10);
		
		Course::$courses_by_id[$this->course_id] = $this;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		return new Course(
			$result_assoc["course_id"],
			$result_assoc["lang_id_0"],
			$result_assoc["lang_id_1"],
			!!$result_assoc["course_name"] && strlen($result_assoc["course_name"]) > 0 ? $result_assoc["course_name"] : null,
			$result_assoc["public"]
		);
	}
	
	public function delete()
	{
		if (!$this->session_user_is_instructor()) return null;
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM courses WHERE course_id = %d",
			$this->get_course_id()
		));
		
		return null;
	}
	
	private function add_user(&$array, $table, $user)
	{
		if (!$this->session_user_is_instructor() || $user->in_array($array)) return null;
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT IGNORE INTO $table (course_id, user_id) VALUES (%d, %d)",
			$this->get_course_id(),
			$user->get_user_id()
		));
		
		array_push($array, $user);
		
		return $this;
	}
	
	public function add_instructor($user)
	{
		return $this->add_user($this->get_instructors(), "course_instructors", $user);
	}
	
	public function add_student($user)
	{
		return $this->add_user($this->get_students(), "course_students", $user);
	}
	
	private function remove_user(&$array, $table, $user)
	{
		if (!$this->session_user_is_instructor()) return null;
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM $table WHERE course_id = %d AND user_id = %d",
			$this->get_course_id(),
			$user->get_user_id()
		));
		
		$array_new = array ();
		foreach ($array as $item)
		{
			if (!$array->equals($user)) array_push($array_new, $item);
		}
		
		$array = $array_new;
		
		return $this;
	}
	
	public function remove_instructor($user)
	{
		return $this->remove_user($this->get_instructors(), "course_instructors", $user);
	}
	
	public function remove_student($user)
	{
		return $this->remove_user($this->get_students(), "course_students", $user);
	}
	
	public function insert_unit($unit_name)
	{
		//  ...
		return null;
	}
	
	public function assoc_for_json($privacy = null)
	{
		$omniscience = $this->session_user_is_instructor();
		
		if ($omniscience) $privacy = false;
		else if ($privacy === null) $privacy = !$this->session_user_is_student();
		
		if (!$privacy)
		{
			$instructors_returnable = array ();
			foreach ($this->get_instructors() as $instructor)
			{
				array_push($instructors_returnable, $instructor->assoc_for_json(!$omniscience));
			}
			
			$students_returnable = array ();
			foreach ($this->get_students() as $student)
			{
				array_push($students_returnable, $student->assoc_for_json(!$omniscience));
			}
			
			$units_returnable = array ();
			// ...
		}
		
		return array (
			"courseId" => $this->get_course_id(),
			"courseName" => !$privacy ? $this->get_course_name() : null,
			"instructors" => !$privacy ? $instructors_returnable : null,
			"students" => !$privacy ? $students_returnable : null,
			"units" => !$privacy ? $units_returnable : null
		);
	}
}

?>