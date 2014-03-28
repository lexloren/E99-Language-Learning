<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Section extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
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
		
		$mysqli->query("INSERT INTO course_unit_test_sections (test_id, section_name, section_num, timer, message) VALUES ($test_id, $section_name, $section_number, $timer, $message)");
		
		if (!!$mysqli->error)
		{
			return self::set_error_description("Failed to insert section: " . $mysqli->error);
		}
		
		return self::select_by_id($mysqli->insert_id);
	}
	
	public static function select_by_id($section_id)
	{
		return parent::select("course_unit_test_sections", "section_id", $section_id);
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
	
	public function get_unit()
	{
		return $this->get_test()->get_unit();
	}
	public function get_unit_id()
	{
		return $this->get_unit()->get_unit_id();
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
	public function set_section_name($section_name)
	{
		if (!self::update_this($this, "course_unit_test_sections", array ("section_name", $section_name), "section_id", $this->get_section_id()))
		{
			return null;
		}
		$this->section_name = $section_name;
		return $this;
	}

	private $entries;
	public function get_entries()
	{
		$section_entries = "(course_unit_test_section_entries LEFT JOIN user_entries USING (user_entry_id))";
		$language_codes = sprintf("(SELECT entry_id, %s FROM %s) AS reference",
			Dictionary::language_code_columns(),
			Dictionary::join()
		);
		$table = "$section_entries LEFT JOIN $language_codes USING (entry_id)";
		return self::get_cached_collection($this->entries, "Entry", $table, "section_id", $this->get_section_id());
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
		return false; //(!$this->get_timeframe() || ($this->get_timeframe()->is_current()) && $this->session_user_is_student();
	}
	
	public function delete()
	{
		return self::delete_this($this, "course_unit_test_sections", "section_id", $this->get_section_id());
	}
	
	public function assoc_for_json($privacy = null)
	{
		$omniscience = $this->session_user_is_owner();
		
		if ($omniscience) $privacy = false;
		else if ($privacy === null) $privacy = !$this->session_user_can_execute();
		
		return array (
			"sectionId" => $this->get_section_id(),
			"sectionName" => !$privacy ? $this->get_section_name() : null,
			"testId" => !$privacy ? $this->get_test_id() : null,
			"testName" => !$privacy ? $this->get_test_name() : null,
			"unitId" => !$privacy ? $this->get_unit_id() : null,
			"unitName" => !$privacy ? $this->get_unit()->get_unit_name() : null,
			"courseId" => !$privacy ? $this->get_course_id() : null,
			"courseName" => !$privacy ? $this->get_course()->get_course_name() : null,
			"owner" => !$privacy ? $this->get_owner()->assoc_for_json() : null,
			"timer" => !$privacy ? $this->get_timer() : null,
			"message" => !$privacy ? $this->get_message() : null
		);
	}
}

?>