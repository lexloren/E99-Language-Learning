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
			return static::set_error_description("Failed to insert test: " . Unit::unset_error_description());
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
			return static::set_error_description("Failed to insert test: " . $mysqli->error . ".");
		}
		
		$unit->uncache_tests();
		
		return self::select_by_id($mysqli->insert_id);
	}
	
	public static function select_by_id($test_id)
	{
		return parent::select("course_unit_tests", "test_id", $test_id);
	}
	
	public static function select_by_test_entry_id($test_entry_id)
	{
		return parent::select("course_unit_tests LEFT JOIN course_unit_test_entries USING (test_id)", "test_entry_id", $test_entry_id);
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
	
	public function uncache_all()
	{
		$this->uncache_entries();
		$this->uncache_sittings();
	}
	
	private $entries;
	public function get_entries()
	{
		$user_entries = "(course_unit_test_entries LEFT JOIN user_entries USING (user_entry_id))";
		$language_codes = sprintf("(SELECT entry_id, %s FROM %s) AS reference",
			Dictionary::language_code_columns(),
			Dictionary::join()
		);
		$table = "$user_entries LEFT JOIN $language_codes USING (entry_id)";
		return self::get_cached_collection($this->entries, "UserEntry", $table, "test_id", $this->get_test_id(), "*", "ORDER BY num");
	}
	public function uncache_entries()
	{
		if (isset($this->entries)) unset($this->entries);
	}
	public function set_entry_number($entry, $number)
	{
		if (!$this->session_user_can_write()) return self::set_error_description("Session user cannot edit test.");
		
		if ($number === null || ($number = intval($number, 10)) < 1) return self::set_error_description("Test cannot set entry number to null or nonpositive integer.");
		
		$entry = $entry->copy_for_user($this->get_owner());
		
		if ($number > ($entries_count = count($this->get_entries()))) $number = $entries_count;
		
		if ($number === ($number_formerly = array_search($entry, $this->get_entries()) + 1)) return $this;
		
		if ($number_formerly < 1) return self::set_error_description("Test cannot set entry number for entry not already in test.");
			
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = $entries_count + 1 WHERE test_id = %d AND user_entry_id = %d", $this->get_test_id(), $entry->get_user_entry_id()));
		
		if ($mysqli->error) return self::set_error_description("Test Modification", "Failed to reorder test entries: " . $mysqli->error . ".");
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = num - 1 WHERE test_id = %d AND num > %d ORDER BY num", $this->get_test_id(), $number_formerly));
		
		if ($mysqli->error) return self::set_error_description("Test Modification", "Failed to reorder test entries: " . $mysqli->error . ".");
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = num + 1 WHERE test_id = %d AND num >= %d ORDER BY num DESC", $this->get_test_id(), $number));
		
		if ($mysqli->error) return self::set_error_description("Test Modification", "Failed to reorder test entries: " . $mysqli->error . ".");
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = $number WHERE test_id = %d AND user_entry_id = %d", $this->get_test_id(), $entry->get_user_entry_id()));
		
		if ($mysqli->error) return self::set_error_description("Test Modification", "Failed to reorder test entries: " . $mysqli->error . ".");
		
		$this->uncache_entries();
		
		return $this;
	}
	
	public function entries_add($entry, $mode = 1)
	{
		if (!$entry)
		{
			return static::set_error_description("Test cannot add null entry.");
		}
		
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit test.");
		}
		
		//  Insert into user_entries from dictionary, if necessary
		$entry = $entry->copy_for_user($this->get_owner());
		
		if (!$entry)
		{
			return static::set_error_description("Test failed to add entry: " . Entry::unset_error_description());
		}
		
		$mode = $mode === null ? "NULL" : (!!$mode ? "1" : "0");
		
		$mysqli = Connection::get_shared_instance();
		
		//  Insert into list_entries for $this->list_id and $entry->entry_id
		//      If this entry already exists in the list, then ignore the error
		$mysqli->query(sprintf("INSERT INTO course_unit_test_entries (test_id, user_entry_id, num, mode) VALUES (%d, %d, %d, %s)",
			$this->get_test_id(),
			$entry->get_user_entry_id(),
			count($this->get_entries()) + 1,
			$mode
		));
		
		if (!!$mysqli->error)
		{
			return self::set_error_description("Test failed to add entry: " . $mysqli->error . ".");
		}
		
		array_push($this->entries, $entry);
		
		$entry_languages = $entry->get_languages();
		$entry_words = $entry->get_words();
		$entry_pronunciations = $entry->get_pronunciations();
		
		$test_entry_id = $mysqli->insert_id;
		
		$mysqli->query(sprintf("INSERT INTO course_unit_test_entry_patterns (test_entry_id, prompt, word_0, word_1, word_1_pronun) VALUES (%d, 1, '%s', '%s', '%s')",
			$test_entry_id,
			$mysqli->escape_string($entry_words[$entry_languages[0]]),
			$mysqli->escape_string($entry_words[$entry_languages[1]]),
			$mysqli->escape_string($entry_pronunciations[$entry_languages[1]])
		));
		
		if (!!$mysqli->error)
		{
			return self::set_error_description("Test failed to add entry pattern: " . $mysqli->error . ".");
		}
		
		return $this;
	}
	
	public function entries_add_from_list($list, $mode = 1)
	{
		foreach ($list->get_entries() as $entry)
		{
			if (!$this->entries_add($entry, $mode))
			{
				return null;
			}
		}
		return $this;
	}
	
	public function entries_remove($entry)
	{
		if (!$entry)
		{
			return static::set_error_description("Test cannot remove null entry.");
		}
		
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit test.");
		}
		
		$entry = $entry->copy_for_user($this->get_owner());
		
		$number = array_search($entry, $this->get_entries()) + 1;
		
		if ($number < 1) return self::set_error_description("Test cannot remove entry not already in test.");
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM course_unit_test_entries WHERE test_id = %d AND user_entry_id = %d LIMIT 1",
			$this->get_test_id(),
			$entry->get_user_entry_id()
		));
		
		if (!!$mysqli->error)
		{
			return self::set_error_description("Test failed to remove entry: " . $mysqli->error . ".");
		}
		
		if (isset($this->entries)) array_drop($this->entries, $entry);
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = num - 1 WHERE test_id = %d AND num > %d ORDER BY num",
			$this->get_test_id(),
			$entry->get_user_entry_id()
		));
		
		if (!!$mysqli->error)
		{
			return self::set_error_description("Test failed to remove entry: " . $mysqli->error . ".");
		}
		
		return $this;
	}
	
	private $sittings;
	public function get_sittings()
	{
		return self::get_cached_collection($this->sittings, "Sitting", "course_unit_test_sittings", "test_id", $this->get_test_id());
	}
	public function uncache_sittings()
	{
		if (isset($this->sittings)) unset($this->sittings);
	}
	public function get_sitting_for_user($user)
	{
		foreach ($this->get_sittings() as $sitting)
		{
			if (!$sitting->get_user())
			{
				return self::set_error_description("Failed to identify user for sitting: " . Sitting::unset_error_description());
			}
			
			if ($sitting->get_user()->equals($user)) return $sitting;
		}
		
		return null;
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
	
	private $timer;
	public function get_timer()
	{
		return $this->timer;
	}
	public function set_timer($timer)
	{
		return parent::update_this($this, "course_unit_tests", array ("timer" => intval($timer, 10)), "test_id", $this->get_test_id());
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
			? new self(
				$result_assoc["test_id"],
				$result_assoc["unit_id"],
				$result_assoc["name"],
				$result_assoc["open"],
				$result_assoc["close"],
				$result_assoc["message"]
			)
			: null;
	}
	
	public function user_can_execute($user)
	{
		if (!($course = $this->get_course()))
		{
			return self::set_error_description("Failed to get course for test where test_id = " . $this->get_test_id() . ".");
		}
		
		return ($course->user_is_student($user)
				&& (!$this->get_timeframe()
					|| $this->get_timeframe()->is_current()));
	}
	
	public function execute_for_session_user()
	{
		if ($this->session_user_can_execute())
		{
			if (($sitting = $this->get_sitting_for_user(Session::get()->get_user())))
			{
				return $sitting;
			}
			
			if (($sitting = Sitting::insert($this->get_test_id())))
			{
				return $sitting;
			}
			
			return self::set_error_description("Failed to execute test: " . Sitting::unset_error_description());
		}
		
		$reasons = array ();
		if (!$this->session_user_is_student()) array_push($reasons, "session user is not course student");
		if (!!$this->get_timeframe() && !$this->get_timeframe()->is_current()) array_push($reasons, "test timeframe is not current");
		
		return self::set_error_description("Session user cannot execute test because " . implode(" and ", $reasons) . ".");
	}
	
	public function delete()
	{
		$this->get_unit()->uncache_tests();
		return self::delete_this($this, "course_unit_tests", "test_id", $this->get_test_id());
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->privacy_mask(array (
			"testId" => $this->get_test_id(),
			"name" => $this->get_test_name(),
			"unitId" => $this->get_unit_id(),
			"courseId" => $this->get_course_id(),
			//"owner" => $this->get_owner()->json_assoc(),
			"timeframe" => !!$this->get_timeframe() ? $this->get_timeframe()->json_assoc() : null,
			"entriesCount" => count($this->get_entries()),
			"message" => $this->get_message()
		), array (0 => "testId"), $privacy);
	}
	
	public function detailed_json_assoc($privacy = null)
	{
		$assoc = $this->json_assoc($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["course"] = $this->get_course()->json_assoc($privacy !== null ? $privacy : null);
		$assoc["unit"] = $this->get_unit()->json_assoc($privacy !== null ? $privacy : null);
		$assoc["entries"] = $this->session_user_can_write() ? self::array_for_json($this->get_entries()) : null;
		
		return $this->privacy_mask($assoc, $public_keys, $privacy);
	}
}

?>
