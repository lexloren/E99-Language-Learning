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
	
	/*
	public static function select($course_id)
	{
		$course_id = intval($course_id, 10);
		
		if (!in_array($course_id, array_keys(self::$courses_by_id)))
		{
			$mysqli = Connection::get_shared_instance();
			
			
			
			//  First, try to get a course for which the session user is an instructor or student
			if (!!Session::get_user())
			{
				$course_matches = sprintf("user_id = %d AND course_id = %d",
					Session::get_user()->get_user_id(),
					$course_id
				);
				
				$courses_join_instructors = "course_instructors LEFT JOIN courses USING course_id";
				$result = $mysqli->query("SELECT courses.* FROM $courses_join_instructors WHERE $course_matches");
				
				//  If we didn't find a course for which the session user is an instructor,
				//      then check for a course for which the session user is a student
				if (!$result || $result->num_rows === 0)
				{
					$courses_join_students = "course_students LEFT JOIN courses USING course_id";
					$result = $mysqli->query("SELECT courses.* FROM $courses_join_students WHERE $course_matches");
				}
				
				if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
				{
					$course = Course::from_mysql_result_assoc($result_assoc);
				}
			}
			
			//  If still no result, then check for a public course
			if (!isset ($course))
			{
				$result = $mysqli->query("SELECT * FROM courses WHERE course_id = %d AND public");
				if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
				{
					$course = Course::from_mysql_result_assoc($result_assoc);
				}
			}
			
			//  If we found a course, make sure it's registered in the cache
			if (isset ($course))
			{
				self::$courses_by_id[$course_id] = $course;
			}
		}
		
		//  If we found a course or had already cached a course, then return it.
		return in_array($course_id, array_keys(self::$courses_by_id))
			? self::$courses_by_id[$course_id]
			: null;
	}
	*/
	
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
			if (Session::get_user()->get_user_id() === $instructor->get_user_id()) return true;
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
			if (Session::get_user()->get_user_id() === $student->get_user_id()) return true;
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
	
	private function add(&$variable, $table, $keyed_values)
	{
		$columns = implode(", ", array_keys($keyed_values));
		$values = implode(", ", array_values($keyed_values));
		
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function add_instructor($instructor_to_add)
	{
		if (!self::session_user_can_write()) return null;
		
		//  Insert into user_entries from dictionary, if necessary
		$entry_added = $entry_to_add->copy_for_session_user();
		
		$mysqli = Connection::get_shared_instance();
		
		//  Insert into list_entries for $this->list_id and $entry->entry_id
		//      If this entry already exists in the list, then ignore the error
		$mysqli->query(sprintf("INSERT IGNORE INTO list_entries (list_id, entry_id) VALUES (%d, %d)",
			$this->list_id,
			$entry_added->get_entry_id()
		));
		
		return $this;
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function remove_instructor($instructor_to_remove)
	{
		if (!self::session_user_can_write()) return null;
		
		foreach ($this->get_entries() as $entry_removed)
		{
			if ($entry_removed->entry_id === $entry_to_remove->entry_id)
			{
				$mysqli->query(sprintf("DELETE FROM list_entries (list_id, entry_id) VALUES (%d, %d)",
					$this->list_id,
					$entry_removed->entry_id
				));
				
				$this->entries = array_diff($this->entries, array ($entry_removed));
				
				return $this;
			}
		}
		
		//  Tried to remove an entry that's apparently not in this list
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