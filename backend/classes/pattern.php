<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Pattern extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	public static function insert($test_id, $entry_id, $contents, $prompt = false, $score = null, $mode = null, $default = false)
	{
		if (!Session::get()->get_user())
		{
			return static::errors_push("Session user has not reauthenticated.");
		}
		
		$failure_message = "Failed to insert test entry pattern";
		
		if (!($test = Test::select_by_id(($test_id = intval($test_id, 10)))))
		{
			return static::errors_push("$failure_message: " . Test::errors_unset());
		}
		
		if (!$test->session_user_can_write()
			&& !($test->session_user_can_execute() && !$prompt && $score === null))
		{
			return static::errors_push("$failure_message: Session user cannot edit test.");
		}
		
		if (!($entry = UserEntry::select_by_user_id_entry_id($test->get_owner()->get_user_id(), ($entry_id = intval($entry_id, 10)))))
		{
			return static::errors_push("$failure_message: " . Entry::errors_unset());
		}
		
		$score = $score !== null ? intval($score, 10) : "NULL";
		
		$contents = Connection::escape($contents);
		$prompt = !!$prompt ? 1 : 0;
		$default = !!$default ? 1 : 0;
		
		$mode = $mode === null ? "mode" : intval($mode, 10);
		
		Connection::query(sprintf("INSERT INTO course_unit_test_entry_patterns (test_entry_id, mode, prompt, contents, score) SELECT test_entry_id, $mode, $prompt, '$contents', $score FROM course_unit_test_entries WHERE test_id = %d AND user_entry_id = %d ON DUPLICATE KEY UPDATE score = $score, prompt = $prompt",
			$test->get_test_id(), $entry->get_user_entry_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("$failure_message: $error.");
		}
		
		//  print_r("insert($test_id, $entry_id, $contents, $prompt, $score, $mode, $default)\n");
		return self::select_by_test_id_entry_id_contents_mode($test->get_test_id(), $entry->get_entry_id(), $contents, is_string($mode) ? null : $mode);
	}
	
	public static function select_all_for_test_entry_id($test_entry_id, $prompts_only = true)
	{
		$test_entry_id = intval($test_entry_id, 10);
		$prompts_only = $prompts_only ? 1 : 0;
		
		$result = Connection::query("SELECT course_unit_test_entry_patterns.* FROM course_unit_test_entry_patterns CROSS JOIN course_unit_test_entries USING (test_entry_id, mode) WHERE test_entry_id = $test_entry_id AND prompt >= $prompts_only");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to select all test entry patterns for test_entry_id = $test_entry_id: $error.");
		}
		
		$patterns = array ();
		
		while (($result_assoc = $result->fetch_assoc()))
		{
			array_push($patterns, self::from_mysql_result_assoc($result_assoc));
		}
		
		return $patterns;
	}
	
	public static function select_by_id($pattern_id)
	{
		return parent::select("course_unit_test_entry_patterns", "pattern_id", $pattern_id);
	}
	
	public static function select_by_test_id_entry_id_contents_mode($test_id, $entry_id, $contents, $mode = null)
	{
		$test_id = intval($test_id, 10);
		
		$failure_message = "Failed to select test entry pattern by test_id = $test_id, entry_id = $entry_id, contents = '$contents', mode = " . ($mode !== null ? $mode : "*");
		
		if (!($test = Test::select_by_id($test_id)))
		{
			return static::errors_push("$failure_message: " . Test::errors_unset());
		}
		
		$entry_id = intval($entry_id, 10);
		
		if (!($entry = UserEntry::select_by_user_id_entry_id($test->get_owner()->get_user_id(), $entry_id, false)))
		{
			return static::errors_push("$failure_message: " . Entry::errors_unset());
		}
		
		$user_entry_id = $entry->get_user_entry_id();
		//  print_r("select_by_test_id_entry_id_contents_mode($test_id, $entry_id, [$user_entry_id,] $contents, $mode)\n");
		
		$failure_message .= "[, user_entry_id = $user_entry_id]";
		
		$contents = Connection::escape($contents);
		
		if ($mode === null)
		{
			$result = Connection::query("SELECT * FROM course_unit_test_entry_patterns CROSS JOIN course_unit_test_entries USING (test_entry_id, mode) WHERE test_id = $test_id AND user_entry_id = $user_entry_id AND contents = '$contents'");
		}
		else
		{
			$mode = intval($mode, 10);
			
			$result = Connection::query("SELECT course_unit_test_entry_patterns.* FROM course_unit_test_entry_patterns CROSS JOIN course_unit_test_entries USING (test_entry_id) WHERE test_id = $test_id AND user_entry_id = $user_entry_id AND course_unit_test_entry_patterns.mode = $mode AND contents = '$contents'");
		}
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("$failure_message: $error.");
		}
		
		if ($result->num_rows > 1)
		{
			return static::errors_push("$failure_message: Multiple patterns selected!");
		}
		
		if ($result->num_rows > 0 && ($result_assoc = $result->fetch_assoc()))
		{
			return self::from_mysql_result_assoc($result_assoc);
		}
		
		return static::errors_push("$failure_message: No pattern selected!");
	}
	
	/***    INSTANCE    ***/

	private $pattern_id = null;
	public function get_pattern_id()
	{
		return $this->pattern_id;
	}

	private $test_entry_id = null;
	public function get_test_entry_id()
	{
		return $this->test_entry_id;
	}
	public function get_entry_id()
	{
		return $this->get_entry()->get_entry_id();
	}
	public function get_entry()
	{
		return $this->get_test()->get_entry_by_test_entry_id($this->get_test_entry_id());
	}
	
	private $test;
	public function get_test()
	{
		if (!isset($this->test))
		{
			if (!($this->test = Test::select_by_test_entry_id($this->get_test_entry_id())))
			{
				unset($this->test);
				return static::errors_push("$failure_message: " . Test::errors_unset());
			}
		}
		
		return $this->test;
	}
	public function get_course()
	{
		return $this->get_test()
			? $this->get_test()->get_course()
			: null;
	}
	public function get_container()
	{
		return $this->get_course();
	}
	
	private $prompt = null;
	public function get_prompt()
	{
		return $this->prompt;
	}
	public function set_prompt($prompt)
	{
		if (!static::update_this($this, "course_unit_test_entry_patterns", array ("prompt" => ($prompt = intval($prompt, 10))), "pattern_id", $this->get_pattern_id())) return null;
		
		$this->prompt = !!$prompt;
		
		return $this;
	}
	
	private $contents = null;
	public function get_contents()
	{
		return $this->contents;
	}
	
	private $user_entry;
	public function get_user_entry()
	{
		if (!isset($this->user_entry))
		{
			$result = Connection::query(sprintf("SELECT user_entry_id FROM course_unit_test_entries CROSS JOIN user_entries USING (user_entry_id) WHERE test_entry_id = %d GROUP BY user_entry_id", $this->get_test_entry_id()));
			
			$failure_message = "Test entry pattern failed to get test entry";
			
			if (!!($error = Connection::query_error_clear()))
			{
				return static::errors_push("$failure_message: $error.");
			}
			
			if (!($result_assoc = $result->fetch_assoc()))
			{
				return static::errors_push("$failure_message: Failed to select user entry where test_entry_id = " . $this->get_test_entry_id() . ".");
			}
			
			$this->user_entry = UserEntry::select_by_user_entry_id($result_assoc["user_entry_id"]);
		}
		
		return $this->user_entry;
	}
	
	private $mode_id = null;
	public function get_mode_id()
	{
		return $this->mode_id;
	}
	public function get_mode()
	{
		return Mode::select_by_id($this->get_mode_id());
	}
	
	private $score = null;
	public function get_score()
	{
		return $this->score;
	}
	public function set_score($score)
	{
		if (!static::update_this($this, "course_unit_test_entry_patterns", array ("score" => ($score = intval($score, 10))), "pattern_id", $this->get_pattern_id())) return null;
		
		$this->score = $score;
		return $this;
	}
	
	public function set_message($message)
	{
		return parent::set_this_message($this, $message, "course_unit_test_entry_patterns", "pattern_id", $this->get_pattern_id());
	}
	
	private function __construct($pattern_id, $test_entry_id, $mode_id, $prompt, $contents, $score)
	{
		$this->pattern_id = intval($pattern_id, 10);
		$this->test_entry_id = intval($test_entry_id, 10);
		$this->mode_id = intval($mode_id, 10);
		$this->prompt = !!$prompt;
		$this->contents = !!$contents && strlen($contents) > 0 ? $contents : null;
		$this->score = intval($score, 10);
		
		self::register($this->pattern_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"pattern_id",
			"test_entry_id",
			"mode",
			"prompt",
			"contents",
			"score",
			"message"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["pattern_id"],
				$result_assoc["test_entry_id"],
				$result_assoc["mode"],
				$result_assoc["prompt"],
				$result_assoc["contents"],
				$result_assoc["score"],
				$result_assoc["message"]
			)
			: null;
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->prune(array (
			"testId" => $this->get_test()->get_test_id(),
			"entryId" => $this->get_entry_id(),
			"testEntryId" => $this->get_test_entry_id(),
			"mode" => $this->get_mode()->json_assoc(),
			"patternId" => $this->get_pattern_id(),
			"contents" => $this->get_contents(),
			"score" => $this->get_score(),
			"message" => $this->get_message()
		), array (0 => "patternId"), $privacy);
	}
}

?>