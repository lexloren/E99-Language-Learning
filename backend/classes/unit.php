<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Unit extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function insert($course_id, $name = null, $timeframe = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return static::set_error_description("Session user has not reauthenticated.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		if ($name !== null) $name = $mysqli->escape_string($name);
		$course = Course::select_by_id(($course_id = intval($course_id, 10)));
		
		if (!$course) return static::set_error_description("Failed to insert unit: " . Course::unset_error_description());
		
		if (!$course->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit course.");
		}
		
		$unit_number = count($course->get_units()) + 1;
		$open = !!$timeframe ? "FROM_UNIXTIME(" . $timeframe->get_open() . ")" : "NULL";
		$close = !!$timeframe ? "FROM_UNIXTIME(" . $timeframe->get_close() . ")" : "NULL";
		$message = $message !== null ? "'" . $mysqli->escape_string($message) . "'" : "NULL";
		
		$mysqli->query(sprintf("INSERT INTO course_units (course_id, name, num, open, close, message) VALUES (%d, %s, %d, %s, %s, %s)",
			$course_id,
			!!$name && strlen($name) > 0 ? "'$name'" : "NULL",
			$unit_number,
			$open, $close,
			$message
		));
		
		if (!!$mysqli->error) return static::set_error_description("Failed to insert unit: " . $mysqli->error);
		
		$course->uncache_all();
		
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
	
	private $name = null;
	public function get_unit_name()
	{
		return $this->name;
	}
	public function set_unit_name($name)
	{
		if (!self::update_this($this, "course_units", array ("name" => $name), "unit_id", $this->get_unit_id()))
		{
			return null;
		}
		$this->name = $name;
		return $this;
	}
	
	private $timeframe;
	public function get_timeframe()
	{
		return $this->timeframe;
	}
	public function set_timeframe($timeframe)
	{
		if (!self::update_this(
			$this,
			"course_units",
			!!$timeframe
				? $timeframe->mysql_assignments()
				: array ("open" => "NULL", "close" => "NULL"),
			"unit_id",
			$this->get_unit_id(),
			true
		)) return null;
		
		$this->timeframe = $timeframe;
		
		return $this;
	}
	public function set_open($open)
	{
		return $this->set_timeframe(new Timeframe($open, $this->get_timeframe()->get_close()));
	}
	public function set_close($close)
	{
		return $this->set_timeframe(new Timeframe($this->get_timeframe()->get_open(), $close));
	}
	
	//  inherits: protected $message;
	public function set_message($message)
	{
		return parent::set_this_message($this, $message, "course_units", "unit_id", $this->get_unit_id());
	}

	protected function uncache_all()
	{
		if (isset($this->tests)) unset($this->tests);
		if (isset($this->lists)) unset($this->lists);
	}
	
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
	
	private function __construct($unit_id, $course_id, $unit_number, $name = null, $open = null, $close = null, $message = null)
	{
		$this->unit_id = intval($unit_id, 10);
		$this->course_id = intval($course_id, 10);
		$this->unit_number = intval($unit_number, 10);
		$this->name = !!$name && strlen($name) > 0 ? $name : null;
		$this->timeframe = !!$open && !!$close ? new Timeframe($open, $close) : null;
		$this->message = !!$message && strlen($message) > 0 ? $message : null;
		
		self::register($this->unit_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"unit_id",
			"course_id",
			"num",
			"name",
			"open",
			"close",
			"message"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new Unit(
				$result_assoc["unit_id"],
				$result_assoc["course_id"],
				$result_assoc["num"],
				$result_assoc["name"],
				$result_assoc["open"],
				$result_assoc["close"],
				$result_assoc["message"]
			)
			: null;
	}
	
	public function delete()
	{
		$this->get_course()->uncache_all();
		return self::delete_this($this, "course_units", "unit_id", $this->get_unit_id());
	}
	
	public function lists_add($list)
	{
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit course.");
		}
		
		if (!($list = $list->copy_for_user($this->get_owner())))
		{
			return static::set_error_description("Failed to add list: " . EntryList::unset_error_description());
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT IGNORE INTO course_unit_lists (unit_id, list_id) VALUES (%d, %d)",
			$this->get_unit_id(),
			$list->get_list_id()
		));
		
		$lists = $this->get_lists();
		array_push($lists, $list);
		
		$list->uncache_all();
		
		return $this;
	}
	
	public function lists_remove($list)
	{
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM course_unit_lists WHERE unit_id = %d AND list_id = %d",
			$this->get_unit_id(),
			$list->get_list_id()
		));
		
		unset($this->lists);
		
		$list->uncache_all();
		
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
			"timeframe" => !$privacy && !!$this->get_timeframe() ? $this->get_timeframe()->assoc_for_json() : null
		);
	}
	
	public function detailed_assoc_for_json($privacy = null)
	{
		$assoc = $this->assoc_for_json($privacy);
		
		$assoc["course"] = $this->get_course()->assoc_for_json($privacy);
		$assoc["lists"] = self::array_for_json($this->get_lists());
		$assoc["tests"] = self::array_for_json($this->get_tests());
		
		return $assoc;
	}
}

?>
