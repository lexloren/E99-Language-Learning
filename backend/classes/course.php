<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Course extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	public static function insert($lang_code_0, $lang_code_1, $name = null, $timeframe = null, $message = null, $public = false)
	{
		if (!Session::get()->get_user())
		{
			return static::errors_push("Session user has not reauthenticated.", ErrorReporter::ERRCODE_AUTHENTICATION);
		}
		
		return Connection::transact(
			function () use ($lang_code_0, $lang_code_1, $name, $timeframe, $message, $public)
			{
				$languages_join = "languages AS languages_0 CROSS JOIN languages AS languages_1";
				
				$language_0_matches = sprintf("languages_0.lang_code = '%s'",
					Connection::escape($lang_code_0)
				);
				$language_1_matches = sprintf("languages_1.lang_code = '%s'",
					Connection::escape($lang_code_1)
				);
				$language_codes_match = "$language_0_matches AND $language_1_matches";
				
				$language_ids = "languages_0.lang_id AS lang_id_0, languages_1.lang_id AS lang_id_1";
				
				$name = ($name !== null && strlen($name) > 0)
					? "'".Connection::escape($name)."'"
					: "NULL";
				$open = !!$timeframe ? $timeframe->get_open() : "NULL";
				$close = !!$timeframe ? $timeframe->get_close() : "NULL";
				$message = $message !== null ? "'" . Connection::escape($message) . "'" : "NULL";
				$public = $public ? 1 : 0;
				
				Connection::query(sprintf("INSERT INTO courses (user_id, lang_id_0, lang_id_1, name, open, close, message, public) %s",
					"SELECT " . Session::get()->get_user()->get_user_id() . ", $language_ids, $name, $open, $close, $message, $public FROM $languages_join ON $language_codes_match"
				));
				
				if (!!($error = Connection::query_error_clear()))
				{
					return static::errors_push("Failed to insert course: $error.", ErrorReporter::ERRCODE_DATABASE);
				}
				
				if (!($course = self::select_by_id(Connection::insert_id())))
				{
					return null;
				}
				
				Connection::query(sprintf("INSERT INTO course_instructors (course_id, user_id) VALUES (%d, %d)",
					$course->get_course_id(),
					Session::get()->get_user()->get_user_id()
				));
				
				if (!!($error = Connection::query_error_clear()))
				{
					return static::errors_push("Failed to insert course instructor: $error.", ErrorReporter::ERRCODE_DATABASE);
				}
				
				Session::get()->get_user()->uncache_all();
				
				return $course;
			}
		);
	}
	
	public static function select_by_id($course_id)
	{
		return parent::select("courses", "course_id", $course_id);
	}
	
	private static function courses_from_mysql_result($result)
	{
		$courses = array ();
		while (($result_assoc = $result->fetch_assoc()))
		{
			if (($course = Course::from_mysql_result_assoc($result_assoc)))
			{
				if ($course->session_user_can_read())
				{
					$courses[$course->get_course_id()] = $course;
				}
			}
			else return null;
		}
		return $courses;
	}

	public static function find_by_entry_ids($entry_ids)
	{
		foreach ($entry_ids as &$entry_id)
		{
			$entry_id = intval($entry_id, 10);
		}
		
		$course_units = "(courses CROSS JOIN course_units USING (course_id))";
		$unit_lists = "($course_units CROSS JOIN course_unit_lists USING (unit_id))";
		$course_lists = "($unit_lists CROSS JOIN lists ON course_unit_lists.list_id = lists.list_id AND (lists.user_id = courses.user_id OR lists.public))";
		$course_user_entries = "($course_lists CROSS JOIN user_entries USING (user_entry_id))";
		
		$result = Connection::query(sprintf("SELECT courses.* FROM $course_user_entries WHERE entry_id IN (%s) GROUP BY course_id",
			implode(",", $entry_ids)
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to find course(s) by entry_ids: $error.");
		}
		
		return self::courses_from_mysql_result($result);
	}
	
	public static function find_by_entry_query($query, $lang_codes)
	{
		if (($entries = Dictionary::query($query, $lang_codes, array ("num" => 0, "size" => 100), false)))
		{
			$entry_ids = array ();
			foreach ($entries as $entry) array_push($entry_ids, $entry->get_entry_id());
			return self::find_by_entry_ids($entry_ids);
		}
		
		return static::errors_push("Failed to find course(s) by entry ids: " . Dictionary::errors_unset());
	}
	
	public static function find_by_user_query($query)
	{
		if (($users = User::find($query)))
		{
			$user_ids = array ();
			foreach ($users as $user) array_push($user_ids, $user->get_user_id());
			return self::find_by_user_ids($user_ids);
		}
		
		return static::errors_push("Failed to find course(s) by user handles: " . User::errors_unset());
	}

	public static function find_by_user_ids($user_ids)
	{
		foreach ($user_ids as &$user_id)
		{
			$user_id = intval($user_id, 10);
		}
		
		$user_ids_string = implode(",", $user_ids);
		
		//  Don't include this information because student identities should be private
		//  $courses_studied = "(SELECT course_id FROM courses CROSS JOIN course_students USING (course_id) WHERE students.user_id IN ($user_ids_string)) AS courses_studied";
		
		$course_ids_instructed = "SELECT course_id FROM courses CROSS JOIN course_instructors USING (course_id) WHERE course_instructors.user_id IN ($user_ids_string)";
		
		$result = Connection::query("SELECT * FROM courses WHERE (user_id IN ($user_ids_string) OR course_id IN ($course_ids_instructed)) AND courses.public GROUP BY course_id");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to find course(s) by user_ids: $error.");
		}
		
		return self::courses_from_mysql_result($result);
	}

	public static function find_by_languages($lang_codes)
	{
		$lang_ids = array ();
		foreach ($lang_codes as $lang_code)
		{
			if (($lang = Language::select_by_code($lang_code)) && !in_array(($lang_id = $lang->get_lang_id()), $lang_ids))
			{
				array_push($lang_ids, $lang_id);
			}
		}
		
		$lang_ids_string = implode(",", $lang_ids);
		
		$result = Connection::query("SELECT * FROM courses WHERE (lang_id_0 IN ($lang_ids_string) AND lang_id_1 IN ($lang_ids_string)) GROUP BY course_id");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to find course(s) by languages: $error.");
		}
		
		return self::courses_from_mysql_result($result);
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
	public function uncache_researchers()
	{
		if (isset($this->researchers)) unset($this->researchers);
	}
	public function uncache_units()
	{
		if (isset($this->units)) unset($this->units);
	}
	public function uncache_all()
	{
		$this->uncache_instructors();
		$this->uncache_students();
		$this->uncache_researchers();
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
	public function instructors()
	{
		$table = "course_instructors LEFT JOIN users USING (user_id)";
		return self::cache($this->instructors, "User", $table, "course_id", $this->get_course_id());
	}
	public function session_user_is_instructor()
	{
		return !!Session::get() && $this->user_is_instructor(Session::get()->get_user());
	}
	public function user_is_instructor($user)
	{
		return !!$user && $user->in($this->instructors());
	}
	
	private $researchers;
	public function researchers()
	{
		$table = "course_researchers LEFT JOIN users USING (user_id)";
		return self::cache($this->researchers, "User", $table, "course_id", $this->get_course_id());
	}
	public function session_user_is_researcher()
	{
		return !!Session::get() && $this->user_is_researcher(Session::get()->get_user());
	}
	public function user_is_researcher($user)
	{
		return !!$user && $user->in($this->researchers());
	}
	
	private $students;
	public function students()
	{
		$table = "course_students LEFT JOIN users USING (user_id)";
		return self::cache($this->students, "User", $table, "course_id", $this->get_course_id());
	}
	public function session_user_is_student()
	{
		return !!Session::get() && $this->user_is_student(Session::get()->get_user());
	}
	public function user_is_student($user)
	{
		return !!$user && $user->in($this->students());
	}
	
	private $units;
	public function units()
	{
		$table = "course_students LEFT JOIN users USING (user_id)";
		return self::cache($this->units, "Unit", "course_units", "course_id", $this->get_course_id(), "*", "ORDER BY num");
	}
	public function lists()
	{
		$lists = array ();
		foreach ($this->units() as $unit)
		{
			foreach ($unit->lists() as $list)
			{
				if (!in_array($list, $lists))
				{
					array_push($lists, $list);
				}
			}
		}
		return $lists;
	}
	public function tests()
	{
		$tests = array ();
		foreach ($this->units() as $unit)
		{
			foreach ($unit->tests() as $test)
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
			|| $this->session_user_is_student()
			|| $this->get_public();
	}
	
	public function delete()
	{
		foreach (array_merge(array ($this->get_owner()), $this->students(), $this->instructors()) as $user)
		{
			$user->uncache_all_courses();
		}
		foreach ($this->lists() as $list)
		{
			$list->uncache_courses();
		}
		return self::delete_this($this, "courses", "course_id", $this->get_course_id());
	}
	
	private function users_add(&$array, $table, $user)
	{
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit course.");
		}
		
		Connection::query(sprintf("INSERT INTO $table (course_id, user_id) VALUES (%d, %d)",
			$this->get_course_id(),
			$user->get_user_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to add course user: $error.");
		}
		
		if (isset($array)) array_push($array, $user);
		
		$user->uncache_all_courses();
		
		return $this;
	}
	
	public function instructors_add($user)
	{
		if (!$this->session_user_is_owner())
		{
			return static::errors_push("Session user is not course owner.");
		}
		
		return $this->users_add($this->instructors, "course_instructors", $user);
	}
	
	public function students_add($user)
	{
		return $this->users_add($this->students, "course_students", $user);
	}
	
	public function researchers_add($user)
	{
		if (!$this->session_user_is_owner())
		{
			return static::errors_push("Session user is not course owner.");
		}
		
		return $this->users_add($this->researchers, "course_researchers", $user);
	}
	
	private function users_remove(&$array, $table, $user)
	{
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit course.");
		}
		
		Connection::query(sprintf("DELETE FROM $table WHERE course_id = %d AND user_id = %d",
			$this->get_course_id(),
			$user->get_user_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to remove course user: $error.");
		}
		
		if (isset($array)) array_drop($array, $user);
		
		$user->uncache_all_courses();
		
		return $this;
	}
	
	public function instructors_remove($user)
	{
		if (!$this->session_user_is_owner())
		{
			return static::errors_push("Session user is not course owner.");
		}
		return $this->users_remove($this->instructors, "course_instructors", $user);
	}
	
	public function researchers_remove($user)
	{
		if (!$this->session_user_is_owner())
		{
			return static::errors_push("Session user is not course owner.");
		}
		return $this->users_remove($this->researchers, "course_researchers", $user);
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
	
	public function instructors_count()
	{
		return self::count("course_instructors", "course_id", $this->get_course_id());
	}
	
	public function students_count()
	{
		return self::count("course_students", "course_id", $this->get_course_id());
	}
	
	public function units_count()
	{
		return self::count("course_units", "course_id", $this->get_course_id());
	}
	
	public function lists_count()
	{
		$course_units = "courses CROSS JOIN course_units USING (course_id)";
		$unit_lists = "($course_units) CROSS JOIN course_unit_lists USING (unit_id)";
		return self::count($unit_lists, "course_id", $this->get_course_id());
	}
	
	public function tests_count()
	{
		$course_units = "courses CROSS JOIN course_units USING (course_id)";
		$unit_tests = "($course_units) CROSS JOIN course_unit_tests USING (unit_id)";
		return self::count($unit_tests, "course_id", $this->get_course_id());
	}
	
	public function researchers_count()
	{
		return self::count("course_researchers", "course_id", $this->get_course_id());
	}
	
	public function json_assoc($privacy = null)
	{
		return $this->privacy_mask(array (
			"courseId" => $this->get_course_id(),
			"name" => $this->get_course_name(),
			"languageKnown" => Language::select_by_id($this->get_lang_id_0())->json_assoc(),
			"languageUnknown" => Language::select_by_id($this->get_lang_id_1())->json_assoc(),
			"owner" => $this->get_owner()->json_assoc_condensed(),
			"isPublic" => $this->get_public(),
			"timeframe" => !!$this->get_timeframe() ? $this->get_timeframe()->json_assoc() : null,
			"instructorsCount" => $this->instructors_count(),
			"studentsCount" => $this->students_count(),
			"researchersCount" => $this->researchers_count(),
			"unitsCount" => $this->units_count(),
			"listsCount" => $this->lists_count(),
			"testsCount" => $this->tests_count(),
			"message" => $this->get_message()
		), array (0 => "courseId"), $privacy);
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		$assoc = $this->json_assoc($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["instructors"] = self::json_array($this->instructors());
		$assoc["students"] = self::json_array($this->students());
		$assoc["researchers"] = self::json_array($this->researchers());
		$assoc["units"] = self::json_array($this->units());
		$assoc["lists"] = self::json_array($this->lists());
		$assoc["tests"] = self::json_array($this->tests());
		
		return $this->privacy_mask($assoc, $public_keys, $privacy);
	}
}

?>
