<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Sitting extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	public static function insert($test_id)
	{
		if (!($session_user = Session::get()->get_user())) return static::errors_push("Session user has not reauthenticated.");
		
		return Connection::transact(
			function () use ($test_id)
			{
				if (!($test = Test::select_by_id(($test_id = intval($test_id, 10)))))
				{
					return static::errors_push("Failed to insert test sitting: " . Test::errors_unset());
				}
				
				if (!$test->session_user_can_execute())
				{
					return static::errors_push("Session user cannot execute test.");
				}
				
				Connection::query(sprintf("INSERT INTO course_unit_test_sittings (test_id, student_id, start) SELECT %d, student_id, %d FROM course_students WHERE course_id = %d AND user_id = %d",
					$test->get_test_id(),
					time(),
					$test->get_course_id(),
					$session_user->get_user_id()
				));
				
				if (!!($error = Connection::query_error_clear()))
				{
					return static::errors_push("Failed to insert test sitting: $error.");
				}
				
				$test->uncache_sittings();
				
				return self::select_by_id(Connection::insert_id());
			}
		);
	}
	
	public static function select_by_id($sitting_id)
	{
		return parent::select("course_unit_test_sittings", "sitting_id", $sitting_id);
	}
	
	public static function select_by_test_id_user_id($test_id, $user_id)
	{
		$table = "course_unit_test_sittings";
		
		$result = Connection::query(sprintf("SELECT * FROM $table WHERE test_id = %d AND user_id = %d",
			($test_id = intval($test_id, 10)), ($user_id = intval($user_id, 10))
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to select from $table: $error.");
		}
		
		if (!$result || $result->num_rows === 0 || !($result_assoc = $result->fetch_assoc()))
		{
			return static::errors_push("Failed to select any rows from $table where test_id = $test_id and user_id = $user_id.");
		}
		
		return self::from_mysql_result_assoc($result_assoc);
	}
	
	/***    INSTANCE    ***/

	private $sitting_id = null;
	public function get_sitting_id()
	{
		return $this->sitting_id;
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
	public function get_course()
	{
		return $this->get_test()->get_course();
	}
	public function get_score_json_assoc()
	{
		$response_patterns = "(course_unit_test_sitting_responses CROSS JOIN course_unit_test_entry_patterns USING (pattern_id))";
		$result = Connection::query(sprintf("SELECT SUM(score) AS scoredTotal, SUM(unscored) AS unscoredCount FROM (SELECT score, IF(score IS NULL, 1, 0) AS unscored FROM $response_patterns WHERE sitting_id = %d) AS scores",
			$this->get_sitting_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to get test sitting score: $error.");
		}
		
		if (!($result_assoc = $result->fetch_assoc()))
		{
			return static::errors_push("Failed to get test sitting score!");
		}
		
		foreach ($result_assoc as $key => &$value)
		{
			$value = intval($value, 10);
		}
		
		return $result_assoc;
	}
	
	private $student_id = null;
	public function get_student_id()
	{
		return $this->student_id;
	}
	private $user;
	public function get_user()
	{
		if (!isset($this->user_id))
		{
			$result = Connection::query(sprintf("SELECT * FROM course_students WHERE student_id = %d",
				$this->get_student_id()
			));
			
			if (!!($error = Connection::query_error_clear()))
			{
				return static::errors_push("Failed to select sitting user: $error.");
			}
			
			if (!$result || $result->num_rows != 1 || !($result_assoc = $result->fetch_assoc()))
			{
				return static::errors_push("Failed to select sitting user: No user selected for student_id = " . $this->get_student_id() . ".");
			}
			
			if (!($this->user = User::select_by_id($result_assoc["user_id"])))
			{
				unset($this->user);
				return static::errors_push("Failed to select sitting user: " . User::errors_unset());
			}
		}
		
		return $this->user;
	}
	public function get_user_id()
	{
		if (!$this->get_user()) return null;
		
		return $this->get_user()->get_user_id();
	}
	
	public function uncache_responses()
	{
		if (isset($this->responses)) unset($this->responses);
	}
	public function uncache_all()
	{
		$this->uncache_responses();
		$this->uncache_enries_remaining();
	}
	
	private $responses;
	public function responses()
	{
		return self::cache($this->responses, "Response", "course_unit_test_sitting_responses", "sitting_id", $this->get_sitting_id());
	}
	
	private $timeframe = null;
	public function get_timeframe()
	{
		return $this->timeframe;
	}
	
	//  inherits: protected $message;
	public function set_message($message)
	{
		return parent::set_this_message($this, $message, "course_unit_test_sittings", "sitting_id", $this->get_sitting_id());
	}
	
	private function __construct($sitting_id, $test_id, $student_id, $start, $stop, $message)
	{
		$this->sitting_id = intval($sitting_id, 10);
		$this->test_id = intval($test_id, 10);
		$this->student_id = intval($student_id, 10);
		$this->timeframe = !!$start || !!$stop ? new Timeframe($start, $stop) : null;
		$this->message = !!$message && strlen($message) > 0 ? $message : null;
		
		self::register($this->sitting_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"sitting_id",
			"test_id",
			"student_id",
			"start",
			"stop",
			"message"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["sitting_id"],
				$result_assoc["test_id"],
				$result_assoc["student_id"],
				$result_assoc["start"],
				$result_assoc["stop"],
				$result_assoc["message"]
			)
			: null;
	}
	
	private $entries_remaining;
	public function entries_remaining()
	{
		if (!isset($this->entries_remaining))
		{
			$test_entry_ids = "SELECT test_entry_id, response_id FROM (course_unit_test_sitting_responses CROSS JOIN course_unit_test_entry_patterns USING (pattern_id)) LEFT JOIN course_unit_test_sittings USING (sitting_id) WHERE sitting_id = " . $this->get_sitting_id();
			
			//  Check whether user has already answered all the questions.
			$result = Connection::query(sprintf("SELECT user_entry_id FROM course_unit_test_entries LEFT JOIN ($test_entry_ids) AS course_unit_test_entries_completed USING (test_entry_id) WHERE test_id = %d AND response_id IS NULL ORDER BY num", $this->get_test_id()));
			
			if (!!($error = Connection::query_error_clear()))
			{
				return static::errors_push("Session failed to get entries remaining: $error.");
			}
			
			$this->entries_remaining = array ();
			while (($result_assoc = $result->fetch_assoc()))
			{
				array_push($this->entries_remaining, UserEntry::select_by_user_entry_id($result_assoc["user_entry_id"]));
			}
		}
		return $this->entries_remaining;
	}
	public function uncache_entries_remaining()
	{
		if (isset($this->entries_remaining)) unset($this->entries_remaining);
	}
	
	public function user_can_execute($user)
	{
		return $this->get_test()->user_can_execute($user)
			&& $this->get_user()->equals($user)
			&& $this->entries_remaining() > 0;
	}
	
	public function next_json_assoc()
	{
		if (!$this->session_user_can_execute())
		{
			return static::errors_push("Session user cannot execute test" . (!$this->entries_remaining() ? " because session user has already responded to all test entries" : "") . ".");
		}
		
		if (!count($entries_remaining = $this->entries_remaining()))
		{
			return static::errors_push("Session user has already responded to all test entries.");
		}
		
		$entry = array_shift($entries_remaining);
		
		$result = Connection::query(sprintf("SELECT * FROM course_unit_test_entries WHERE test_id = %d AND user_entry_id = %d",
			$this->get_test_id(),
			$entry->get_user_entry_id()
		));
		
		$failure_message = "Test failed to get next entry for session user";
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("$failure_message: $error.");
		}
		
		if (!($result_assoc = $result->fetch_assoc()))
		{
			return static::errors_push("Test failed to get mask for next entry for session user.");
		}
		
		$mode = $result_assoc["mode"];
		
		$result = Connection::query(sprintf("SELECT course_unit_test_entry_patterns.* FROM course_unit_test_entry_patterns CROSS JOIN course_unit_test_entries USING (test_entry_id, mode) WHERE test_entry_id = %d AND prompt = 1 ORDER BY rand()",
			($test_entry_id = intval($result_assoc["test_entry_id"], 10))
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("$failure_message: $error.");
		}
		
		$options = array ();
		while (($result_assoc = $result->fetch_assoc()))
		{
			array_push($options, $result_assoc["contents"]);
		}
		
		if (count($options) == 1) $options = null;
		
		return array (
			"testEntryId" => $test_entry_id,
			"entriesRemainingCount" => count($entries_remaining),
			"prompt" => $mode === 1 ? $entry->get_word_0() : $entry->get_word_1(),
			"options" => $options
		);
	}
	
	public function user_can_read($user)
	{
		if (!$user) return false;
		
		return $this->user_can_write($user)
			|| ($user->equals($this->get_user())
				&& !$this->user_can_execute($this->get_user()));
	}
	
	public function delete()
	{
		$this->get_test()->uncache_sittings();
		return self::delete_this($this, "course_unit_test_sittings", "sitting_id", $this->get_sitting_id());
	}
	
	public function responses_count()
	{
		return self::count("course_unit_test_sitting_responses", "sitting_id", $this->get_sitting_id());
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->privacy_mask(array (
			"sittingId" => $this->get_sitting_id(),
			"owner" => $this->get_owner()->json_assoc_condensed(),
			"testId" => $this->get_test_id(),
			"student" => $this->get_user()->json_assoc(),
			"timeframe" => $this->get_timeframe()->json_assoc(),
			"responsesCount" => $this->responses_count(),
			"score" => $this->get_score_json_assoc(),
			"message" => $this->get_message()
		), array (0 => "sittingId"), $privacy);
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		$assoc = $this->json_assoc($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["responses"] = self::json_array($this->responses());
		
		return $this->privacy_mask($assoc, $public_keys, $privacy);
	}
}

?>