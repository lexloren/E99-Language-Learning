<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Unit extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	
	private static $units_by_id = array ();
	
	public static function insert($course_id, $unit_name = null)
	{
		if (!Session::get()->get_user())
		{
			return Unit::set_error_description("Session user has not reauthenticated.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		if ($unit_name !== null) $unit_name = $mysqli->escape_string($unit_name);
		$course_id = intval($course_id, 10);
		$course = Course::select_by_id($course_id);
		
		if (!$course) return Unit::set_error_description(Course::get_error_description());
		
		if (!$course->session_user_is_instructor())
		{
			return Unit::set_error_description("Session user is not instructor of course.");
		}
		
		$unit_number = count($course->get_units()) + 1;
		
		$mysqli->query(sprintf("INSERT INTO course_units (course_id, unit_name, unit_numbr) VALUES (%d, %s, %d)",
			$course_id,
			!!$unit_name && strlen($unit_name) > 0 ? "'$unit_name'" : "NULL",
			$unit_number
		));
		
		return self::select_by_id($mysqli->insert_id);
	}
	
	public static function select_by_id($unit_id)
	{
		$unit_id = intval($unit_id, 10);
		
		if (isset(self::$units_by_id[$unit_id])) return self::$units_by_id[$unit_id];
		
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query("SELECT * FROM course_units WHERE unit_id = $unit_id");
		
		if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
		{
			return Unit::from_mysql_result_assoc($result_assoc);
		}
		
		return Unit::set_error_description("No unit matches unit_id = $unit_id.");
	}
	
	/***    INSTANCE    ***/

	private $course_id = null;
	public function get_course_id()
	{
		return $this->course_id;
	}
	public function get_course()
	{
		return Course::select_by_id($this->get_course_id());
	}
	
	private $unit_id = null;
	public function get_unit_id()
	{
		return $this->unit_id;
	}
	
	private $unit_number = null;
	public function get_unit_number()
	{
		return $this->unit_number;
	}
	public function set_unit_number($unit_number)
	{
		//  Verify that $unit_number <= count of units in the course
		//  Set this unit's number to $unit_number, pushing all subsequent units down by one
	}
	
	private $unit_name = null;
	public function get_unit_name()
	{
		return $this->unit_name;
	}
	
	private $tests;
	public function get_tests()
	{
		if (!isset($this->tests))
		{
			$this->tests = array ();
			
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query(sprintf("SELECT * FROM course_unit_tests WHERE unit_id = %d",
				$this->get_unit_id()
			));
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				array_push($this->instructors, Test::from_mysql_result_assoc($result_assoc));
			}
		}
		return $this->tests;
	}
	
	private $lists;
	public function get_lists()
	{
		if (!isset($this->lists))
		{
			$this->lists = array ();
			
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query(sprintf("SELECT * FROM course_unit_lists WHERE unit_id = %d",
				$this->get_unit_id()
			));
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				array_push($this->students, EntryList::from_mysql_result_assoc($result_assoc));
			}
		}
		
		return $this->lists;
	}
	
	private function __construct($unit_id, $course_id, $unit_number, $unit_name = null)
	{
		$this->unit_id = intval($unit_id, 10);
		$this->course_id = intval($course_id, 10);
		$this->unit_number = intval($unit_number, 10);
		$this->unit_name = !!$unit_name && strlen($unit_name) > 0 ? $unit_name : null;
		
		Unit::$units_by_id[$this->unit_id] = $this;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		if (!$result_assoc)
		{
			return Unit::set_error_description("Invalid result_assoc.");
		}
		
		return new Unit(
			$result_assoc["unit_id"],
			$result_assoc["course_id"],
			$result_assoc["unit_numbr"],
			!!$result_assoc["unit_name"] && strlen($result_assoc["unit_name"]) > 0 ? $result_assoc["unit_name"] : null
		);
	}
	
	public function delete()
	{
		if (!$this->session_user_is_instructor())
		{
			return Unit::set_error_description("Session user is not instructor of course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM course_units WHERE unit_id = %d",
			$this->get_unit_id()
		));
		
		return $this;
	}
	
	public function session_user_is_instructor()
	{
		return $this->get_course()->session_user_is_instructor();
	}
	
	public function session_user_is_student()
	{
		return $this->get_course()->session_user_is_student();
	}
	
	public function lists_add($list)
	{
		if (!$this->session_user_is_instructor())
		{
			return Unit::set_error_description("Session user is not instructor of course.");
		}
		
		if (!$list->get_owner()->equals(Session::get()->get_user()))
		{
			return Unit::set_error_description("Session user is not owner of list.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT IGNORE INTO course_unit_lists (unit_id, list_id) VALUES (%d, $list_id)",
			$this->get_unit_id()
		));
		
		array_push($this->get_lists(), $list);
		
		return $this;
	}
	
	public function lists_remove($list)
	{
		if (!$this->session_user_is_instructor())
		{
			return Unit::set_error_description("Session user is not instructor of course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM course_unit_lists WHERE unit_id = %d AND list_id = $list_id",
			$this->get_unit_id()
		));
		
		unset($this->lists);
		
		return $this;
	}
	
	public function assoc_for_json($privacy = null)
	{
		$omniscience = $this->session_user_is_instructor();
		
		if ($omniscience) $privacy = false;
		else if ($privacy === null) $privacy = !$this->session_user_is_student();
		
		if (!$privacy)
		{
			$lists_returnable = array ();
			foreach ($this->get_lists() as $list)
			{
				array_push($lists_returnable, $list->assoc_for_json());
			}
			
			$tests_returnable = array ();
			foreach ($this->get_tests() as $test)
			{
				array_push($tests_returnable, $test->assoc_for_json(!$omniscience));
			}
		}
		
		return array (
			"unitId" => $this->get_unit_id(),
			"unitName" => !$privacy ? $this->get_unit_name() : null,
			"timeframe" => null // Not yet implemented
		);
	}
}

?>