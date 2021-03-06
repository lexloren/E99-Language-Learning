<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class EntryList extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	public static function insert($name = null)
	{
		if (!Session::get()->get_user())
		{
			return static::errors_push("Session user has not reauthenticated.");
		}
		
		Connection::query(sprintf("INSERT INTO lists (user_id, name) VALUES (%d, '%s')",
			Session::get()->get_user()->get_user_id(),
			Connection::escape($name)
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to insert list: $error.");
		}
		
		Session::get()->get_user()->uncache_lists();
		
		return self::select_by_id(Connection::query_insert_id());
	}
	
	private static function lists_from_mysql_result($result)
	{
		$lists = array ();
		while (($result_assoc = $result->fetch_assoc()))
		{
			if (($list = self::from_mysql_result_assoc($result_assoc)))
			{
				if ($list->session_user_can_read())
				{
					$lists[$list->get_list_id()] = $list;
				}
			}
			else return null;
		}
		return $lists;
	}
	
	public static function find_by_entry_ids($entry_ids)
	{
		foreach ($entry_ids as &$entry_id)
		{
			$entry_id = intval($entry_id, 10);
		}
		
		$entry_ids_string = implode(",", $entry_ids);
		
		$result = Connection::query("SELECT lists.* FROM (lists CROSS JOIN list_entries USING (list_id)) CROSS JOIN user_entries USING (user_entry_id) WHERE entry_id IN ($entry_ids_string) GROUP BY list_id");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to find list(s) by entry_ids: $error.");
		}
		
		return self::lists_from_mysql_result($result);
	}
	
	public static function find_by_entry_query($query, $lang_codes)
	{
		if (($entries = Dictionary::query($query, $lang_codes, array ("num" => 0, "size" => 100), false)))
		{
			$entry_ids = array ();
			foreach ($entries as $entry) array_push($entry_ids, $entry->get_entry_id());
			return self::find_by_entry_ids($entry_ids);
		}
		
		return static::errors_push("Failed to find list(s) by entry ids: " . Dictionary::errors_unset());
	}
	
	public static function find_by_user_query($query)
	{
		if (($users = User::find($query)))
		{
			$user_ids = array ();
			foreach ($users as $user) array_push($user_ids, $user->get_user_id());
			return self::find_by_user_ids($user_ids);
		}
		
		return static::errors_push("Failed to find list(s) by user handles: " . User::errors_unset());
	}
	
	public static function find_by_user_ids($user_ids)
	{
		foreach ($user_ids as &$user_id)
		{
			$user_id = intval($user_id, 10);
		}
		
		$user_ids_string = implode(",", $user_ids);
		
		$result = Connection::query("SELECT * FROM lists WHERE user_id IN ($user_ids_string) GROUP BY list_id");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to find list(s) by user_ids: $error.");
		}
		
		return self::lists_from_mysql_result($result);
	}
	
	public static function select_by_id($list_id)
	{
		return parent::select("lists", "list_id", $list_id);
	}
	
	/***    INSTANCE    ***/

	private $list_id;
	public function get_list_id()
	{
		return $this->list_id;
	}
	
	private $user_id;
	public function get_user_id()
	{
		return $this->user_id;
	}
	public function get_owner()
	{
		return User::select_by_id($this->get_user_id());
	}
	
	private $name;
	public function get_name()
	{
		return $this->name;
	}
	public function set_name($name)
	{
		if (strlen($name) === 0) $name = null;
		if (!self::update_this($this, "lists", array ("name" => $name), "list_id", $this->get_list_id()))
		{
			return null;
		}
		$this->name = $name;
		return $this;
	}
	
	private $public;
	public function get_public()
	{
		return !!$this->public;
	}
	public function set_public($public)
	{
		return static::errors_push("List.set_public() not yet implemented.");
	}
	
	public function uncache_entries()
	{
		if (isset($this->entries)) unset($this->entries);
	}
	public function uncache_courses()
	{
		if (isset($this->courses)) unset($this->courses);
	}
	public function uncache_all()
	{
		$this->uncache_entries();
		$this->uncache_courses();
	}
	
	private $entries;
	public function entries()
	{
		$user_entries = sprintf("SELECT * FROM user_entries LEFT JOIN (SELECT entry_id, %s FROM %s) AS reference USING (entry_id) WHERE user_id = %d",
			Dictionary::language_code_columns(),
			Dictionary::join(),
			$this->get_user_id()
		);
		
		$table = "list_entries LEFT JOIN ($user_entries) AS user_entries USING (user_entry_id)";
		return self::cache($this->entries, "UserEntry", $table, "list_id", $this->get_list_id());
	}
	
	private $courses;
	public function courses()
	{
		if (!isset($this->courses))
		{
			$table = "(course_unit_lists LEFT JOIN course_units USING (unit_id)) LEFT JOIN courses USING (course_id)";
			if (self::cache($this->courses, "Course", $table, "list_id", $this->get_list_id()))
			{
				$courses_unique = array ();
				foreach ($this->courses as $course)
				{
					if (!in_array($course, $courses_unique)) array_push($courses_unique, $course);
				}
				$this->courses = $courses_unique;
			}
			else
			{
				return null;
			}
		}
		return $this->courses;
	}
	
	private function __construct($list_id, $user_id, $name = null, $public = false)
	{
		$this->list_id = intval($list_id, 10);
		$this->user_id = intval($user_id, 10);
		$this->name = $name;
		$this->public = !!$public;
		
		self::register($this->list_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"list_id",
			"user_id",
			"name",
			"public"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["list_id"],
				$result_assoc["user_id"],
				!!$result_assoc["name"] && strlen($result_assoc["name"]) > 0 ? $result_assoc["name"] : null,
				$result_assoc["public"]
			)
			: null;
	}
	
	public function delete()
	{
		$this->get_owner()->uncache_lists();
		//  Don't need to uncache course_lists() because key constraints prevent deleting lists needed by courses
		return self::delete_this($this, "lists", "list_id", $this->get_list_id());
	}
	
	public function entries_count()
	{
		return self::count("list_entries", "list_id", $this->get_list_id());
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function entries_add($entry_to_add, $hint = null, $ignoring_permissions = false)
	{
		if (!$entry_to_add)
		{
			return static::errors_push("List cannot add null entry.");
		}
		
		if (!$ignoring_permissions && !$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit list.");
		}
		
		if (!($entry_added = $entry_to_add->copy_for_user($this->get_owner(), $hint)))
		{
			return static::errors_push("List failed to add entry: " . Entry::errors_unset());
		}
		
		//  Insert into list_entries for $this->list_id and $entry->entry_id
		//      If this entry already exists in the list, then ignore the error
		Connection::query(sprintf("INSERT INTO list_entries (list_id, user_entry_id) VALUES (%d, %d) ON DUPLICATE KEY UPDATE list_id = list_id",
			$this->list_id,
			$entry_added->get_user_entry_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("List failed to insert entry: $error.", ErrorReporter::ERRCODE_DATABASE);
		}
		
		$entry_added->uncache_lists();
		
		return $this;
	}
	
	public function entries_add_from_list($other, $ignoring_permissions = false)
	{
		if ($other == $this) return static::errors_push("List cannot add entries from itself.");
		
		$list = $this;
		
		return Connection::transact(
			function () use ($list, $other, $ignoring_permissions)
			{
				foreach ($other->entries() as $entry)
				{
					if (!$list->entries_add($entry, $other, $ignoring_permissions)) return null;
				}
				
				return $list;
			}
		);
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function entries_remove($entry)
	{
		if (!$entry)
		{
			return static::errors_push("List cannot remove null entry.");
		}
		
		if (!$this->session_user_can_write())
		{
			return static::errors_push("Session user cannot edit list.");
		}
		
		if (!($entry = $entry->copy_for_user($this->get_owner(), $this)))
		{
			return static::errors_push("List failed to remove entry: " . Entry::errors_unset());
		}
		
		Connection::query(sprintf("DELETE FROM list_entries WHERE list_id = %d AND user_entry_id = %d",
			$this->get_list_id(),
			$entry->get_user_entry_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("List failed to remove entry: $error.");
		}
		
		if (isset($this->entries)) array_drop($this->entries, $entry);
		
		return $this;
	}
	
	//  Copies this list, setting the copy's owner to some other user
	//      Returns the copy
	public function copy_for_session_user()
	{
		if (!Session::get() || !($session_user = Session::get()->get_user()))
		{
			return static::errors_push("Session user has not reauthenticated.");
		}
		
		return $this->copy_for_user($session_user);
	}
	
	public function in($array)
	{
		foreach ($array as $key => $item)
		{
			if ($this->equals($item))
			{
				return $key;
			}
		}
		
		return null;
	}
	
	public function equals($list)
	{
		return !!$list && $list->get_list_id() === $this->get_list_id();
	}
	
	public function copy_for_user($user, $hint = null)
	{
		if ($hint !== true)
		{
			if (!$this->user_can_read($user)
				&& (!$hint || ($this->in($hint->lists()) === null)))
			{
				return static::errors_push("User cannot read list to copy.");
			}
		}
		
		$list = $this;
		
		$error_code;
		$error_message;
		
		return ($result = Connection::transact(
			function () use ($list, $user, &$error_code, &$error_message)
			{
				Connection::query(sprintf("INSERT INTO lists (user_id, name) SELECT %d, name FROM lists WHERE list_id = %d",
					$user->get_user_id(),
					$list->get_list_id()
				));
				
				if (!!($error = Connection::query_error_clear()))
				{
					$error_message = "List failed to copy for user: $error.";
					$error_code = ErrorReporter::ERRCODE_DATABASE;
					return null;
				}
				
				if (!($list_copy_id = Connection::query_insert_id()))
				{
					$error_message = "List failed to copy for user: Insertion id is null.";
					$error_code = ErrorReporter::ERRCODE_DATABASE;
					return null;
				}

				if (!($list_copy = EntryList::select_by_id($list_copy_id))
					|| !$list_copy->entries_add_from_list($list, true))
				{
					$error_message = "List failed to copy for user: $error.";
					$error_code = ErrorReporter::ERRCODE_UNKNOWN;
					return null;
				}
				
				return $list_copy;
			}
		)) ? $result : static::errors_push($error_message, $error_code);
	}
	
	public function user_can_read($user)
	{
		return $this->user_can_write($user)
			|| $this->user_can_read_via_some_course($user)
			|| $this->get_public();
	}
	
	public function user_can_write($user)
	{
		return parent::user_can_write($user)
			|| $this->user_can_write_via_some_course($user);
	}
	
	private function user_affiliated_via_courses($user, $courses)
	{
		foreach ($courses as $course)
		{
			if ($course->user_can_execute($user))
			{
				foreach ($course->units() as $unit)
				{
					if ($unit->user_can_execute($user))
					{
						if (in_array($this, $unit->lists()))
						{
							return true;
						}
						
						foreach ($unit->lists() as $list)
						{
							if ($list->get_list_id() === $this->get_list_id())
							{
								return true;
							}
						}
					}
				}
			}
		}
		
		return false;
	}
	
	//  Returns true iff Session::get()->get_user() is in any course in which this list is shared
	private function user_can_read_via_some_course($user)
	{
		return !!$user
			&& ($this->user_affiliated_via_courses($user, $user->courses_studied())
				|| $this->user_affiliated_via_courses($user, $user->courses_instructed()));
	}
	
	private function user_can_write_via_some_course($user)
	{
		return !!$user && $this->user_affiliated_via_courses($user, $user->courses_instructed());
	}public function json_assoc($privacy = null)
	{
		return $this->prune(array (
			"listId" => $this->list_id,
			"name" => $this->name,
			"owner" => $this->get_owner()->json_assoc_condensed(),
			"isPublic" => $this->get_public(),
			"entriesCount" => $this->entries_count(),
		), array (0 => "listId"), $privacy);
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		$assoc = $this->json_assoc($privacy);
		
		$public_keys = array_keys($assoc);
		
		$assoc["entries"] = array ();
		foreach ($this->entries() as $entry)
		{
			array_push($assoc["entries"], $entry->json_assoc(null, $this));
		}
		
		return $this->prune($assoc, $public_keys, $privacy);
	}
}

?>
