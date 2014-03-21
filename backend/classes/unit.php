<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Unit extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	
	private static $units_by_id = array ();
	
	public static function insert($course_id, $unit_name = null)
	{
		if (!Session::get_user())
		{
			return Unit::set_error_description("Session user has not reauthenticated.");
		}
		
		$course_id = intval($course_id, 10);
		$course = Course::select($course_id);
		
		if (!$course->session_user_is_instructor())
		{
			return Unit::set_error_description("Session user is not instructor of course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO course_units (course_id, unit_name) VALUES ($course_id, %s)",
			!!$unit_name && strlen($unit_name) > 0 ? "'" . $mysqli->escape_string($unit_name) . "'" : "NULL"
		));
		
		return self::select($mysqli->insert_id);
	}
	
	public static function select($unit_id)
	{
		$unit_id = intval($unit_id, 10);
		
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
		return Course::select($this->get_course_id());
	}
	
	private $unit_id = null;
	public function get_course_id()
	{
		return $this->unit_id;
	}
	
	private $unit_name = null;
	public function get_unit_name()
	{
		return $this->unit_name;
	}
	
	private $tests = null;
	public function get_tests()
	{
		if (!$this->tests)
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
	
	private $lists = null;
	public function get_lists()
	{
		if (!$this->lists)
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
	
	private function __construct($unit_id, $course_id, $unit_name = null)
	{
		$this->unit_id = intval($unit_id, 10);
		$this->course_id = intval($course_id, 10);
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
	
	public function add_list($list_id)
	{
		$list_id = intval($list_id, 10);
		
		if (!$this->session_user_is_instructor())
		{
			return Unit::set_error_description("Session user is not instructor of course.");
		}
		
		$list = EntryList::select($list_id);
		
		if (!$list->get_owner()->equals(Session::get_user()))
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
	
	public function remove_list($list_id)
	{
		$list_id = intval($list_id, 10);
		
		if (!$this->session_user_is_instructor())
		{
			return Unit::set_error_description("Session user is not instructor of course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM course_unit_lists (unit_id, list_id) VALUES (%d, $list_id)",
			$this->get_unit_id()
		));
		
		$this->lists = array_diff($this->get_lists(), array (EntryList::select($list_id)));
		
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
			"unitName" => !$privacy ? $this->get_unit_name() : null
		);
	}
}

?>