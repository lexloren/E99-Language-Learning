<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Test extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function insert($unit_id, $name = null, $timeframe = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return static::set_error_description("Session user has not reauthenticated.");
		}
		
		if (!($unit = Unit::select_by_id(($unit_id = intval($unit_id, 10)))))
		{
			return static::set_error_description("Failure to insert test: " . Unit::unset_error_description());
		}
		
		if (!$unit->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit course unit.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$name = $name !== null ? "'" . $mysqli->escape_string($name) . "'" : "NULL";
		$message = $message !== null ? "'" . $mysqli->escape_string($message) . "'" : "NULL";
		$open = !!$timeframe ? $timeframe->get_open() : "NULL";
		$close = !!$timeframe ? $timeframe->get_close() : "NULL";
		
		$mysqli->query("INSERT INTO course_unit_tests (unit_id, name, open, close, message) VALUES ($unit_id, $name, $open, $close, $message)");
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Failed to insert test: " . $mysqli->error);
		}
		
		$unit->uncache_tests();
		
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
	
	private $name = null;
	public function get_test_name()
	{
		return $this->name;
	}
	public function set_test_name($name)
	{
		if (!self::update_this($this, "course_unit_tests", array ("name" => $name), "test_id", $this->get_test_id()))
		{
			return null;
		}
		$this->name = $name;
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

	public function uncache_sections()
	{
		if (isset($this->sections)) unset($this->sections);
	}
	public function uncache_all()
	{
		$this->uncache_sections();
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
	
	private $timeframe = null;
	public function get_timeframe()
	{
		return $this->timeframe;
	}
	public function set_timeframe($timeframe)
	{
		if (!self::update_this(
			$this,
			"course_unit_tests",
			!!$timeframe
				? $timeframe->mysql_assignments()
				: array ("open" => "NULL", "close" => "NULL"),
			"test_id",
			$this->get_test_id(),
			true
		)) return null;
		
		$this->timeframe = $timeframe;
		
		return $this;
	}
	public function set_open($open)
	{
		return $this->set_timeframe(new Timeframe($open, !!$this->get_timeframe() ? $this->get_timeframe()->get_close() : null));
	}
	public function set_close($close)
	{
		return $this->set_timeframe(new Timeframe(!!$this->get_timeframe() ? $this->get_timeframe()->get_open() : null, $close));
	}
	
	//  inherits: protected $message;
	public function set_message($message)
	{
		return parent::set_this_message($this, $message, "course_unit_tests", "test_id", $this->get_test_id());
	}
	
	private function __construct($test_id, $unit_id, $name = null, $open = null, $close = null, $message = null)
	{
		$this->test_id = intval($test_id, 10);
		$this->unit_id = intval($unit_id, 10);
		$this->name = !!$name ? $name : null;
		$this->timeframe = !!$open && !!$close ? new Timeframe($open, $close) : null;
		$this->message = !!$message && strlen($message) > 0 ? $message : null;
		
		self::register($this->test_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"test_id",
			"unit_id",
			"name",
			"open",
			"close",
			"message"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new Test(
				$result_assoc["test_id"],
				$result_assoc["unit_id"],
				$result_assoc["name"],
				$result_assoc["open"],
				$result_assoc["close"],
				$result_assoc["message"]
			)
			: null;
	}
	
	public function session_user_can_execute()
	{
		return $this->session_user_can_read(); // && $this->session_user_unfinished();
	}
	
	public function delete()
	{
		$this->get_unit()->uncache_tests();
		return self::delete_this($this, "course_unit_tests", "test_id", $this->get_test_id());
	}
	
	public function assoc_for_json($privacy = null)
	{
		return $this->privacy_mask(array (
			"testId" => $this->get_test_id(),
			"name" => !$privacy ? $this->get_test_name() : null,
			"unitId" => !$privacy ? $this->get_unit_id() : null,
			"courseId" => !$privacy ? $this->get_course_id() : null,
			"owner" => !$privacy ? $this->get_owner()->assoc_for_json() : null,
			"timeframe" => !$privacy && !!$this->get_timeframe() ? $this->get_timeframe()->assoc_for_json() : null,
			"sectionsCount" => count($this->get_sections()),
			"message" => $this->get_message()
		), array (0 => "testId"), $privacy);
	}
	
	public function detailed_assoc_for_json($privacy = null)
	{
		$assoc = $this->assoc_for_json($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["course"] = $this->get_course()->assoc_for_json($privacy !== null ? $privacy : null);
		$assoc["unit"] = $this->get_unit()->assoc_for_json($privacy !== null ? $privacy : null);
		$assoc["sections"] = $this->session_user_can_execute() ? self::array_for_json($this->get_sections()) : null;
		
		return $this->privacy_mask($assoc, $public_keys, $privacy);
	}
}

?>
