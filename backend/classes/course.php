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
		
	}
	
	private $lang_id_1;
	public function get_lang_id_1()
	{
		return $this->lang_id_1;
	}
	public function get_lang_code_1()
	{
		
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
			
		}
		return $this->instructors;
	}
	
	private $students;
	public function get_students()
	{
		if (!isset ($this->students))
		{
			
		}
		
		return $this->students;
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
	
	//  Returns true iff Session::get_user() can read this list for any reason
	private function session_user_can_read()
	{
		return $this->session_user_can_write()
			|| $this->session_user_can_read_via_course_sharing()
			|| !!$this->public;
	}
	
	//  Returns true iff Session::get_user() is in any course in which this list is shared
	private function session_user_can_read_via_course_sharing()
	{
		//  Stub...
		//      Will depend on implementing Course
		return false;
	}
	
	//  Returns true iff Session::get_user() owns this list
	private function session_user_can_write()
	{
		return !!Session::get_user() && (Session::get_user()->get_user_id() === $this->user_id);
	}
	
	public function delete()
	{
		$mysqli = Connection::get_shared_instance();
		
		if (!Session::get_user()) return null;
		
		$mysqli->query(sprintf("DELETE FROM lists WHERE user_id = %d AND list_id = %d",
			Session::get_user()->get_user_id(),
			$this->list_id
		));
		
		return null;
	}
	
	public function get_entries()
	{
		$mysqli = Connection::get_shared_instance();
		
		//  Need to add privileges here, based on public sharing and course-wide sharing
		
		if (!isset ($this->entries))
		{
			$result = $mysqli->query(sprintf("SELECT * FROM list_entries LEFT JOIN dictionary ON entry_id WHERE list_id = %d",
				intval($this->list_id)
			));
			
			$this->entries = array();
			if (!$result) return $this->entries;

			while (($entry_assoc = $result->fetch_assoc()))
			{
				array_push($this->entries, Dictionary::select_entry($entry_assoc["entry_id"]));
			}
		}
		return $this->entries;
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function add_entry($entry_to_add)
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
	public function remove_entry($entry_to_remove)
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
	
	//  Copies this list, setting the copy's owner to some other user
	//      Returns the copy
	public function copy_for_session_user()
	{
		if (!Session::get_user() || !session_user_can_read()) return null;
		//  Create a copy of the list with Session::get_user() as the owner
		
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