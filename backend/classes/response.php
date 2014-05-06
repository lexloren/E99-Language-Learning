<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Response extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	public static function insert($test_entry_id, $contents)
	{
		if (!($session_user = Session::get()->get_user())) return static::errors_push("Session user has not reauthenticated.");
		
		$test_entry_id = intval($test_entry_id, 10);
		
		$error_message;
		$error_code;
		
		return ($result = Connection::transact(
			function () use ($test_entry_id, $contents, $session_user, &$error_message, &$error_code)
			{
				$failure_message = "Failed to insert test sitting response";
				
				if (!($test = Test::select_by_test_entry_id(($test_entry_id = intval($test_entry_id, 10)))))
				{
					$error_message = "$failure_message: " . Test::errors_unset();
					return null;
				}
				
				if (!($sitting = $test->get_sitting_for_user(Session::get()->get_user())))
				{
					$error_message = "$failure_message: Test returned no sitting for session user.";
					return null;
				}
				
				if (count(($reasons = $sitting->user_cannot_respond_reasons($session_user))) > 0)
				{
					$error_message = "$failure_message: Session user cannot execute test sitting " . implode(" and ", $reasons) . ".";
					return null;
				}
				
				$entries_remaining = $sitting->entries_remaining();
				$test_entry = $entries_remaining[0];
				
				if ($test->get_test_entry_id_for_entry($test_entry) !== $test_entry_id)
				{
					$error_message = "$failure_message: Session user cannot respond to test_entry_id = $test_entry_id because that test_entry_id mismatches that of the current test entry for this test sitting.";
					return null;
				}
				
				if (!($pattern = Pattern::insert($test->get_test_id(), $test_entry->get_entry_id(), $contents)))
				{
					$error_message = "$failure_message: " . Pattern::errors_unset();
					return null;
				}
				
				Connection::query(sprintf("INSERT INTO course_unit_test_sitting_responses (sitting_id, pattern_id, timestamp) VALUES (%d, %d, %d)",
					$sitting->get_sitting_id(),
					$pattern->get_pattern_id(),
					($now = time())
				));
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "$failure_message: $error.";
					return null;
				}
				
				$response_id = Connection::query_insert_id();
				
				$sitting->uncache_entries_remaining();
				
				if (!$sitting->entries_remaining())
				{
					Connection::query(sprintf("UPDATE course_unit_test_sittings SET stop = %d WHERE sitting_id = %d",
						$now,
						$sitting->get_sitting_id()
					));
					
					if (!!($error = Connection::query_error_clear()))
					{
						$error_message = "Failed to close test sitting: $error.";
						return null;
					}
				}
				
				return Response::select_by_id($response_id);
			}
		)) ? $result : static::errors_push($error_message);
	}
	
	public static function select_by_id($response_id)
	{
		return parent::select("course_unit_test_sitting_responses", "response_id", $response_id);
	}
	
	public static function select_all_for_test_id($test_id)
	{
		$test_id = intval($test_id, 10);
		$table = "course_unit_test_sitting_responses CROSS JOIN course_unit_test_sittings USING (sitting_id)";
		
		$result = Connection::query("SELECT * FROM $table WHERE test_id = $test_id");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to select all responses for test: $error.");
		}
		
		$responses = array ();
		while (($result_assoc = $result->fetch_assoc()))
		{
			array_push($responses, self::from_mysql_result_assoc($result_assoc));
		}
		
		return $responses;
	}
	
	public static function select_all_for_test_entry_id($test_entry_id)
	{
		$test_entry_id = intval($test_entry_id, 10);
		$table = "course_unit_test_sitting_responses CROSS JOIN course_unit_test_entry_patterns USING (pattern_id)";
		
		$result = Connection::query("SELECT * FROM $table WHERE test_entry_id = $test_entry_id");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to select all responses for test entry: $error.");
		}
		
		$responses = array ();
		while (($result_assoc = $result->fetch_assoc()))
		{
			array_push($responses, self::from_mysql_result_assoc($result_assoc));
		}
		
		return $responses;
	}
	
	/***    INSTANCE    ***/
	
	private $response_id = null;
	public function get_response_id()
	{
		return $this->response_id;
	}

	private $sitting_id = null;
	public function get_sitting_id()
	{
		return $this->sitting_id;
	}
	public function get_sitting()
	{
		return Sitting::select_by_id($this->get_sitting_id());
	}
	public function get_container()
	{
		return $this->get_sitting();
	}
	public function get_student_id()
	{
		return $this->get_sitting()->get_student_id();
	}
	public function get_user()
	{
		return $this->get_sitting()->get_user();
	}
	public function get_user_id()
	{
		return $this->get_sitting()->get_user_id();
	}
	public function get_course()
	{
		return $this->get_sitting()->get_course();
	}
	public function get_test()
	{
		return $this->get_sitting()->get_test();
	}
	public function get_test_id()
	{
		return $this->get_test()->get_test_id();
	}
	
	private $pattern_id = null;
	public function get_pattern_id()
	{
		return $this->pattern_id;
	}
	public function get_pattern()
	{
		return $this->pattern_id
			? Pattern::select_by_id($this->pattern_id)
			: null;
	}
	public function get_score()
	{
		return $this->get_pattern()
			? $this->get_pattern()->get_score()
			: null;
	}
	public function set_score($score)
	{
		if (!$this->get_pattern())
		{
			return static::errors_push("Response failed to set score because response failed to get pattern!");
		}
		
		if (!$this->get_pattern()->set_score($score))
		{
			return static::errors_push("Response failed to set score: " . Pattern::errors_unset());
		}
		
		return $this;
	}
	
	public function get_message()
	{
		return $this->get_pattern()->get_message();
	}
	public function set_message($message)
	{
		return $this->get_pattern()->set_message($message);
	}
	
	private $timestamp = null;
	public function get_timestamp()
	{
		return $this->timestamp;
	}
	
	private function __construct($response_id, $sitting_id, $pattern_id, $timestamp)
	{
		$this->response_id = intval($response_id, 10);
		$this->sitting_id = intval($sitting_id, 10);
		$this->pattern_id = intval($pattern_id, 10);
		$this->timestamp = intval($timestamp, 10);
		
		self::register($this->response_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"response_id",
			"sitting_id",
			"pattern_id",
			"timestamp"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["response_id"],
				$result_assoc["sitting_id"],
				$result_assoc["pattern_id"],
				$result_assoc["timestamp"]
			)
			: null;
	}
	
	public function user_can_read($user)
	{
		if (!$user) return false;
		
		return $this->user_can_write($user) || $user->equals($this->get_user());
	}
	
	public function delete()
	{
		$this->get_sitting()->uncache_responses();
		return self::delete_this($this, "course_unit_test_sitting_responses", "response_id", $this->get_response_id());
	}
	
	public function entry_json_assoc()
	{
		$entry = $this->get_pattern()->get_entry();
		return $this->get_test()->entry_json_assoc($entry, true);
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->prune(array (
			"responseId" => $this->get_response_id(),
			"owner" => $this->get_owner()->json_assoc_condensed(),
			"testId" => $this->get_test_id(),
			"entry" => $this->entry_json_assoc(),
			"student" => $this->get_user()->json_assoc_condensed(),
			"timestamp" => $this->get_timestamp(),
			"pattern" => $this->get_pattern()->json_assoc(!$this->get_test()->get_disclosed())
		), array (0 => "responseId"), $privacy);
	}
}

?>