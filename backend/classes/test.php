<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Test extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function insert($unit_id, $test_name = null, $timeframe = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return static::set_error_description("Session user has not reauthenticated.");
		}
		
		$unit = Unit::select_by_id(($unit_id = intval($unit_id, 10)));
		
		if (!$unit->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit course unit.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$test_name = $test_name !== null ? "'" . $mysqli->escape_string($test_name) . "'" : "NULL";
		$message = $message !== null ? "'" . $mysqli->escape_string($message) . "'" : "NULL";
		$open = !!$timeframe ? "FROM_UNIXTIME(" . $timeframe->get_open() . ")" : "NULL";
		$close = !!$timeframe ? "FROM_UNIXTIME(" . $timeframe->get_close() . ")" : "NULL";
		
		$mysqli->query("INSERT INTO course_unit_tests (unit_id, test_name, open, close, message) VALUES ($unit_id, $test_name, $open, $close, $message)");
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Failed to insert test: " . $mysqli->error);
		}
		
		return self::select_by_id($mysqli->insert_id);
	}
	
	public static function select_by_id($test_id)
	{
		return parent::select("course_unit_tests", "test_id", $test_id);
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
	public function set_test_name($test_name)
	{
		if (!self::update_this($this, "course_unit_tests", array ("test_name" => $test_name), "test_id", $this->get_test_id()))
		{
			return null;
		}
		$this->test_name = $test_name;
		return $this;
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
	
	public function get_course()
	{
		return $this->get_unit()->get_course();
	}
	
	private $sections;
	public function get_sections()
	{
		return self::get_cached_collection($this->sections, "Section", "course_unit_test_sections", "test_id", $this->get_test_id());
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
	public function set_timeframe($timeframe)
	{
		if (!self::update_this(
			$this,
			"course_unit_tests",
			array ("open" => $timeframe->get_open(), "close" => $timeframe->get_close()),
			"test_id",
			$this->get_test_id()
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
		return parent::set_this_message($this, $message, "course_unit_tests", "test_id", $this->get_test_id());
	}
	
	private function __construct($test_id, $unit_id, $test_name = null, $open = null, $close = null, $message = null)
	{
		$this->test_id = intval($test_id, 10);
		$this->unit_id = intval($unit_id, 10);
		$this->test_name = !!$test_name ? $test_name : null;
		$this->timeframe = !!$open && !!$close ? new Timeframe($open, $close) : null;
		$this->message = !!$message && strlen($message) > 0 ? $message : null;
		
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
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new Test(
				$result_assoc["test_id"],
				$result_assoc["unit_id"],
				$result_assoc["test_name"],
				$result_assoc["open"],
				$result_assoc["close"],
				$result_assoc["message"]
			)
			: null;
	}
	
	public function session_user_can_execute()
	{
		return (!$this->get_timeframe() || $this->get_timeframe()->is_current()) && $this->session_user_is_student();
	}
	
	public function delete()
	{
		return self::delete_this($this, "course_unit_tests", "test_id", $this->get_test_id());
	}
	
	public function assoc_for_json($privacy = null)
	{
		$omniscience = $this->session_user_is_owner();
		
		if ($omniscience) $privacy = false;
		else if ($privacy === null) $privacy = !$this->session_user_can_read();
		
		return array (
			"testId" => $this->get_test_id(),
			"testName" => !$privacy ? $this->get_test_name() : null,
			"unitId" => !$privacy ? $this->get_unit_id() : null,
			"unitName" => !$privacy ? $this->get_unit()->get_unit_name() : null,
			"courseId" => !$privacy ? $this->get_course_id() : null,
			"courseName" => !$privacy ? $this->get_course()->get_course_name() : null,
			"owner" => !$privacy ? $this->get_owner()->assoc_for_json() : null,
			"timeframe" => !$privacy && !!$this->get_timeframe() ? $this->get_timeframe()->assoc_for_json() : null
		);
	}
}

?>