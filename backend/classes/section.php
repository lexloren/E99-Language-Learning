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
		
		if (!($test = Test::select_by_id(($test_id = intval($test_id, 10)))))
		{
			return static::set_error_description("Failure to insert section: " . Test::unset_error_description());
		}
		
		if (!$test->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit course unit test.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$name = $name !== null ? "'" . $mysqli->escape_string($name) . "'" : "NULL";
		$message = $message !== null ? "'" . $mysqli->escape_string($message) . "'" : "NULL";
		$number = count($test->get_sections()) + 1;
		$timer = intval($timer, 10);
		
		$mysqli->query("INSERT INTO course_unit_test_sections (test_id, name, num, timer, message) VALUES ($test_id, $name, $number, $timer, $message)");
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Failed to insert section: " . $mysqli->error . ".");
		}
		
		$test->uncache_sections();
		
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
	
	private $number = null;
	public function get_number()
	{
		return $this->number;
	}
	public function set_number($number)
	{
		if (!$this->session_user_can_write()) return self::set_error_description("Session user cannot edit test section.");
		
		if ($number === null || ($number = intval($number, 10)) < 1) return self::set_error_description("Test section cannot set number to null or nonpositive integer.");
		
		if ($number > ($sections_count = count($this->get_test()->get_sections()))) $number = $sections_count;
		
		if ($number === $this->get_number()) return $this;
			
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("UPDATE course_unit_test_sections SET num = $sections_count + 1 WHERE section_id = %d", $this->get_section_id()));
		
		if ($mysqli->error) return self::set_error_description("Section Modification", "Failed to reorder test sections: " . $mysqli->error . ".");
		
		$mysqli->query(sprintf("UPDATE course_unit_test_sections SET num = num - 1 WHERE test_id = %d AND num > %d ORDER BY num", $this->get_test_id(), $this->get_number()));
		
		if ($mysqli->error) return self::set_error_description("Section Modification", "Failed to reorder test sections: " . $mysqli->error . ".");
		
		$mysqli->query(sprintf("UPDATE course_unit_test_sections SET num = num + 1 WHERE test_id = %d AND num >= %d ORDER BY num DESC", $this->get_test_id(), $number));
		
		if ($mysqli->error) return self::set_error_description("Section Modification", "Failed to reorder test sections: " . $mysqli->error . ".");
		
		if (!self::update_this($this, "course_unit_test_sections", array ("num" => $number), "section_id", $this->get_section_id())) return null;
		
		$this->number = $number;
		
		return $this;
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

	public function uncache_entries()
	{
		if (isset($this->entries)) unset($this->entries);
	}
	public function uncache_all()
	{
		$this->uncache_entries();
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
	
	private function __construct($section_id, $test_id, $number, $name = null, $timer = null, $message = null)
	{
		$this->section_id = intval($section_id, 10);
		$this->test_id = intval($test_id, 10);
		$this->name = !!$name && strlen($name) > 0 ? $name : null;
		$this->number = intval($number, 10);
		$this->timer = intval($timer, 10);
		$this->message = !!$message && strlen($message) > 0 ? $message : null;
		
		self::register($this->section_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"section_id",
			"test_id",
			"num",
			"name",
			"timer",
			"message"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new Section(
				$result_assoc["section_id"],
				$result_assoc["test_id"],
				$result_assoc["num"],
				$result_assoc["name"],
				$result_assoc["timer"],
				$result_assoc["message"]
			)
			: null;
	}
	
	public function session_user_can_execute()
	{
		//  Need to add another check whether this section is the next section available in the test
		return $this->get_test()->session_user_can_execute(); // && $this->session_user_unfinished();
	}
	
	public function delete()
	{
		if (($return = self::delete_this($this, "course_unit_test_sections", "section_id", $this->get_section_id())))
		{
			$this->get_test()->uncache_sections();
			
			$mysqli = Connection::get_shared_instance();
			
			$mysqli->query(sprintf("UPDATE course_unit_test_sections SET num = num - 1 WHERE test_id = %d AND num >= %d ORDER BY num", $this->get_test_id(), $this->get_number()));
			
			if ($mysqli->error) return self::set_error_description("Unit Deletion", "Failed to reorder test sections: " . $mysqli->error . ".");
		}
		return $return;
	}
	
	public function assoc_for_json($privacy = null)
	{
		return $this->privacy_mask(array (
			"sectionId" => $this->get_section_id(),
			"number" => $this->get_number(),
			"name" => $this->get_section_name(),
			"testId" => $this->get_test_id(),
			"unitId" => $this->get_unit_id(),
			"courseId" => $this->get_course_id(),
			"owner" => $this->get_owner()->assoc_for_json(),
			"timer" => $this->get_timer(),
			"entriesCount" => count($this->get_entries()),
			"message" => $this->get_message()
		), array (0 => "sectionId"), $privacy);
	}
	
	public function detailed_assoc_for_json($privacy = null)
	{
		$assoc = $this->assoc_for_json($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["course"] = $this->get_course()->assoc_for_json($privacy !== null ? $privacy : null);
		$assoc["unit"] = $this->get_unit()->assoc_for_json($privacy !== null ? $privacy : null);
		$assoc["test"] = $this->get_test()->assoc_for_json($privacy !== null ? $privacy : null);
		$assoc["entries"] = $this->session_user_can_execute() ? self::array_for_json($this->get_entries()) : null;
		
		return $this->privacy_mask($assoc, $public_keys, $privacy);
	}
}

?>