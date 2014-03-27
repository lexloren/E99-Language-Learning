<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Test extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	
	public static function insert($unit_id, $test_name = null, $timeframe = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return self::set_error_description("Session user has not reauthenticated.");
		}
		
		$unit = Unit::select_by_id(($unit_id = intval($unit_id, 10)));
		
		if (!$unit->session_user_is_instructor())
		{
			return self::set_error_description("Session user is not course instructor.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$test_name = $test_name !== null ? "'" . $mysqli->escape_string($test_name) . "'" : "NULL";
		$message = $message !== null ? "'" . $mysqli->escape_string($message) . "'" : "NULL";
		$open = $timeframe->get_open();
		$close = $timeframe->get_close();
		
		$mysqli->query("INSERT INTO course_unit_tests (test_name, open, close, message) VALUES ($test_name, FROM_UNIXTIME($open), FROM_UNIXTIME($close), $message)");
		
		if ($mysqli->error)
		{
			return self::set_error_description("Failed to insert test: " . $mysqli->error);
		}
		
		if (!($test = self::select_by_id($mysqli->insert_id)))
		{
			return null;
		}
		
		return $test;
	}
	
	public static function select_by_id($test_id)
	{
		return self::select_by_id("tests", "test_id", $test_id);
	}
	
	/***    INSTANCE    ***/

	private $test_id = null;
	public function get_test_id()
	{
		return $this->test_id;
	}
	
	private $test_name = null;
	public function get_test_name()
	{
		return $this->test_name;
	}
	
	private $unit_id = null;
	public function get_unit_id()
	{
		return $this->unit_id;
	}
	public function get_unit()
	{
		return Unit::select_by_id($this->get_unit_id());
	}
	public function get_course_id()
	{
		return $this->get_unit()->get_course_id();
	}
	public function get_course()
	{
		return $this->get_unit()->get_course();
	}
	
	public function get_owner()
	{
		return User::select_by_id($this->get_course()->get_owner());
	}
	public function session_user_is_owner()
	{
		return !!Session::get() && !!Session::get()->get_user()
			&& Session::get()->get_user()->equals($this->get_owner());
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
	
	private $sections;
	public function get_sections()
	{
		if (!isset($this->sections))
		{
			$this->sections = array();
			
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT * FROM course_unit_test_sections WHERE test_id = %d",
				$this->get_test_id()
			));

			while (($section_assoc = $result->fetch_assoc()))
			{
				if (!($section = Section::from_mysql_result_assoc($section_assoc)))
				{
					return self::set_error_description("Failed to get sections: " . Section::get_error_description());
				}
				array_push($this->sections, $section);
			}
		}
		
		return $this->sections;
	}
	public function get_sections_by_number()
	{
		$sections_by_number = array ();
		foreach ($this->get_sections() as $section)
		{
			$sections_by_number[$section->get_section_number()] = $section;
		}
		return $sections_by_number;
	}
	
	private $timeframe;
	public function get_timeframe()
	{
		return $this->timeframe;
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
		
		self::register($this->test_id, $this);
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
		$omniscience = $this->get_owner()->equals(Session::get()->get_user());
		
		if ($omniscience) $privacy = false;
		else if ($privacy === null) $privacy = !$this->session_user_can_read();
		
		return array (
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