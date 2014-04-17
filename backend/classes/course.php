<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Course extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function insert($lang_code_0, $lang_code_1, $name = null, $timeframe = null, $message = null, $public = false)
	{
		if (!Session::get()->get_user())
		{
			return static::set_error_description("Session user has not reauthenticated.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$languages_join = "languages AS languages_0 CROSS JOIN languages AS languages_1";
		
		$language_0_matches = sprintf("languages_0.lang_code = '%s'",
			$mysqli->escape_string($lang_code_0)
		);
		$language_1_matches = sprintf("languages_1.lang_code = '%s'",
			$mysqli->escape_string($lang_code_1)
		);
		$language_codes_match = "$language_0_matches AND $language_1_matches";
		
		$language_ids = "languages_0.lang_id AS lang_id_0, languages_1.lang_id AS lang_id_1";
		
		$name = ($name !== null && strlen($name) > 0)
			? "'".$mysqli->escape_string($name)."'"
			: "NULL";
		$open = !!$timeframe ? $timeframe->get_open() : "NULL";
		$close = !!$timeframe ? $timeframe->get_close() : "NULL";
		$message = $message !== null ? "'" . $mysqli->escape_string($message) . "'" : "NULL";
		$public = $public ? 1 : 0;
		
		$mysqli->query(sprintf("INSERT INTO courses (user_id, lang_id_0, lang_id_1, name, open, close, message, public) %s",
			"SELECT " . Session::get()->get_user()->get_user_id() . ", $language_ids, $name, $open, $close, $message, $public FROM $languages_join ON $language_codes_match"
		));
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Failed to insert course: " . $mysqli->error . ".");
		}
		
		if (!($course = self::select_by_id($mysqli->insert_id)))
		{
			return null;
		}
		
		$mysqli->query(sprintf("INSERT INTO course_instructors (course_id, user_id) VALUES (%d, %d)",
			$course->get_course_id(),
			Session::get()->get_user()->get_user_id()
		));
		
		Session::get()->get_user()->uncache_all();
		
		return $course;
	}
	
	public static function select_by_id($course_id)
	{
		return parent::select("courses", "course_id", $course_id);
	}
	
	/***    INSTANCE    ***/

	private $course_id = null;
	public function get_course_id()
	{
		return $this->course_id;
	}
	
	private $user_id = null;
	public function get_user_id()
	{
		return $this->user_id;
	}
	public function get_owner()
	{
		return User::select_by_id($this->get_user_id());
	}
	public function session_user_is_owner()
	{
		return !!Session::get() && !!Session::get()->get_user()
			&& Session::get()->get_user()->equals($this->get_owner());
	}
	
	public function uncache_instructors()
	{
		if (isset($this->instructors)) unset($this->instructors);
	}
	public function uncache_students()
	{
		if (isset($this->students)) unset($this->students);
	}
	public function uncache_units()
	{
		if (isset($this->units)) unset($this->units);
	}
	public function uncache_all()
	{
		$this->uncache_instructors();
		$this->uncache_students();
		$this->uncache_units();
	}
	
	private $name = null;
	public function get_course_name()
	{
		return $this->name;
	}
	public function set_course_name($name)
	{
		if (!self::update_this($this, "courses", array ("name" => $name), "course_id", $this->get_course_id()))
		{
			return null;
		}
		$this->name = $name;
		return $this;
	}
	
	private $lang_id_0 = null;
	public function get_lang_id_0()
	{
		return $this->lang_id_0;
	}
	public function get_lang_code_0()
	{
		return Dictionary::get_lang_code($this->get_lang_id_0());
	}
	
	private $lang_id_1 = null;
	public function get_lang_id_1()
	{
		return $this->lang_id_1;
	}
	public function get_lang_code_1()
	{
		return Dictionary::get_lang_code($this->get_lang_id_1());
	}
	
	private $public = null;
	public function get_public()
	{
		return !!$this->public;
	}
	public function set_public($public)
	{
		$public = $public ? 1 : 0;
		if (!self::update_this($this, "courses", array ("public" => $public), "course_id", $this->get_course_id()))
		{
			return null;
		}
		$this->public = $public;
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
			"courses",
			!!$timeframe
				? $timeframe->mysql_assignments()
				: array ("open" => "NULL", "close" => "NULL"),
			"course_id",
			$this->get_course_id(),
			true
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
	public function is_current()
	{
		return !$this->get_timeframe() || $this->get_timeframe()->is_current();
	}
	
	private $message;
	public function get_message()
	{
		return $this->message;
	}
	public function set_message($message)
	{
		if (!self::update_this($this, "courses", array ("message" => $message), "course_id", $this->get_course_id()))
		{
			return null;
		}
		$this->message = $message;
		return $this;
	}
	
	private $instructors;
	public function get_instructors()
	{
		$table = "course_instructors LEFT JOIN users USING (user_id)";
		return self::get_cached_collection($this->instructors, "User", $table, "course_id", $this->get_course_id());
	}
	public function session_user_is_instructor()
	{
		return !!Session::get() && $this->user_is_instructor(Session::get()->get_user());
	}
	public function user_is_instructor($user)
	{
		return !!$user && $user->in_array($this->get_instructors());
	}
	
	private $students;
	public function get_students()
	{
		$table = "course_students LEFT JOIN users USING (user_id)";
		return self::get_cached_collection($this->students, "User", $table, "course_id", $this->get_course_id());
	}
	public function session_user_is_student()
	{
		return !!Session::get() && $this->user_is_student(Session::get()->get_user());
	}
	public function user_is_student($user)
	{
		return !!$user && $user->in_array($this->get_students());
	}
	
	private $units;
	public function get_units()
	{
		$table = "course_students LEFT JOIN users USING (user_id)";
		return self::get_cached_collection($this->units, "Unit", "course_units", "course_id", $this->get_course_id(), "*", "ORDER BY num");
	}
	public function get_lists()
	{
		$lists = array ();
		foreach ($this->get_units() as $unit)
		{
			foreach ($unit->get_lists() as $list)
			{
				if (!in_array($list, $lists))
				{
					array_push($lists, $list);
				}
			}
		}
		return $lists;
	}
	public function get_tests()
	{
		$tests = array ();
		foreach ($this->get_units() as $unit)
		{
			foreach ($unit->get_tests() as $test)
			{
				array_push($tests, $test);
			}
		}
		return $tests;
	}
	
	private function __construct($course_id, $user_id, $lang_id_0, $lang_id_1, $name = null, $public = false, $open = null, $close = null, $message = null)
	{
		$this->course_id = intval($course_id, 10);
		$this->user_id = intval($user_id, 10);
		$this->lang_id_0 = $lang_id_0;
		$this->lang_id_1 = $lang_id_1;
		$this->name = !!$name && strlen($name) > 0 ? $name : null;
		$this->timeframe = !!$open && !!$close ? new Timeframe($open, $close) : null;
		$this->message = !!$message && strlen($message) > 0 ? $message : null;
		$this->public = !!$public;
		
		self::register($this->course_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"course_id",
			"user_id",
			"lang_id_0",
			"lang_id_1",
			"name",
			"public",
			"open",
			"close",
			"message"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["course_id"],
				$result_assoc["user_id"],
				$result_assoc["lang_id_0"],
				$result_assoc["lang_id_1"],
				$result_assoc["name"],
				$result_assoc["public"],
				$result_assoc["open"],
				$result_assoc["close"],
				$result_assoc["message"]
			)
			: null;
	}
	
	public function session_user_can_write()
	{
		return $this->session_user_is_instructor()
			|| $this->session_user_is_owner();
	}
	
	public function session_user_can_read()
	{
		return $this->session_user_can_write()
			|| $this->session_user_is_student();
	}
	
	public function delete()
	{
		foreach (array_merge(array ($this->get_owner()), $this->get_students(), $this->get_instructors()) as $user)
		{
			$user->uncache_all_courses();
		}
		foreach ($this->get_lists() as $list)
		{
			$list->uncache_courses();
		}
		return self::delete_this($this, "courses", "course_id", $this->get_course_id());
	}
	
	private function users_add(&$array, $table, $user)
	{
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO $table (course_id, user_id) VALUES (%d, %d)",
			$this->get_course_id(),
			$user->get_user_id()
		));
		
		if ($mysqli->error)
		{
			return static::set_error_description("Failed to add course user: " . $mysqli->error . ".");
		}
		
		if (isset($array)) array_push($array, $user);
		
		$user->uncache_all_courses();
		
		return $this;
	}
	
	public function instructors_add($user)
	{
		if (!$this->session_user_is_owner())
		{
			return static::set_error_description("Session user is not course owner.");
		}
		
		return $this->users_add($this->instructors, "course_instructors", $user);
	}
	
	public function students_add($user)
	{
		return $this->users_add($this->students, "course_students", $user);
	}
	
	private function users_remove(&$array, $table, $user)
	{
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit course.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM $table WHERE course_id = %d AND user_id = %d",
			$this->get_course_id(),
			$user->get_user_id()
		));
		
		if ($mysqli->error)
		{
			return static::set_error_description("Failed to remove course user: " . $mysqli->error . ".");
		}
		
		if (isset($array)) array_drop($array, $user);
		
		$user->uncache_all_courses();
		
		return $this;
	}
	
	public function instructors_remove($user)
	{
		if (!$this->session_user_is_owner())
		{
			return static::set_error_description("Session user is not course owner.");
		}
		return $this->users_remove($this->instructors, "course_instructors", $user);
	}
	
	public function students_remove($user)
	{
		return $this->users_remove($this->students, "course_students", $user);
	}
	
	protected function privacy()
	{
		if (!$this->session_user_can_write()
				&& ($this->session_user_can_read() && !$this->is_current()))
		{
			return true;
		}
		else
		{
			return parent::privacy();
		}
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->privacy_mask(array (
			"courseId" => $this->get_course_id(),
			"name" => $this->get_course_name(),
			"languageKnown" => Language::select_by_id($this->get_lang_id_0())->json_assoc(),
			"languageUnknown" => Language::select_by_id($this->get_lang_id_1())->json_assoc(),
			"owner" => $this->get_owner()->json_assoc(),
			"isPublic" => $this->get_public(),
			"timeframe" => !!$this->get_timeframe() ? $this->get_timeframe()->json_assoc() : null,
			"instructorsCount" => count($this->get_instructors()),
			"studentsCount" => count($this->get_students()),
			"unitsCount" => count($this->get_units()),
			"listsCount" => count($this->get_lists()),
			"testsCount" => count($this->get_tests()),
			"message" => $this->get_message()
		), array (0 => "courseId"), $privacy);
	}
	
	public function detailed_json_assoc($privacy = null)
	{
		$assoc = $this->json_assoc($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["instructors"] = self::array_for_json($this->get_instructors());
		$assoc["students"] = self::array_for_json($this->get_students());
		$assoc["units"] = self::array_for_json($this->get_units());
		$assoc["lists"] = self::array_for_json($this->get_lists());
		$assoc["tests"] = self::array_for_json($this->get_tests());
		
		return $this->privacy_mask($assoc, $public_keys, $privacy);
	}
}

?>
