<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Unit extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function insert($course_id, $unit_name = null, $timeframe = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return self::set_error_description("Session user has not reauthenticated.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		if ($unit_name !== null) $unit_name = $mysqli->escape_string($unit_name);
		$course_id = intval($course_id, 10);
		$course = Course::select_by_id($course_id);
		
		if (!$course) return self::set_error_description(Course::get_error_description());
		
		if (!$course->session_user_is_instructor())
		{
			return self::set_error_description("Session user is not instructor of course.");
		}
		
		$unit_number = count($course->get_units()) + 1;
		
		$mysqli->query(sprintf("INSERT INTO course_units (course_id, unit_name, unit_num) VALUES (%d, %s, %d)",
			$course_id,
			!!$unit_name && strlen($unit_name) > 0 ? "'$unit_name'" : "NULL",
			$unit_number
		));
		
		if (!!$mysqli->error) return self::set_error_description("Failed to insert unit: " . $mysqli->error);
		
		return self::select_by_id($mysqli->insert_id);
	}
	
	public static function select_by_id($unit_id)
	{
		return parent::select("course_units", "unit_id", $unit_id);
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
		return null;
		//  Verify that $unit_number <= count of units in the course
		//  Set this unit's number to $unit_number, pushing all subsequent units down by one
	}
	
	private $unit_name = null;
	public function get_unit_name()
	{
		return $this->unit_name;
	}
	public function set_unit_name($unit_name)
	{
		if (!self::update_this($this, "course_units", array ("unit_name", $unit_name), "unit_id", $this->get_unit_id()))
		{
			return null;
		}
		$this->unit_name = $unit_name;
		return $this;
	}
	
	//  $message
	
	private $tests;
	public function get_tests()
	{
		return self::get_cached_collection($this->tests, "Test", "course_unit_tests", "unit_id", $this->get_unit_id());
	}
	
	private $lists;
	public function get_lists()
	{
		$table = "course_unit_lists LEFT JOIN lists USING (list_id)";
		return self::get_cached_collection($this->lists, "EntryList", $table, "unit_id", $this->get_unit_id());
	}
	
	private function __construct($unit_id, $course_id, $unit_number, $unit_name = null)
	{
		$this->unit_id = intval($unit_id, 10);
		$this->course_id = intval($course_id, 10);
		$this->unit_number = intval($unit_number, 10);
		$this->unit_name = !!$unit_name && strlen($unit_name) > 0 ? $unit_name : null;
		
		self::register($this->unit_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"unit_id",
			"course_id",
			"unit_num",
			"unit_name"
		);
		
		if (!self::assoc_contains_keys($result_assoc, $mysql_columns)) return null;
		
		return new Unit(
			$result_assoc["unit_id"],
			$result_assoc["course_id"],
			$result_assoc["unit_num"],
			!!$result_assoc["unit_name"] && strlen($result_assoc["unit_name"]) > 0 ? $result_assoc["unit_name"] : null
		);
	}
	
	public function delete()
	{
		return self::delete_this($this, "course_units", "unit_id", $this->get_unit_id());
	}
	
	public function lists_add($list)
	{
		if (!$this->session_user_is_instructor())
		{
			return self::set_error_description("Session user is not instructor of course.");
		}
		
		if (!$list->session_user_is_owner())
		{
			return self::set_error_description("Session user is not owner of list.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT IGNORE INTO course_unit_lists (unit_id, list_id) VALUES (%d, %d)",
			$this->get_unit_id(),
			$list->get_list_id()
		));
		
		array_push($this->get_lists(), $list);
		
		return $this;
	}
	
	public function lists_remove($list)
	{
		if (!$this->session_user_is_instructor())
		{
			return self::set_error_description("Session user is not instructor of course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM course_unit_lists WHERE unit_id = %d AND list_id = %d",
			$this->get_unit_id(),
			$list->get_list_id()
		));
		
		unset($this->lists);
		
		return $this;
	}
	
	public function assoc_for_json($privacy = null)
	{
		$omniscience = $this->session_user_is_instructor();
		
		if ($omniscience) $privacy = false;
		else if ($privacy === null) $privacy = !$this->session_user_is_student();
		
		return array (
			"unitId" => $this->get_unit_id(),
			"unitName" => !$privacy ? $this->get_unit_name() : null,
			"courseId" => $this->get_course_id(),
			"owner" => $this->get_owner()->assoc_for_json(),
			"timeframe" => null // Not yet implemented
		);
	}
}

?>