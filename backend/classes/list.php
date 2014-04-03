<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class EntryList extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function insert($list_name = null)
	{
		if (!Session::get()->get_user())
		{
			return static::set_error_description("Session user has not reauthenticated.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO lists (user_id, list_name) VALUES (%d, '%s')",
			Session::get()->get_user()->get_user_id(),
			$mysqli->escape_string($list_name)
		));
		
		if (!!$mysqli->error)
		{
			return static::set_error_description("Failed to insert list: " . $mysqli->error);
		}
		
		return self::select_by_id($mysqli->insert_id);
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
	
	private $list_name;
	public function get_list_name()
	{
		return $this->list_name;
	}
	public function set_list_name($list_name)
	{
		if (!self::update_this($this, "lists", array ("list_name" => $list_name), "list_id", $this->get_list_id()))
		{
			return null;
		}
		$this->list_name = $list_name;
		return $this;
	}
	
	private $public;
	public function is_public()
	{
		return !!$this->public;
	}
	
	private $entries;
	public function get_entries()
	{
		$user_entries = sprintf("SELECT * FROM user_entries LEFT JOIN (SELECT entry_id, %s FROM %s) AS reference USING (entry_id) WHERE user_id = %d",
			Dictionary::language_code_columns(),
			Dictionary::join(),
			$this->get_user_id()
		);
		
		$table = "list_entries LEFT JOIN ($user_entries) AS user_entries USING (user_entry_id)";
		return self::get_cached_collection($this->entries, "Entry", $table, "list_id", $this->get_list_id());
	}
	
	private function __construct($list_id, $user_id, $list_name = null, $public = false)
	{
		$this->list_id = intval($list_id, 10);
		$this->user_id = intval($user_id, 10);
		$this->list_name = $list_name;
		$this->public = !!$public;
		
		self::register($this->list_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"list_id",
			"user_id",
			"list_name",
			"public"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new EntryList(
				$result_assoc["list_id"],
				$result_assoc["user_id"],
				!!$result_assoc["list_name"] && strlen($result_assoc["list_name"]) > 0 ? $result_assoc["list_name"] : null,
				$result_assoc["public"]
			)
			: null;
	}
	
	//  Returns true iff Session::get()->get_user() can read this list for any reason
	public function session_user_can_read()
	{
		return !!Session::get()
			&& !!($session_user = Session::get()->get_user())
			&& $this->user_can_read($session_user);
	}
	
	public function user_can_read($user)
	{
		return $this->user_can_write($user)
			|| $this->user_can_read_via_course($user)
			|| $this->is_public();
	}
	
	public function user_can_write($user)
	{
		return parent::user_can_write($user)
			|| $this->user_can_write_via_course($user);
	}
	
	public function session_user_can_write()
	{
		return !!Session::get() && $this->user_can_write(Session::get()->get_user());
	}
	
	private function user_affiliated_via_courses($user, $courses)
	{
		foreach ($courses as $course)
		{
			foreach ($course->get_lists() as $list)
			{
				if ($list->list_id === $this->list_id)
					//  Should now be true for all lists associated with all courses:
					//  && $list->get_owner()->equals($course->get_owner()))
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
	//  Returns true iff Session::get()->get_user() is in any course in which this list is shared
	private function user_can_read_via_course($user)
	{
		return !!$user && $this->user_affiliated_via_courses($user, $user->get_student_courses());
	}
	
	private function user_can_write_via_course($user)
	{
		return !!$user && $this->user_affiliated_via_courses($user, $user->get_instructor_courses());
	}
	
	public function delete()
	{
		return self::delete_this($this, "lists", "list_id", $this->get_list_id());
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function entries_add($entry_to_add)
	{
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit list.");
		}
		
		//  Insert into user_entries from dictionary, if necessary
		$entry_added = $entry_to_add->copy_for_session_user();
		
		if (!$entry_added)
		{
			return static::set_error_description("List failed to add entry: " . Entry::unset_error_description());
		}
		
		$mysqli = Connection::get_shared_instance();
		
		//  Insert into list_entries for $this->list_id and $entry->entry_id
		//      If this entry already exists in the list, then ignore the error
		$mysqli->query(sprintf("INSERT IGNORE INTO list_entries (list_id, user_entry_id) VALUES (%d, %d)",
			$this->list_id,
			$entry_added->get_user_entry_id()
		));
		
		return $this;
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function entries_remove($entry_to_remove)
	{
		if (!$this->session_user_can_write())
		{
			return static::set_error_description("Session user cannot edit list.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		foreach ($this->get_entries() as $entry_removed)
		{
			if ($entry_removed->get_entry_id() === $entry_to_remove->get_entry_id())
			{
				$mysqli->query(sprintf("DELETE FROM list_entries WHERE list_id = %d AND user_entry_id = %d",
					$this->list_id,
					$entry_removed->get_user_entry_id()
				));
				
				unset($this->entries);
				
				return $this;
			}
		}
		
		return static::set_error_description("List failed to remove entry.");
	}
	
	//  Copies this list, setting the copy's owner to some other user
	//      Returns the copy
	public function copy_for_session_user()
	{
		if (!Session::get() || !($session_user = Session::get()->get_user()))
		{
			return static::set_error_description("Session user has not reauthenticated.");
		}
		
		return $this->copy_for_user($session_user);
	}
	
	public function copy_for_user($user)
	{
		if (!$this->user_can_read($user))
		{
			return static::set_error_description("User cannot read list.");
		}
		
		if ($this->get_owner()->equals($user)) return $this;
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO lists (user_id, list_name) SELECT %d, list_name FROM lists WHERE list_id = %d",
			$user->get_user_id(),
			$this->get_list_id()
		));
		
		$list_copy_id = $mysqli->insert_id;
		
		$insertion_values = array ();
		foreach ($this->get_entries() as $entry)
		{
			$user_entry_copy_id = $entry->copy_for_user($user)->get_user_entry_id();
			array_push($insertion_values, "($list_copy_id, $user_entry_copy_id)");
		}
		
		$mysqli->query(sprintf("INSERT INTO list_entries (list_id, user_entry_id) VALUES %s",
			implode(", ", $insertion_values)
		));
		
		return self::select_by_id($list_copy_id);
	}
	
	public function assoc_for_json($privacy = null)
	{
		return array (
			"listId" => $this->list_id,
			"listName" => $this->list_name,
			"owner" => $this->get_owner()->assoc_for_json(),
			"isPublic" => $this->is_public()
		);
	}
	
	public function detailed_assoc_for_json($privacy = null)
	{
		$assoc = $this->assoc_for_json($privacy);
		
		$assoc["entries"] = self::array_for_json($this->get_entries());
		
		return $assoc;
	}
}

?>
