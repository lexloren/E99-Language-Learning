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
		
		return Connection::transact(
			function () use ($test_entry_id, $contents)
			{
				$failure_message = "Failed to insert test sitting response";
				
				if (!($test = Test::select_by_test_entry_id(($test_entry_id = intval($test_entry_id, 10)))))
				{
					return static::errors_push("$failure_message: " . Test::errors_unset());
				}
				
				if (!($sitting = $test->get_sitting_for_user(Session::get()->get_user())))
				{
					return static::errors_push("$failure_message: Test returned no sitting for session user.");
				}
				
				if (!$sitting->session_user_can_execute())
				{
					return static::errors_push("$failure_message: Session user cannot execute sitting.");
				}
				
				$current_question = $sitting->next_json_assoc();
				
				if ($current_question["testEntryId"] !== $test_entry_id)
				{
					return static::errors_push("$failure_message: Session user cannot respond to test_entry_id = $test_entry_id because the response is out of order.");
				}
				
				$test_entries = $test->entries();
				if (!($test_entry = $test_entries[$test_entry_id]))
				{
					return static::errors_push("$failure_message: Test returned no entry for test_entry_id = $test_entry_id.");
				}
				
				if (!($pattern = Pattern::select_by_test_id_entry_id_contents($test->get_test_id(), $test_entry->get_entry_id(), $contents)))
				{
					return static::errors_push("$failure_message: " . Pattern::errors_unset());
				}
				
				Connection::query(sprintf("INSERT INTO course_unit_test_sitting_responses (sitting_id, pattern_id, timestamp) VALUES (%d, %d, %d)",
					$sitting->get_sitting_id(),
					$pattern->get_pattern_id(),
					($now = time())
				));
				
				if (!!($error = Connection::query_error_clear()))
				{
					return static::errors_push("Failed to insert test sitting response: $error.");
				}
				
				$response_id = Connection::insert_id();
				
				$sitting->uncache_entries_remaining();
				
				if (!($current_question["entriesRemainingCount"]))
				{
					Connection::query(sprintf("UPDATE course_unit_test_sittings SET stop = %d WHERE sitting_id = %d",
						$now,
						$sitting->get_sitting_id()
					));
				}
				
				return self::select_by_id($response_id);
			}
		);
	}
	
	public static function select_by_id($response_id)
	{
		return parent::select("course_unit_test_sitting_responses", "response_id", $response_id);
	}
	
	/***    INSTANCE    ***/

	private $sitting_id = null;
	public function get_sitting_id()
	{
		return $this->sitting_id;
	}
	public function get_sitting()
	{
		return Sitting::select_by_id($this->get_sitting_id());
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
		
		return $this->user_can_write() || $user->equals($this->get_user());
	}
	
	public function delete()
	{
		$this->get_sitting()->uncache_responses();
		return self::delete_this($this, "course_unit_test_sitting_responses", "response_id", $this->get_response_id());
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->privacy_mask(array (
			"responseId" => $this->get_response_id(),
			"owner" => $this->get_owner()->json_assoc_condensed(),
			"testId" => $this->get_test_id(),
			"student" => $this->get_user()->json_assoc(),
			"timestamp" => $this->get_timestamp(),
			"pattern" => $this->get_pattern()->json_assoc()
		), array (0 => "responseId"), $privacy);
	}
}

?>