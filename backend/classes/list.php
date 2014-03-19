<?php

require_once "./backend/connection.php";
require_once "./backend/support.php";

class EntryList
{
	/***    STATIC/CLASS    ***/
	
	private static $lists_by_id = array ();
	
	public static function insert($list_name = null)
	{
		$mysqli = Connection::get_shared_instance();
		
		if (!Session::get_user()) return null;
		
		$mysqli->query(sprintf("INSERT INTO lists (user_id, list_name) VALUES (%d, '%s')",
			Session::get_user()->get_user_id(),
			$mysqli->escape_string($list_name)
		));
		
		return self::select($mysqli->insert_id);
	}
	
	public static function select($list_id)
	{
		$list_id = intval($list_id, 10);
		
		if (!in_array($list_id, array_keys(self::$lists_by_id)))
		{
			$mysqli = Connection::get_shared_instance();
			
			//  Need to add privileges here, based on public sharing and course-wide sharing
			
			$result = $mysqli->query(sprintf("SELECT * FROM lists WHERE list_id = %d",
				intval($list_id)
			));
			
			if (!!$result && !!($result_assoc = $result->fetch_assoc()))
			{
				EntryList::from_mysql_result_assoc($result_assoc);
			}
		}
		
		$list = self::$lists_by_id[$list_id];
		
		if (!!$list && $list->session_user_can_read()) return $list;
		
		return null;
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
	
	private $list_name;
	public function get_list_name()
	{
		return $this->list_name;
	}
	
	private $public;
	public function is_public()
	{
		return !!$this->public;
	}
	
	private $entries;
	public function get_entries()
	{
		if (!isset ($this->entries))
		{
			$mysqli = Connection::get_shared_instance();
			$result = $mysqli->query(sprintf("SELECT * FROM list_entries LEFT JOIN dictionary ON entry_id WHERE list_id = %d",
				intval($this->list_id)
			));
			
			$this->entries = array();
			if (!$result) return $this->entries;

			while (($entry_assoc = $result->fetch_assoc()))
			{
				array_push($this->entries, Dictionary::select_entry($entry_assoc["entry_id"]));
			}
		}
		return $this->entries;
	}
	
	private function __construct($list_id, $user_id, $list_name = null, $public = false)
	{
		$this->list_id = intval($list_id, 10);
		$this->user_id = intval($user_id, 10);
		$this->list_name = $list_name;
		$this->public = !!$public;
		
		EntryList::$lists_by_id[$this->list_id] = $this;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		if (!$result) return null;
		
		return new EntryList(
			$result_assoc["list_id"],
			$result_assoc["user_id"],
			!!$result_assoc["list_name"] && strlen($result_assoc["list_name"]) > 0 ? $result_assoc["list_name"] : null,
			$result_assoc["public"]
		);
	}
	
	//  Returns true iff Session::get_user() can read this list for any reason
	private function session_user_can_read()
	{
		return $this->session_user_can_write()
			|| $this->session_user_can_read_via_course_sharing()
			|| $this->is_public();
	}
	
	//  Returns true iff Session::get_user() is in any course in which this list is shared
	private function session_user_can_read_via_course_sharing()
	{
		//  Stub...
		//      Will depend on implementing Course
		return false;
	}
	
	//  Returns true iff Session::get_user() owns this list
	private function session_user_can_write()
	{
		return !!Session::get_user() && (Session::get_user()->get_user_id() === $this->get_user_id());
	}
	
	public function delete()
	{
		$mysqli = Connection::get_shared_instance();
		
		if (!Session::get_user()) return null;
		
		$mysqli->query(sprintf("DELETE FROM lists WHERE user_id = %d AND list_id = %d",
			Session::get_user()->get_user_id(),
			$this->list_id
		));
		
		return null;
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function add_entry($entry_to_add)
	{
		if (!$this->session_user_can_write()) return null;
		
		//  Insert into user_entries from dictionary, if necessary
		$entry_added = $entry_to_add->copy_for_session_user();
		
		$mysqli = Connection::get_shared_instance();
		
		//  Insert into list_entries for $this->list_id and $entry->entry_id
		//      If this entry already exists in the list, then ignore the error
		$mysqli->query(sprintf("INSERT IGNORE INTO list_entries (list_id, entry_id) VALUES (%d, %d)",
			$this->list_id,
			$entry_added->get_entry_id()
		));
		
		return $this;
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function remove_entry($entry_to_remove)
	{
		if (!$this->session_user_can_write()) return null;
		
		foreach ($this->get_entries() as $entry_removed)
		{
			if ($entry_removed->entry_id === $entry_to_remove->entry_id)
			{
				$mysqli->query(sprintf("DELETE FROM list_entries (list_id, entry_id) VALUES (%d, %d)",
					$this->list_id,
					$entry_removed->entry_id
				));
				
				$this->entries = array_diff($this->entries, array ($entry_removed));
				
				return $this;
			}
		}
		
		//  Tried to remove an entry that's apparently not in this list
		return null;
	}
	
	//  Copies this list, setting the copy's owner to some other user
	//      Returns the copy
	public function copy_for_session_user()
	{
		if (!Session::get_user() || !session_user_can_read()) return null;
		//  Create a copy of the list with Session::get_user() as the owner
		
		return null;
	}
	
	public function assoc_for_json()
	{
		$entries_returnable = array ();
		foreach ($this->get_entries() as $entry)
		{
			array_push($entries_returnable, $entry->assoc_for_json());
		}
		
		return array (
			"listId" => $this->list_id,
			"listName" => $this->list_name,
			"owner" => $this->get_owner()->assoc_for_json(),
			"isPublic" => $this->is_public(),
			"entries" => $entries_returnable
		);
	}
}

?>