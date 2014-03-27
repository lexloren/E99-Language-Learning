<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Section extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	
	public static function insert($test_id, $section_name = null, $timer = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return self::set_error_description("Session user has not reauthenticated.");
		}
		
		$test = Test::select_by_id(($test_id = intval($test_id, 10)));
		
		if (!$test->session_user_is_instructor())
		{
			return self::set_error_description("Session user is not course instructor.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$section_name = $section_name !== null ? "'" . $mysqli->escape_string($section_name) . "'" : "NULL";
		$message = $message !== null ? "'" . $mysqli->escape_string($message) . "'" : "NULL";
		$section_number = count($test->get_sections()) + 1;
		$timer = intval($timer, 10);
		
		$mysqli->query("INSERT INTO course_unit_test_sections (test_id, section_name, section_nmbr, timer, message) VALUES ($test_id, $section_name, $section_number, $timer, $message)");
		
		if ($mysqli->error)
		{
			return self::set_error_description("Failed to insert test section: " . $mysqli->error);
		}
		
		return !!($section = self::select_by_id($mysqli->insert_id)) ? $section : null;
	}
	
	public static function select_by_id($section_id)
	{
		return self::select_by_id("course_unit_test_sections", "section_id", $section_id);
	}
	
	/***    INSTANCE    ***/

	private $section_id = null;
	public function get_section_id()
	{
		return $this->section_id;
	}
	
	private $test_id = null;
	public function get_test_id()
	{
		return $this->test_id;
	}
	public function get_test()
	{
		return Test::select_by_id($this->get_test_id());
	}
	public function get_unit_id()
	{
		return $this->get_test()->get_unit_id();
	}
	public function get_unit()
	{
		return $this->get_test()->get_unit();
	}
	public function get_course_id()
	{
		return $this->get_test()->get_course_id();
	}
	public function get_course()
	{
		return $this->get_test()->get_course();
	}
	
	private $section_name = null;
	public function get_section_name()
	{
		return $this->section_name;
	}
	
	public function get_owner()
	{
		return $this->get_course()->get_owner();
	}
	
	public function get_instructors()
	{
		return $this->get_course()->get_instructors();
	}
	public function session_user_is_instructor()
	{
		return $this->get_course()->session_user_is_instructor();
	}
	
	public function get_students()
	{
		return $this->get_course()->get_students();
	}
	public function session_user_is_student()
	{
		return $this->get_course()->session_user_is_student();
	}

	private $entries;
	public function get_entries()
	{
		if (!isset($this->entries))
		{
			$this->entries = array();
			
			$mysqli = Connection::get_shared_instance();
			
			$section_entries = "(course_unit_test_section_entries LEFT JOIN user_entries USING (user_entry_id))";
			$language_codes = sprintf("(SELECT entry_id, %s FROM %s) AS reference",
				Dictionary::language_code_columns(),
				Dictionary::join()
			);
			
			$result = $mysqli->query(sprintf("SELECT * FROM $section_entries LEFT JOIN $language_codes USING (entry_id) WHERE section_id = %d",
				$this->get_section_id()
			));
			
			while (($entry_assoc = $result->fetch_assoc()))
			{
				array_push($this->entries, Entry::from_mysql_result_assoc($entry_assoc);
			}
		}
		return $this->entries;
	}
	
	private $timer;
	public function get_timer()
	{
		return $this->timer;
	}
	
	private $message;
	public function get_message()
	{
		return $this->message;
	}
	
	private function __construct($test_id, $unit_id, $test_name = null, $open = null, $close = null, $message = null)
	{
		$this->test_id = intval($test_id, 10);
		$this->unit_id = intval($unit_id, 10);
		$this->test_name = !!$test_name ? $test_name : null;
		$this->timeframe = !!$open && !!$close ? new Timeframe($open, $close) : null;
		$this->message = !!$message ? $message : null;
		
		self::register($this->unit_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"test_id",
			"unit_id",
			"test_name",
			"open",
			"close",
			"message"
		);
		
		if (!self::assoc_contains_keys($result_assoc, $mysql_columns)) return null;
		
		return new Test(
			$result_assoc["test_id"],
			$result_assoc["unit_id"],
			$result_assoc["test_name"],
			$result_assoc["open"],
			$result_assoc["close"],
			$result_assoc["message"]
		);
	}
	
	public function session_user_can_write()
	{
		return $this->session_user_is_instructor() || $this->session_user_is_owner();
	}
	
	public function session_user_can_read()
	{
		return $this->session_user_can_write() || $this->session_user_is_student();
	}
	
	public function session_user_can_execute()
	{
		return (!$this->get_timeframe() || ($this->get_timeframe()->is_current()) && $this->session_user_is_student();
	}
	
	public function delete()
	{
		if (!$this->session_user_is_owner())
		{
			return self::set_error_description("Session user is not course owner.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM course_unit_tests WHERE test_id = %d",
			$this->get_test_id()
		));
		
		return $this;
	}
	
	public function assoc_for_json($privacy = null)
	{
		$omniscience = $this->session_user_is_owner();
		
		if ($omniscience) $privacy = false;
		else if ($privacy === null) $privacy = !$this->session_user_can_read();
		
		return array (
			"sectionId" => $this->get_section_id(),
			"sectionName" => !$privacy ? $this->get_section_name() : null,
			"testId" => $this->get_test_id(),
			"testName" => !$privacy ? $this->get_test_name() : null,
			"unitId" => !$privacy ? $this->get_unit_id() : null,
			"courseId" => !$privacy ? $this->get_course_id() : null,
			"owner" => !$privacy ? $this->get_owner()->assoc_for_json(),
			"timeframe" => !$privacy ? $this->get_timeframe()->assoc_for_json()
		);
	}
}

?>