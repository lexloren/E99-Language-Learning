<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Result extends CourseComponent
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function insert($sitting_id, $user_entry_id, $word_0, $word_1, $word_1_pronun)
	{
		if (!($session_user = Session::get()->get_user())) return static::set_error_description("Session user has not reauthenticated.");
		
		if (!($sitting = Sitting::select_by_id(($sitting_id = intval($sitting_id, 10)))))
		{
			return static::set_error_description("Failed to insert sitting result: " . Test::unset_error_description());
		}
		
		if (!$test->session_user_can_execute())
		{
			return static::set_error_description("Session user cannot execute test.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO course_unit_test_sittings (test_id, student_id, start) SELECT %d, student_id, CURRENT_TIMESTAMP FROM course_students WHERE course_id = %d AND user_id = %d",
			$test->get_test_id(),
			$test->get_course_id(),
			$session_user->get_user_id()
		));
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Failed to insert test sitting: " . $mysqli->error . ".");
		}
		
		$test->uncache_sittings();
		
		return self::select_by_id($mysqli->insert_id);
	}
	
	public static function select_by_id($sitting_id)
	{
		return parent::select("course_unit_test_sittings", "sitting_id", $sitting_id);
	}
	
	/***    INSTANCE    ***/

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
			$mysqli = Connection::get_shared_instance();
			$result = $mysqli->query(sprintf("SELECT * FROM course_students WHERE student_id = %d",
				$this->get_student_id()
			));
			
			if (!!$mysqli->error) return self::set_error_description("Failed to select sitting user: " . $mysqli->error . ".");
			
			if (!$result || $result->num_rows != 1 || !($result_assoc = $result->fetch_assoc()))
			{
				return self::set_error_description("Failed to select sitting user: No user selected for student_id = " . $this->get_student_id() . ".");
			}
			
			if (!($this->user = User::select_by_id($result_assoc["user_id"])))
			{
				unset($this->user);
				return self::set_error_description("Failed to select sitting user: " . User::unset_error_description());
			}
		}
		
		return $this->user_id;
	}
	public function get_user_id()
	{
		if (!$this->get_user()) return null;
		
		return $this->get_user()->get_user_id();
	}
	
	public function uncache_results()
	{
		if (isset($this->results)) unset($this->results);
	}
	public function uncache_all()
	{
		$this->uncache_results();
	}
	
	private $results;
	public function get_results()
	{
		return self::get_cached_collection($this->results, "Result", "course_unit_test_sitting_results", "sitting_id", $this->get_sitting_id());
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
			? new Sitting(
				$result_assoc["sitting_id"],
				$result_assoc["test_id"],
				$result_assoc["student_id"],
				$result_assoc["start"],
				$result_assoc["stop"],
				$result_assoc["message"]
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
		$this->get_test()->uncache_sittings();
		return self::delete_this($this, "course_unit_test_sittings", "sitting_id", $this->get_sitting_id());
	}
	
	public function assoc_for_json($privacy = null)
	{
		return $this->privacy_mask(array (
			"sittingId" => $this->get_sitting_id(),
			"owner" => $this->get_owner()->assoc_for_json(),
			"testId" => $this->get_test_id(),
			"userId" => $this->get_user_id(),
			"timeframe" => $this->get_timeframe()->assoc_for_json(),
			"results" => self::array_for_json($this->get_results()),
			"message" => $this->get_message()
		), array (0 => "sittingId"), $privacy);
	}
}

?>