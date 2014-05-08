<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Unit extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	public static function insert($course_id, $name = null, $timeframe = null, $message = null)
	{
		if (!Session::get()->get_user())
		{
			return static::errors_push("Session user has not reauthenticated.");
		}
		
		if (!($course = Course::select_by_id(($course_id = intval($course_id, 10)))))
		{
			return static::errors_push("Failed to insert unit: " . Course::errors_unset());
		}
		
		if (!$course->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit course.");
		}
		
		$number = count($course->units()) + 1;
		$open = !!$timeframe ? $timeframe->get_open() : "NULL";
		$close = !!$timeframe ? $timeframe->get_close() : "NULL";
		$message = $message !== null ? "'" . Connection::escape($message) . "'" : "NULL";
		
		Connection::query(sprintf("INSERT INTO course_units (course_id, name, num, open, close, message) VALUES (%d, %s, %d, %s, %s, %s)",
			$course_id,
			!!$name && strlen($name) > 0 ? "'$name'" : "NULL",
			$number,
			$open, $close,
			$message
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to insert unit: $error.");
		}
		
		$course->uncache_units();
		
		return self::select_by_id(Connection::query_insert_id());
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
	protected function get_container()
	{
		return $this->get_course();
	}
	
	private $unit_id = null;
	public function get_unit_id()
	{
		return $this->unit_id;
	}
	
	private $number = null;
	public function get_number()
	{
		return $this->number;
	}
	public function set_number($number)
	{
		if (!$this->session_user_can_write()) return static::errors_push("Session user cannot edit course unit.");
		
		if ($number === null || ($number = intval($number, 10)) < 1) return static::errors_push("Course unit cannot set number to null or nonpositive integer.");
		
		if ($number > ($units_count = count($this->get_course()->units()))) $number = $units_count;
		
		if ($number === $this->get_number()) return $this;
		
		$unit = $this;
		
		return Connection::transact(
		  	function () use ($unit, $number)
		  	{
				return $unit->set_number_php53_scope_workaround($number);
			}
		);
	}
	public function set_number_php53_scope_workaround($number)
	{
		$units_count = count($this->get_course()->units());
		$failure_message = "Failed to reorder course units";
		
		Connection::query(sprintf("UPDATE course_units SET num = $units_count + 1 WHERE unit_id = %d", $this->get_unit_id()));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("$failure_message: $error.");
		}
		
		Connection::query(sprintf("UPDATE course_units SET num = num - 1 WHERE course_id = %d AND num > %d ORDER BY num", $this->get_course_id(), $this->get_number()));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("$failure_message: $error.");
		}
		
		Connection::query(sprintf("UPDATE course_units SET num = num + 1 WHERE course_id = %d AND num >= %d ORDER BY num DESC", $this->get_course_id(), $number));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("$failure_message: $error.");
		}
		
		if (!self::update_this($this, "course_units", array ("num" => $number), "unit_id", $this->get_unit_id()))
		{
			return null;
		}
		
		$this->number = $number;
				
		return $this;
	}
	
	private $name = null;
	public function get_name()
	{
		return $this->name;
	}
	public function set_name($name)
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
				: array ("open" => null, "close" => null),
			"unit_id",
			$this->get_unit_id()
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

	public function uncache_tests()
	{
		if (isset($this->tests)) unset($this->tests);
	}
	public function uncache_lists()
	{
		if (isset($this->lists)) unset($this->lists);
	}
	public function uncache_all()
	{
		$this->uncache_tests();
		$this->uncache_lists();
	}
	
	private $tests;
	public function tests()
	{
		return self::cache($this->tests, "Test", "course_unit_tests", "unit_id", $this->get_unit_id());
	}
	
	private $lists;
	public function lists()
	{
		$table = "course_unit_lists LEFT JOIN lists USING (list_id)";
		return self::cache($this->lists, "EntryList", $table, "unit_id", $this->get_unit_id());
	}
	
	private function __construct($unit_id, $course_id, $number, $name = null, $open = null, $close = null, $message = null)
	{
		$this->unit_id = intval($unit_id, 10);
		$this->course_id = intval($course_id, 10);
		$this->number = intval($number, 10);
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
			? new self(
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
		$unit = $this;
		
		return Connection::transact(
			function () use ($unit)
			{
				return $unit->delete_php53_scope_workaround();
			}
		);
	}
	public function delete_php53_scope_workaround()
	{
		$failure_message = "Failed to reorder course units";
		if ((self::delete_this($this, "course_units", "unit_id", $this->get_unit_id())))
		{
			$this->get_course()->uncache_units();
			
			Connection::query(sprintf("UPDATE course_units SET num = num - 1 WHERE course_id = %d AND num >= %d ORDER BY num", $this->get_course_id(), $this->get_number()));
			
			if (!!($error = Connection::query_error_clear()))
			{
				return static::errors_push("$failure_message: $error.");
			}
			return $this;
		}
		
		return null;
	}
	
	public function lists_add($list)
	{
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit course.");
		}
		
		if (!$list->get_owner()->equals($this->get_owner())
			&& !($list = $list->copy_for_user($this->get_owner(), $list->session_user_can_read())))
		{
			return static::errors_push("Failed to add list: " . EntryList::errors_unset());
		}
		
		Connection::query(sprintf("INSERT INTO course_unit_lists (unit_id, list_id) VALUES (%d, %d) ON DUPLICATE KEY UPDATE unit_id = unit_id",
			$this->get_unit_id(),
			$list->get_list_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Unit failed to add list: $error.", ErrorReporter::ERRCODE_DATABASE);
		}
		
		if (isset($this->lists) && !in_array($list, $this->lists)) array_push($this->lists, $list);
		
		$list->uncache_courses();
		
		return $this;
	}
	
	public function lists_remove($list)
	{
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit course.");
		}
		
		Connection::query(sprintf("DELETE FROM course_unit_lists WHERE unit_id = %d AND list_id = %d",
			$this->get_unit_id(),
			$list->get_list_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Unit failed to remove list: $error.", ErrorReporter::ERRCODE_DATABASE);
		}
		
		if (isset($this->lists)) array_drop($this->lists, $list);
		
		$list->uncache_courses();
		
		return $this;
	}
	
	public function lists_count()
	{
		if (isset($this->lists)) return count($this->lists);
		$unit_lists = "course_units CROSS JOIN course_unit_lists USING (unit_id)";
		return self::count($unit_lists, "unit_id", $this->get_unit_id());
	}
	
	public function tests_count()
	{
		if (isset($this->tests)) return count($this->tests);
		$unit_tests = "course_units CROSS JOIN course_unit_tests USING (unit_id)";
		return self::count($unit_tests, "unit_id", $this->get_unit_id());
	}
	
	public function sittings()
	{
		$sittings = array ();
		foreach ($this->tests() as $test)
		{
			$test_sittings = $test->sittings();
			array_merge($sittings, $test_sittings);
		}
		return $sittings;
	}
	
	public function sittings_count()
	{
		if (isset($this->sittings)) return count($this->sittings);
		return self::count("course_unit_tests CROSS JOIN course_unit_test_sittings USING (test_id)", "unit_id", $this->get_unit_id());
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->prune(array (
			"courseId" => $this->get_course_id(),
			"unitId" => $this->get_unit_id(),
			"name" => $this->get_name(),
			"number" => $this->get_number(),
			"timeframe" => !!$this->get_timeframe() ? $this->get_timeframe()->json_assoc() : null,
			"listsCount" => $this->lists_count(),
			"testsCount" => $this->tests_count(),
			"message" => $this->get_message()
		), array (0 => "unitId"), $privacy);
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		$assoc = $this->json_assoc($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["lists"] = $this->session_user_can_execute() ? self::json_array($this->lists()) : null;
		$assoc["tests"] = $this->session_user_can_execute() ? self::json_array($this->tests()) : null;
		
		return $this->prune($assoc, $public_keys, $privacy);
	}
	
	/*
	public static function csv_columns_array()
	{
		return array (
			"courseId",
			"unitId",
			"languageKnown",
			"languageUnknown",
			"instructorsCount",
			"studentsCount",
			"researchersCount",
			"listsCount",
			"testsCount",
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
			Language::select_by_id($this->get_course()->get_lang_id_0())->get_lang_code(),
			Language::select_by_id($this->get_course()->get_lang_id_1())->get_lang_code(),
			$this->get_course()->instructors_count(),
			$this->get_course()->students_count(),
			$this->get_course()->researchers_count(),
			$this->lists_count(),
			$this->tests_count(),
			$tests_mean_seconds_per_entry,
			$sittings_mean_performance
		);
	}
	*/
}

?>