<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Test extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	public static function insert($unit_id, $name = null, $timeframe = null, $timer = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return static::errors_push("Session user has not reauthenticated.");
		}
		
		if (!($unit = Unit::select_by_id(($unit_id = intval($unit_id, 10)))))
		{
			return static::errors_push("Failed to insert test: " . Unit::errors_unset());
		}
		
		if (!$unit->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit course unit.");
		}
		
		$name = $name !== null ? "'" . Connection::escape($name) . "'" : "NULL";
		$message = $message !== null ? "'" . Connection::escape($message) . "'" : "NULL";
		$open = !!$timeframe ? $timeframe->get_open() : "NULL";
		$close = !!$timeframe ? $timeframe->get_close() : "NULL";
		$timer = !!$timer && ($timer = intval($timer, 10)) > 0 ? $timer : "NULL";
		
		Connection::query("INSERT INTO course_unit_tests (unit_id, name, open, close, timer, message) VALUES ($unit_id, $name, $open, $close, $timer, $message)");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to insert test: $error.");
		}
		
		$unit->uncache_tests();
		
		return self::select_by_id(Connection::query_insert_id());
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
	public function get_name()
	{
		return $this->name;
	}
	public function set_name($name)
	{
		if (strlen($name) === 0) $name = null;
		if (!self::update_this($this, "course_unit_tests", array ("name" => $name), "test_id", $this->get_test_id()))
		{
			return null;
		}
		$this->name = $name;
		return $this;
	}
	
	private $disclosed = null;
	public function get_disclosed()
	{
		return $this->disclosed;
	}
	public function set_disclosed($disclosed)
	{
		$disclosed = !!$disclosed ? 1 : 0;
		if (!self::update_this($this, "course_unit_tests", array ("disclosed" => $disclosed), "test_id", $this->get_test_id()))
		{
			return null;
		}
		$this->disclosed = !!$disclosed;
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
	public function get_container()
	{
		return $this->get_unit();
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
	public function entries_by_number()
	{
		$user_entries = "(course_unit_test_entries LEFT JOIN user_entries USING (user_entry_id))";
		$language_codes = sprintf("(SELECT entry_id, %s FROM %s) AS reference",
			Dictionary::language_code_columns(),
			Dictionary::join()
		);
		$table = "$user_entries LEFT JOIN $language_codes USING (entry_id)";
		return self::collect("UserEntry", $table, "test_id", $this->get_test_id(), "*", "ORDER BY num");
	}
	public function number_for_entry($entry)
	{
		$entries_by_number = $this->entries_by_number();
		if (($entry_number = array_search($entry, $entries_by_number)) < 0)
		{
			foreach ($entries_by_number as $number => $test_entry)
			{
				if ($test_entry->get_user_entry_id() === $entry->get_user_entry_id())
				{
					$entry_number = $number;
					break;
				}
			}
		}
		
		return $entry_number + 1;
	}
	public function uncache_entries()
	{
		if (isset($this->entries)) unset($this->entries);
	}
	public function set_entry_number($entry, $number)
	{
		if (!$this->session_user_can_write()) return static::errors_push("Session user cannot edit test.");
		
		$test = $this;
		$error_message;
		return ($result = Connection::transact(
			function () use ($test, $entry, $number, &$error_message)
			{
				if ($test->executed())
				{
					$error_message = "Test failed to update because some student has already begun executing the test.";
					return null;
				}
				
				if ($number === null || ($number = intval($number, 10)) < 1) return static::errors_push("Test cannot set entry number to null or nonpositive integer.");
				
				$entry = $entry->copy_for_user($test->get_owner(), $test);
				
				if ($number > ($entries_count = count($test->entries()))) $number = $entries_count;
				
				if ($number === ($number_formerly = $this->number_for_entry($entry))) return $test;
				
				if ($number_formerly < 1)
				{
					$error_message = "Test cannot set entry number for entry not already in test.";
					return null;
				}
					
				Connection::query(sprintf("UPDATE course_unit_test_entries SET num = $entries_count + 1 WHERE test_id = %d AND user_entry_id = %d", $test->get_test_id(), $entry->get_user_entry_id()));
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "Failed to renumber test entries: $error.";
					return null;
				}
				
				Connection::query(sprintf("UPDATE course_unit_test_entries SET num = num - 1 WHERE test_id = %d AND num > %d ORDER BY num", $test->get_test_id(), $number_formerly));
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "Failed to renumber test entries: $error.";
					return null;
				}
				
				Connection::query(sprintf("UPDATE course_unit_test_entries SET num = num + 1 WHERE test_id = %d AND num >= %d ORDER BY num DESC", $test->get_test_id(), $number));
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "Failed to renumber test entries: $error.";
					return null;
				}
				
				Connection::query(sprintf("UPDATE course_unit_test_entries SET num = $number WHERE test_id = %d AND user_entry_id = %d", $test->get_test_id(), $entry->get_user_entry_id()));
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "Failed to renumber test entries: $error.";
					return null;
				}
				
				$test->uncache_entries();
				
				return $test;
			}
		)) ? $result : static::errors_push($error_message);
	}
	public function set_entry_mode($entry, $mode)
	{
		if (!$this->session_user_can_write()) return static::errors_push("Session user cannot edit test.");
		
		if ($this->executed())
		{
			return static::errors_push("Test failed to update because some student has already begun executing the test.");
		}
		
		$mode = intval($mode, 10);
		
		if ($mode > 6 || $mode < 0)
		{
			return static::errors_push("Test cannot set mode = $mode.");
		}
		
		$entry = $entry->copy_for_user($this->get_owner(), $this);
		
		if (($test_entry_id = $this->get_test_entry_id_for_entry($entry)) < 0)
		{
			return static::errors_push("Test cannot set mode for entry not already in test.");
		}
		
		Connection::query(sprintf("UPDATE course_unit_test_entries SET mode = $mode WHERE test_id = %d AND user_entry_id = %d", $this->get_test_id(), $entry->get_user_entry_id()));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Test failed to update entry mode: $error.");
		}
		
		if (isset($this->modes)) $this->modes[$test_entry_id] = $mode;
		
		return $this;
	}
	
	private $modes;
	public function get_entry_mode($entry)
	{
		if (!isset($this->modes))
		{
			$result = Connection::query("SELECT test_entry_id, mode FROM course_unit_test_entries WHERE test_id = " . $this->get_test_id());
			
			if (!!($error = Connection::query_error_clear()))
			{
				return static::errors_push("Test failed to get entry mode: $error.");
			}
			
			$this->modes = array ();
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				$this->modes[intval($result_assoc["test_entry_id"], 10)] = Mode::select_by_id(intval($result_assoc["mode"], 10));
			}
		}
		
		if (($test_entry_id = $this->get_test_entry_id_for_entry($entry)) < 0)
		{
			return static::errors_push("Test cannot get mode for entry not already in test.");
		}
		
		return $this->modes[$test_entry_id];
	}
	
	public function entries_randomize($renumber = true, $remode = false)
	{
		if (!$this->session_user_can_write()) return static::errors_push("Session user cannot edit test.");
		
		if ($this->executed())
		{
			return static::errors_push("Test failed to update because some student has already begun executing the test.");
		}
		
		if (!$renumber && !$remode)
		{
			return static::errors_push("Test failed to randomize entries because neither renumber nor remode requested.");
		}
		
		$test = $this;
		$error_message;
		return ($result = Connection::transact(
			function () use ($test, $renumber, $remode, &$error_message)
			{
				$entries_ordered = array_values($test->entries());
				$entries_count = count($entries_ordered);
				
				if ($renumber)
				{
					$entries_reordered = array ();
					
					while (count($entries_ordered) > 0)
					{
						$i = rand(0, count($entries_ordered) - 1);
						array_push($entries_reordered, $entries_ordered[$i]);
						unset($entries_ordered[$i]);
						$entries_ordered = array_values($entries_ordered);
					}
					
					Connection::query(sprintf("UPDATE course_unit_test_entries SET num = num + $entries_count WHERE test_id = %d", $test->get_test_id()));
					
					if (!!($error = Connection::query_error_clear()))
					{
						$error_message = "Failed to update test entries: $error.";
						return null;
					}
				}
				else
				{
					$entries_reordered = $entries_ordered;
				}
				
				foreach ($test->entries() as $test_entry_id => $entry)
				{
					$mode = $remode ? rand(0, 6) : "mode";
					
					Connection::query(sprintf("UPDATE course_unit_test_entries SET num = %d, mode = $mode WHERE test_id = %d AND test_entry_id = $test_entry_id",
						array_search($entry, $entries_reordered) + 1,
						$test->get_test_id()
					));
				
					if (!!($error = Connection::query_error_clear()))
					{
						$error_message = "Failed to update test entries: $error.";
						return null;
					}
				}
				
				return $test->entries();
			}
		)) ? $result : static::errors_push($error_message);
	}
	
	public function get_entry_by_test_entry_id($test_entry_id)
	{
		$entries = $this->entries();
		return $entries[$test_entry_id];
	}
	
	public function get_test_entry_id_for_entry($entry)
	{
		if (($test_entry_id = array_search($entry, $this->entries())) > 0)
		{
			return $test_entry_id;
		}
		
		foreach ($this->entries() as $test_entry_id => $test_entry)
		{
			if ($test_entry->get_user_entry_id() === $entry->get_user_entry_id())
			{
				return $test_entry_id;
			}
		}
		
		return -1;
	}
	
	public function entries_add($entry, $mode = null)
	{
		if (!$entry)
		{
			return static::errors_push("Test cannot add null entry.");
		}
		
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit test.");
		}
		
		if ($this->executed())
		{
			return static::errors_push("Test failed to update because some student has already begun executing the test.");
		}
		
		//  Insert into user_entries from dictionary, if necessary
		$entry = $entry->copy_for_user($this->get_owner());
		
		if (!$entry)
		{
			return static::errors_push("Test failed to add entry: " . Entry::errors_unset());
		}
		
		$mode = $mode === null ? rand(0, 6) : intval($mode, 10);
		
		$test = $this;
		$error_message;
		
		return ($result = Connection::transact(
			function () use ($test, $entry, $mode, &$error_message)
			{
				//  Insert into list_entries for $this->list_id and $entry->entry_id
				//      If this entry already exists in the list, then ignore the error
				Connection::query(sprintf("INSERT INTO course_unit_test_entries (test_id, user_entry_id, num, mode) VALUES (%d, %d, %d, $mode) ON DUPLICATE KEY UPDATE mode = $mode",
					$test->get_test_id(),
					$entry->get_user_entry_id(),
					$test->entries_count() + 1
				));
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "Test failed to add entry: $error.";
					return null;
				}
				
				if (isset($test->entries) && Connection::query_insert_id())
				{
					$test->entries[Connection::query_insert_id()] = $entry;
				}
				
				$result = Connection::query("SELECT mode_id AS mode FROM modes");
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "Test failed to add entry: $error.";
					return null;
				}
				
				$mode_ids = array ();
				while (($result_assoc = $result->fetch_assoc()))
				{
					array_push($mode_ids, intval($result_assoc["mode"], 10));
				}
				
				$pronoun = $entry->pronunciations();
				$contents = array ($entry->get_word_0(), $entry->get_word_1(), array_pop($pronoun));
				
				foreach ($mode_ids as $mode_id)
				{
					if (!Pattern::insert(
								$test->get_test_id(),
								$entry->get_entry_id(),
								$contents[$mode % 3],
								true,
								null,
								$mode_id
									))
					{
						$error_message = "Test failed to add entry pattern: " . Pattern::errors_unset();
						return null;
					}
				}
				
				return $test->entries();
			}
		)) ? $result : static::errors_push($error_message);
	}
	
	public function entries_add_from_list($list, $mode = null)
	{
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit test.");
		}
		
		if ($this->executed())
		{
			return static::errors_push("Test failed to update because some student has already begun executing the test.");
		}
		
		$test = $this;
		
		return Connection::transact(
			function () use ($test, $list, $mode)
			{
				foreach ($list->entries() as $entry)
				{
					if (!$test->entries_add($entry, $mode)) return null;
				}
				return $test->entries();
			}
		);
	}
	
	public function entries_remove($entry)
	{
		if (!$entry)
		{
			return static::errors_push("Test cannot remove null entry.");
		}
		
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit test.");
		}
		
		if ($this->executed())
		{
			return static::errors_push("Test failed to update because some student has already begun executing the test.");
		}
		
		$entry = $entry->copy_for_user($this->get_owner(), $this);
		
		$number = $this->number_for_entry($entry);
		
		if ($number < 1) return static::errors_push("Test cannot remove entry not already in test.");
		
		$test = $this;
		$error_message;
		return ($result = Connection::transact(
			function () use ($test, $entry, $number, &$error_message)
			{
				Connection::query(sprintf("DELETE FROM course_unit_test_entries WHERE test_id = %d AND user_entry_id = %d LIMIT 1",
					$test->get_test_id(),
					$entry->get_user_entry_id()
				));
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "Test failed to remove entry: $error.";
					return null;
				}
				
				if (isset($test->entries)) array_drop($test->entries, $entry);
				
				Connection::query(sprintf("UPDATE course_unit_test_entries SET num = num - 1 WHERE test_id = %d AND num > %d ORDER BY num",
					$test->get_test_id(),
					$entry->get_user_entry_id()
				));
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "Test failed to remove entry: $error.";
					return null;
				}
				
				return $test->entries();
			}
		)) ? $result : static::errors_push($error_message);
	}
	
	public function entry_options($entry)
	{
		if (($test_entry_id = $this->get_test_entry_id_for_entry($entry)) < 0)
		{
			return static::errors_push("Test cannot get options for entry not already in test.");
		}
		
		return Pattern::select_all_for_test_entry_id($test_entry_id);
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
				return static::errors_push("Failed to identify user for sitting: " . Sitting::errors_unset());
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
		if (!$this->session_user_can_administer())
		{
			return static::errors_push("Session user cannot edit test.");
		}
		
		Connection::query(sprintf("DELETE FROM course_unit_test_sittings WHERE test_id = %d",
			$this->get_test_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Test failed to unexecute: $error.");
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
			return static::errors_push("Session user cannot edit test.");
		}
		
		if ($this->executed())
		{
			return static::errors_push("Test failed to update because some student has already begun executing the test.");
		}
		
		if (!self::update_this(
			$this,
			"course_unit_tests",
			!!$timeframe
				? $timeframe->mysql_assignments()
				: array ("open" => null, "close" => null),
			"test_id",
			$this->get_test_id()
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
			return static::errors_push("Session user cannot edit test.");
		}
		
		if ($this->executed())
		{
			return static::errors_push("Test failed to update because some student has already begun executing the test.");
		}
		
		$timer = intval($timer, 10) > 0 ? intval($timer, 10) : null;
		
		if (!self::update_this($this, "course_unit_tests", array ("timer" => $timer), "test_id", $this->get_test_id()))
                {
                        return null;
                }
                $this->timer = $timer;
                return $this;
	}
	
	//  inherits: protected $message;
	public function set_message($message)
	{
		return parent::set_this_message($this, $message, "course_unit_tests", "test_id", $this->get_test_id());
	}
	
	private function __construct($test_id, $unit_id, $name = null, $open = null, $close = null, $timer = null, $disclosed = null, $message = null)
	{
		$this->test_id = intval($test_id, 10);
		$this->unit_id = intval($unit_id, 10);
		$this->name = !!$name ? $name : null;
		$this->timeframe = !!$open && !!$close ? new Timeframe($open, $close) : null;
		$this->timer = intval($timer, 10) > 0 ? intval($timer, 10) : null;
		$this->disclosed = !!$disclosed;
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
			"timer",
			"disclosed",
			"message"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["test_id"],
				$result_assoc["unit_id"],
				$result_assoc["name"],
				$result_assoc["open"],
				$result_assoc["close"],
				$result_assoc["timer"],
				$result_assoc["disclosed"],
				$result_assoc["message"]
			)
			: null;
	}
	
	public function execute_for_session_user()
	{
		if ($this->session_user_can_execute() && $this->session_user_is_student())
		{
			if (($sitting = $this->get_sitting_for_user(Session::get()->get_user())))
			{
				return $sitting;
			}
			
			if (($sitting = Sitting::insert($this->get_test_id())))
			{
				return $sitting;
			}
			
			return static::errors_push("Failed to execute test: " . Sitting::errors_unset());
		}
		
		$reasons = array ();
		if (!$this->session_user_is_student()) array_push($reasons, "session user is not course student");
		if (!!$this->get_timeframe() && !$this->get_timeframe()->is_current()) array_push($reasons, "test timeframe is not current");
		
		return static::errors_push("Session user cannot execute test because " . implode(" and ", $reasons) . ".");
	}
	
	public function delete()
	{
		$this->get_unit()->uncache_tests();
		return self::delete_this($this, "course_unit_tests", "test_id", $this->get_test_id());
	}
	
	public function entries_count()
	{
		if (isset($this->entries)) return count($this->entries);
		return self::count("course_unit_tests CROSS JOIN course_unit_test_entries USING (test_id)", "test_id", $this->get_test_id());
	}
	
	public function entry_json_assoc($entry)
	{
		if (!$entry)
		{
			return static::errors_push("Test cannot get entry JSON for null entry.");
		}
		
		$entry = $entry->copy_for_user($this->get_owner(), true);
		
		if (!$entry)
		{
			return static::errors_push("Test cannot get entry JSON for null entry.");
		}
		
		$test_entry_id = $this->get_test_entry_id_for_entry($entry);
		
		if ($test_entry_id < 0)
		{
			return static::errors_push("Test cannot get entry JSON for entry not already in test.");
		}
		
		$entry_assoc = $entry->json_assoc(false);
		unset($entry_assoc["sessionUserPermissions"]);
		$entry_assoc["testEntryId"] = $test_entry_id;
		$entry_assoc["mode"] = $this->get_entry_mode($entry)->json_assoc();
		$entry_assoc["options"] = self::json_array($this->entry_options($entry));
		
		$entry_assoc["scoreMean"] = 0.0;
		$responses = Response::select_all_for_test_entry_id($test_entry_id);
		if ($responses === null)
		{
			return static::errors_push("Test failed to get entry responses: " . Response::errors_unset());
		}
		
		foreach ($responses as $response)
		{
			$entry_assoc["scoreMean"] += floatval($response->get_score());
		}
		if (count($responses)) $entry_assoc["scoreMean"] /= floatval(count($responses));
		else $entry_assoc["scoreMean"] = null;
		
		$entry_assoc["scoreMeanScaled"] =
			!!$this->entry_score_max($entry)
				? $entry_assoc["scoreMean"] / floatval($this->entry_score_max($entry))
				: 0.0;
		
		foreach ($entry_assoc["options"] as &$option)
		{
			unset($option["sessionUserPermissions"]);
		}
		
		return $entry_assoc;
	}
	
	public function entries_json_array()
	{
		if (($entries_by_number = $this->entries_by_number()) === null)
		{
			return null;
		}
		
		$json_array = array ();
		foreach ($entries_by_number as $entry)
		{
			$entry_assoc = $this->entry_json_assoc($entry);
			if (!$entry_assoc) return null;
			array_push($json_array, $entry_assoc);
		}
		
		return $json_array;
	}
	
	public function sittings_count()
	{
		if (isset($this->sittings)) return count($this->sittings);
		return self::count("course_unit_tests CROSS JOIN course_unit_test_sittings USING (test_id)", "test_id", $this->get_test_id());
	}
	
	public function patterns_count()
	{
		if (isset($this->patterns)) return count($this->patterns);
		return self::count("course_unit_test_entries CROSS JOIN course_unit_test_entry_patterns USING (test_entry_id)", "test_id", $this->get_test_id());
	}
	
	public function user_can_write($user)
	{
		return $this->user_can_administer($user) && !$this->executed();
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->privacy_mask(array (
			"testId" => $this->get_test_id(),
			"name" => $this->get_name(),
			"unitId" => $this->get_unit_id(),
			"courseId" => $this->get_course_id(),
			"timeframe" => !!$this->get_timeframe()
								&& $this->session_user_can_read()
					? $this->get_timeframe()->json_assoc()
					: null,
			"timer" => $this->get_timer(),
			"gradesDisclosed" => !!$this->get_disclosed(),
			"entriesCount" => $this->entries_count(),
			"sittingsCount" => $this->session_user_can_administer() ? $this->sittings_count() : null,
			"patternsCount" => $this->session_user_can_administer() ? $this->patterns_count() : null,
			"message" => $this->get_message()
		), array ("testId"), $privacy);
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		$assoc = $this->json_assoc($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["entries"] = $this->session_user_can_administer() ? $this->entries_json_array() : null;
		$assoc["sittings"] = $this->session_user_can_administer() ? self::json_array($this->sittings()) : null;
		$assoc["patterns"] = $this->session_user_can_administer() ? self::json_array($this->patterns()) : null;
		
		return $this->privacy_mask($assoc, $public_keys, $privacy);
	}
	
	public function seconds_per_entry()
	{
		return $this->get_timer() && $this->count_entries()
			? floatval($this->get_timer()) / floatval($this->count_entries())
			: null;
	}
	
	public function entry_score_max($entry)
	{
		$entry_score_max = 0;
		foreach (Pattern::select_all_for_test_entry_id($this->get_test_entry_id_for_entry($entry)) as $pattern)
		{
			if ($pattern->get_mode() == $this->get_entry_mode($entry)
				&& $pattern->get_score() > $entry_score_max)
			{
				$entry_score_max = $pattern->get_score();
			}
		}
		
		return $entry_score_max;
	}
	
	public function score_max()
	{
		$test_score_max = 0;
		foreach ($this->entries() as $entry)
		{
			$test_score_max += $this->entry_score_max($entry);
		}
		return $test_score_max;
	}
	
	/*
	public static function csv_columns_array()
	{
		return array (
			"courseId",
			"unitId",
			"testId",
			"languageKnown",
			"languageUnknown",
			"instructorsCount",
			"studentsCount",
			"researchersCount",
			"entriesCount",
			"optionsCount",
			"patternsCount",
			"testsMeanSecondsPerEntry",
			"sittingsMeanPerformance"
		);
	}
	
	public function csv_row_array()
	{
		$tests_mean_seconds_per_entry = 0.0;
		$tests_total_entries_count = 0;
		foreach ($this->tests() as $test)
		{
			$entries_count = $test->entries_count();
			$tests_total_entries_count += $entries_count;
			
			$tests_mean_seconds_per_entry +=
				floatval($entries_count) * $test->seconds_per_entry();
		}
		if (!$tests_total_entries_count) $tests_mean_seconds_per_entry = null;
		else $tests_mean_seconds_per_entry /= floatval($tests_total_entries_count);
		
		$sittings_mean_performance = 0.0;
		foreach ($this->sittings() as $sitting)
		{
			$score_json_assoc = $sitting->score_json_assoc();
			$sittings_mean_performance += $score_json_assoc["scoreScaled"];
		}
		$sittings_mean_performance /= floatval($this->sittings_count());
		
		return array (
			$this->get_course_id(),
			$this->get_unit_id(),
			$this->get_test_id(),
			Language::select_by_id($this->get_course()->get_lang_id_0())->get_lang_code(),
			Language::select_by_id($this->get_course()->get_lang_id_1())->get_lang_code(),
			$this->get_course()->instructors_count(),
			$this->get_course()->students_count(),
			$this->get_course()->researchers_count(),
			$this->entries_count(),
			$this->options_count(),
			$this->patterns_count(),
			$this->seconds_per_entry(),
			$sittings_mean_performance
		);
	}
	*/
}

?>
