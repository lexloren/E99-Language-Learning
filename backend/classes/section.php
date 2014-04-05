<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Section extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function insert($test_id, $name = null, $timer = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return static::set_error_description("Session user has not reauthenticated.");
		}
		
		$test = Test::select_by_id(($test_id = intval($test_id, 10)));
		
		if (!$test->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit course unit test.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$name = $name !== null ? "'" . $mysqli->escape_string($name) . "'" : "NULL";
		$message = $message !== null ? "'" . $mysqli->escape_string($message) . "'" : "NULL";
		$section_number = count($test->get_sections()) + 1;
		$timer = intval($timer, 10);
		
		$mysqli->query("INSERT INTO course_unit_test_sections (test_id, name, num, timer, message) VALUES ($test_id, $name, $section_number, $timer, $message)");
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Failed to insert section: " . $mysqli->error);
		}
		
		$test->uncache_all();
		
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
		return $this->get_test()->get_unit_id();
	}
	public function get_course()
	{
		return $this->get_test()->get_course();
	}
	public function get_course_id()
	{
		return $this->get_test()->get_course_id();
	}
	
	private $name = null;
	public function get_section_name()
	{
		return $this->name;
	}
	public function set_section_name($name)
	{
		if (!self::update_this($this, "course_unit_test_sections", array ("name" => $name), "section_id", $this->get_section_id()))
		{
			return null;
		}
		$this->name = $name;
		return $this;
	}

	protected function uncache_all()
	{
		if (isset($this->entries)) unset($this->entries);
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
		return self::get_cached_collection($this->entries, "UserEntry", $table, "section_id", $this->get_section_id());
	}
	
	private $timer;
	public function get_timer()
	{
		return $this->timer;
	}
	
	//  inherits: protected $message;
	public function set_message($message)
	{
		return parent::set_this_message($this, $message, "course_unit_test_sections", "section_id", $this->get_section_id());
	}
	
	private function __construct($section_id, $test_id, $name = null, $timer = null, $message = null)
	{
		$this->section_id = intval($section_id, 10);
		$this->test_id = intval($test_id, 10);
		$this->name = !!$name && strlen($name) > 0 ? $name : null;
		$this->timer = intval($timer, 10);
		$this->message = !!$message && strlen($message) > 0 ? $message : null;
		
		self::register($this->section_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"section_id",
			"test_id",
			"name",
			"timer",
			"message"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new Section(
				$result_assoc["section_id"],
				$result_assoc["test_id"],
				$result_assoc["name"],
				$result_assoc["timer"],
				$result_assoc["message"]
			)
			: null;
	}
	
	public function session_user_can_execute()
	{
		//  Need to add another check whether this section is the next section available in the test
		return $this->get_test()->session_user_can_execute();
	}
	
	public function delete()
	{
		$this->get_test()->uncache_all();
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
			"testName" => !$privacy ? $this->get_test()->get_test_name() : null,
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