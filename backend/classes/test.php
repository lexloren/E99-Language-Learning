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
	public function entries()
	{
		$user_entries = "(course_unit_test_entries LEFT JOIN user_entries USING (user_entry_id))";
		$language_codes = sprintf("(SELECT entry_id, %s FROM %s) AS reference",
			Dictionary::language_code_columns(),
			Dictionary::join()
		);
		$table = "$user_entries LEFT JOIN $language_codes USING (entry_id)";
		return self::cache($this->entries, "UserEntry", $table, "test_id", $this->get_test_id(), "*", "ORDER BY num", "test_entry_id");
	}
	public function uncache_entries()
	{
		if (isset($this->entries)) unset($this->entries);
	}
	public function set_entry_number($entry, $number)
	{
		if (!$this->session_user_can_write()) return static::set_error_description("Session user cannot edit test.");
		
		if ($this->executed())
		{
			return static::set_error_description("Test failed to update because some student has already begun executing the test.");
		}
		
		if ($number === null || ($number = intval($number, 10)) < 1) return static::set_error_description("Test cannot set entry number to null or nonpositive integer.");
		
		$entry = $entry->copy_for_user($this->get_owner(), $this);
		
		if ($number > ($entries_count = count($this->entries()))) $number = $entries_count;
		
		if ($number === ($number_formerly = array_search($entry, $this->entries()) + 1)) return $this;
		
		if ($number_formerly < 1) return static::set_error_description("Test cannot set entry number for entry not already in test.");
			
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = $entries_count + 1 WHERE test_id = %d AND user_entry_id = %d", $this->get_test_id(), $entry->get_user_entry_id()));
		
		if ($mysqli->error) return static::set_error_description("Test Modification", "Failed to reorder test entries: " . $mysqli->error . ".");
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = num - 1 WHERE test_id = %d AND num > %d ORDER BY num", $this->get_test_id(), $number_formerly));
		
		if ($mysqli->error) return static::set_error_description("Test Modification", "Failed to reorder test entries: " . $mysqli->error . ".");
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = num + 1 WHERE test_id = %d AND num >= %d ORDER BY num DESC", $this->get_test_id(), $number));
		
		if ($mysqli->error) return static::set_error_description("Test Modification", "Failed to reorder test entries: " . $mysqli->error . ".");
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = $number WHERE test_id = %d AND user_entry_id = %d", $this->get_test_id(), $entry->get_user_entry_id()));
		
		if ($mysqli->error) return static::set_error_description("Test Modification", "Failed to reorder test entries: " . $mysqli->error . ".");
		
		$this->uncache_entries();
		
		return $this;
	}
	public function set_entry_mode($entry, $mode)
	{
		if (!$this->session_user_can_write()) return static::set_error_description("Session user cannot edit test.");
		
		if ($this->executed())
		{
			return static::set_error_description("Test failed to update because some student has already begun executing the test.");
		}
		
		$mode = intval($mode, 10);
		
		if ($mode > 6 || $mode < 0)
		{
			return static::set_error_description("Test cannot set mode = $mode.");
		}
		
		$entry = $entry->copy_for_user($this->get_owner(), $this);
		
		if (($test_entry_id = array_search($entry, $this->entries())) < 0)
		{
			return static::set_error_description("Test cannot set mode for entry not already in test.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET mode = $mode WHERE test_id = %d AND user_entry_id = %d", $this->get_test_id(), $entry->get_user_entry_id()));
		
		if (isset($this->modes)) $this->modes[$test_entry_id] = $mode;
		
		return $this;
	}
	
	private $modes;
	public function get_entry_mode($entry)
	{
		if (!isset($this->modes))
		{
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query("SELECT test_entry_id, mode FROM course_unit_test_entries WHERE test_id = " . $this->get_test_id());
			
			if (!!$mysqli->error)
			{
				return static::set_error_description("Test failed to get entry mode: " . $mysqli->error . ".");
			}
			
			$this->modes = array ();
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				$this->modes[intval($result_assoc["test_entry_id"], 10)] = intval($result_assoc["mode"], 10);
			}
		}
		
		if (($test_entry_id = array_search($entry, $this->entries())) < 0)
		{
			return static::set_error_description("Test cannot get mode for entry not already in test.");
		}
		
		return $this->modes[$test_entry_id];
	}
	
	public function entries_randomize($renumber = true, $remode = false)
	{
		if (!$this->session_user_can_write()) return static::set_error_description("Session user cannot edit test.");
		
		if ($this->executed())
		{
			return static::set_error_description("Test failed to update because some student has already begun executing the test.");
		}
		
		if (!$renumber && !$remode)
		{
			return static::set_error_description("Test failed to randomize entries because neither reorder nor remode requested.");
		}
		
		$entries_ordered = $this->entries();
		$entries_count = count($entries_ordered);
		
		if ($renumber)
		{
			$entries_randomized = array ();
			
			while (count($entries_ordered) > 0)
			{
				array_push($entries_randomized, array_splice($entries_ordered, rand(0, count($entries_ordered)), 1));
			}
			
			$this->uncache_entries();
			
			$mysqli = Connection::get_shared_instance();
			
			$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = num + $entries_count WHERE test_id = %d", $this->get_test_id()));
			
			if ($mysqli->error)
			{
				return static::set_error_description("Test-Entries Randomization", "Failed to update test entries: " . $mysqli->error . ".");
			}
		}
		else
		{
			$entries_randomized = $entries_ordered;
		}
		
		for ($i = 0; $i < $entries_count; $i ++)
		{
			$mode = $remode ? rand(0, 6) : "mode";
			
			$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = $i + 1, mode = $mode WHERE test_id = %d AND user_entry_id = %d", $this->get_test_id(), $entries_randomized[$i]->get_user_entry_id()));
		
			if ($mysqli->error)
			{
				return static::set_error_description("Test-Entries Randomization", "Failed to update test entries: " . $mysqli->error . ".");
			}
		}
		
		return $this;
	}
	
	public function entries_add($entry, $mode = null)
	{
		if (!$entry)
		{
			return static::set_error_description("Test cannot add null entry.");
		}
		
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit test.");
		}
		
		if ($this->executed())
		{
			return static::set_error_description("Test failed to update because some student has already begun executing the test.");
		}
		
		//  Insert into user_entries from dictionary, if necessary
		$entry = $entry->copy_for_user($this->get_owner());
		
		if (!$entry)
		{
			return static::set_error_description("Test failed to add entry: " . Entry::unset_error_description());
		}
		
		$mode = $mode === null ? rand(0, 6) : intval($mode, 10);
		
		if ($mode > 6 || $mode < 0)
		{
			return static::set_error_description("Test cannot set mode = $mode.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		//  Insert into list_entries for $this->list_id and $entry->entry_id
		//      If this entry already exists in the list, then ignore the error
		$mysqli->query(sprintf("INSERT INTO course_unit_test_entries (test_id, user_entry_id, num, mode) VALUES (%d, %d, %d, $mode) ON DUPLICATE KEY UPDATE test_entry_id = LAST_INSERT_ID(test_entry_id)",
			$this->get_test_id(),
			$entry->get_user_entry_id(),
			$this->entries_count() + 1
		));
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Test failed to add entry: " . $mysqli->error . ".");
		}
		
		if (isset($this->entries))
		{
			if ($mysqli->insert_id)
			{
				$this->entries[$mysqli->insert_id] = $entry;
			}
			else
			{
				$this->uncache_entries();
			}
		}
		
		switch ($mode % 3)
		{
			case 1:
			{
				$contents = $entry->get_word_1();
			} break;
			
			case 2:
			{
				$contents = array_pop($entry->pronunciations());
			} break;
			
			default:
			{
				$contents = $entry->get_word_0();
			}
		}
		
		return Pattern::insert(
					$this->get_test_id(),
					$entry->get_entry_id(),
					$contents,
					true
				)
			? $this
			: static::set_error_description(
					"Test failed to add entry pattern: " .
					Pattern::unset_error_description()
				);
	}
	
	public function entries_add_from_list($list, $mode = null)
	{
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit test.");
		}
		
		if ($this->executed())
		{
			return static::set_error_description("Test failed to update because some student has already begun executing the test.");
		}
		
		foreach ($list->entries() as $entry)
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
		
		if ($this->executed())
		{
			return static::set_error_description("Test failed to update because some student has already begun executing the test.");
		}
		
		$entry = $entry->copy_for_user($this->get_owner(), $this);
		
		$number = array_search($entry, $this->entries()) + 1;
		
		if ($number < 1) return static::set_error_description("Test cannot remove entry not already in test.");
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM course_unit_test_entries WHERE test_id = %d AND user_entry_id = %d LIMIT 1",
			$this->get_test_id(),
			$entry->get_user_entry_id()
		));
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Test failed to remove entry: " . $mysqli->error . ".");
		}
		
		if (isset($this->entries)) array_drop($this->entries, $entry);
		
		$mysqli->query(sprintf("UPDATE course_unit_test_entries SET num = num - 1 WHERE test_id = %d AND num > %d ORDER BY num",
			$this->get_test_id(),
			$entry->get_user_entry_id()
		));
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Test failed to remove entry: " . $mysqli->error . ".");
		}
		
		return $this;
	}
	
	public function entry_options($entry)
	{
		if (($test_entry_id = array_search($entry, $this->entries())) < 0)
		{
			return static::set_error_description("Test cannot get options for entry not already in test.");
		}
		
		$patterns = $this->patterns();
		$patterns_returnable = array ();
		
		foreach ($patterns as $pattern)
		{
			if ($pattern->get_test_entry_id() === $test_entry_id)
			{
				array_push($patterns_returnable, $pattern);
			}
		}
		
		return $patterns_returnable;
	}
	
	private $sittings;
	public function sittings()
	{
		return self::cache($this->sittings, "Sitting", "course_unit_test_sittings", "test_id", $this->get_test_id());
	}
	public function uncache_sittings()
	{
		if (isset($this->sittings)) unset($this->sittings);
	}
	public function get_sitting_for_user($user)
	{
		foreach ($this->sittings() as $sitting)
		{
			if (!$sitting->get_user())
			{
				return static::set_error_description("Failed to identify user for sitting: " . Sitting::unset_error_description());
			}
			
			if ($sitting->get_user()->equals($user)) return $sitting;
		}
		
		return null;
	}
	
	private $patterns;
	public function patterns()
	{
		return self::cache($this->patterns, "Pattern", "course_unit_test_entries CROSS JOIN course_unit_test_entry_patterns USING (test_entry_id, mode)", "test_id", $this->get_test_id());
	}
	
	public function executed()
	{
		return count($this->sittings()) > 0;
	}
	
	public function unexecute()
	{
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit test.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM course_unit_test_sittings WHERE test_id = %d",
			$this->get_test_id()
		));
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Test failed to unexecute: " . $mysqli->error . ".");
		}
		
		$this->uncache_sittings();
		
		return $this;
	}
	
	private $timeframe = null;
	public function get_timeframe()
	{
		return $this->timeframe;
	}
	public function set_timeframe($timeframe)
	{
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit test.");
		}
		
		if ($this->executed())
		{
			return static::set_error_description("Test failed to update because some student has already begun executing the test.");
		}
		
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
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit test.");
		}
		
		if ($this->executed())
		{
			return static::set_error_description("Test failed to update because some student has already begun executing the test.");
		}
		
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
			return static::set_error_description("Failed to get course for test where test_id = " . $this->get_test_id() . ".");
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
			
			return static::set_error_description("Failed to execute test: " . Sitting::unset_error_description());
		}
		
		$reasons = array ();
		if (!$this->session_user_is_student()) array_push($reasons, "session user is not course student");
		if (!!$this->get_timeframe() && !$this->get_timeframe()->is_current()) array_push($reasons, "test timeframe is not current");
		
		return static::set_error_description("Session user cannot execute test because " . implode(" and ", $reasons) . ".");
	}
	
	public function delete()
	{
		$this->get_unit()->uncache_tests();
		return self::delete_this($this, "course_unit_tests", "test_id", $this->get_test_id());
	}
	
	public function entries_count()
	{
		return self::count("course_unit_tests CROSS JOIN course_unit_test_entries USING (test_id)", "test_id", $this->get_test_id());
	}
	
	public function entries_json_array()
	{
		$json_array = array ();
		
		foreach ($this->entries() as $test_entry_id => $entry)
		{
			$entry_assoc = $entry->json_assoc();
			$entry_assoc["testEntryId"] = $test_entry_id;
			$entry_assoc["mode"] = $this->get_entry_mode($entry);
			$entry_assoc["options"] = self::json_array($this->entry_options($entry));
			
			foreach ($entry_assoc["options"] as &$option)
			{
				unset($option["hiddenFromSessionUser"]);
				unset($option["sessionUserPermissions"]);
			}
			
			array_push($json_array, $entry_assoc);
		}
		
		return $json_array;
	}
	
	public function sittings_count()
	{
		return self::count("course_unit_tests CROSS JOIN course_unit_test_sittings USING (test_id)", "test_id", $this->get_test_id());
	}
	
	public function patterns_count()
	{
		return self::count("course_unit_test_entries CROSS JOIN course_unit_test_patterns USING (test_entry_id)", "test_id", $this->get_test_id());
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->privacy_mask(array (
			"testId" => $this->get_test_id(),
			"name" => $this->get_test_name(),
			"unitId" => $this->get_unit_id(),
			"courseId" => $this->get_course_id(),
			"timeframe" => !!$this->get_timeframe()
				? ($this->session_user_can_read() || $this->session_user_can_execute()
					? $this->get_timeframe()->json_assoc()
					: null)
				: null,
			"entriesCount" => $this->entries_count(),
			"sittingsCount" => $this->sittings_count(),
			//  "patternsCount" => $this->patterns_count(),
			"message" => $this->get_message()
		), array ("testId", "timeframe"), $privacy);
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		$assoc = $this->json_assoc($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["course"] = $this->get_course()->json_assoc($privacy !== null ? $privacy : null);
		$assoc["unit"] = $this->get_unit()->json_assoc($privacy !== null ? $privacy : null);
		$assoc["entries"] = $this->session_user_can_write() ? $this->entries_json_array() : null;
		$assoc["sittings"] = $this->session_user_can_write() ? self::json_array($this->sittings()) : null;
		//  $assoc["patterns"] = $this->session_user_can_write() ? self::json_array($this->patterns()) : null;
		
		return $this->privacy_mask($assoc, $public_keys, $privacy);
	}
}

?>