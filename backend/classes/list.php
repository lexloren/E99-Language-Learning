<?php

require_once "./backend/connect.php";
require_once "./backend/support.php";

class EntryList
{
	private $list_id;
	private $list_name;
	private $entries;
	private $share_public;
	private $user_id;
	
	private function __construct($list_id, $share_public, $list_name = null, $user_id = null)
	{
		$this->list_id = intval($list_id, 10);
		$this->list_name = $list_name;
		$this->shared_public = $share_public;
		$this->list_name = list_name;
		$this->user_id = $user_id;
	}
	
	//  Returns true iff Session::$user can read this list for any reason
	private function session_user_can_read()
	{
		return session_user_can_write()
			|| session_user_can_read_via_course_sharing()
			|| !!$this->shared_public;
	}
	
	//  Returns true iff Session::$user is in any course in which this list is shared
	private function session_user_can_read_via_course_sharing()
	{
		//  Stub...
		//      Will depend on implementing Course
		return false;
	}
	
	//  Returns true iff Session::$user owns this list
	private function session_user_can_write()
	{
		return !!Session::$user && (Session::$user->user_id === $this->user_id);
	}
	
	public function delete()
	{
		$mysqli = Connect::get();
		
		if (!Session::$user) return null;
		
		$mysqli->query(sprintf("DELETE FROM lists WHERE user_id = %d AND list_id = %d",
			Session::$user->user_id,
			$this->list_id
		));
		
		return self::select($mysqli->insert_id);
	}
	
	public function get_entries()
	{
		$mysqli = Connect::get();
		
		//  Need to add privileges here, based on public sharing and course-wide sharing
		
		if (!isset ($this->entries))
		{
			$result = $mysqli->query(sprintf("SELECT * FROM list_entries LEFT JOIN dictionary ON entry_id WHERE list_id = %d",
				intval($this->list_id)
			));
			
			if (!result) return null;
			
			$this->entries = array();
			while (($entry_assoc = $result->fetch_assoc()))
			{
				array_push($this->entries, Dictionary::select_entry($entry_assoc["entry_id"]));
			}
		}
		return $this->entries;
	}
	
	public static function insert($list_name = null)
	{
		$mysqli = Connect::get();
		
		if (!Session::$user) return null;
		
		$mysqli->query(sprintf("INSERT INTO lists (user_id, list_name) VALUES (%d, '%s')",
			Session::$user->user_id,
			$mysqli->escape_string($list_name)
		));
		
		return self::select($mysqli->insert_id);
	}
	
	public static function select($list_id)
	{
		$mysqli = Connect::get();
		
		//  Need to add privileges here, based on public sharing and course-wide sharing
		
		$result = $mysqli->query(sprintf("SELECT * FROM lists WHERE list_id = %d",
			intval($list_id)
		));
		
		if (!!$result && !!($result_assoc = $result->fetch_assoc()))
		{
			//  Return the list iff Session::$user can read it
			$list = EntryList::from_mysql_result_assoc($result_assoc);
			return $list->session_user_can_read() ? $list : null;
		}
		
		return null;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		return new EntryList(
			$result_assoc["list_id"],
			$result_assoc["share_public"],
			!!$result_assoc["list_name"] && strlen($result_assoc["list_name"]) > 0 ? $result_assoc["list_name"] : null,
			$result_assoc["user_id"]
		);
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function add_entry($entry_to_add)
	{
		if (!session_user_can_write()) return null;
		
		//  Make sure $this->get_entries() doesn't already contain an entry whose entry_id === $entry->entry_id
		foreach ($this->get_entries() as $entry)
		{
			if ($entry->entry_id === $entry_to_add->entry_id) return null;
		}
		
		//  Insert into user_entries from dictionary, if necessary
		$entry_added = $entry_to_add->copy_for_session_user();
		
		//  Insert into list_entries for $this->list_id and $entry->entry_id
		$mysqli->query(sprintf("INSERT INTO list_entries (list_id, entry_id) VALUES (%d, %d)",
			$this->list_id,
			$entry_added->entry_id
		));
		
		return $this;
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function remove_entry($entry_to_remove)
	{
		if (!session_user_can_write()) return null;
		
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
		
		return null;
	}
	
	//  Copies this list, setting the copy's owner to some other user
	//      Returns the copy
	public function copy_for_session_user()
	{
		if (!Session::$user || !session_user_can_read()) return null;
		//  Create a copy of the list with Session::$user as the owner
		
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
			"privileges" => null,
			"listName" => $this->list_name,
			"entries" => $entries_returnable
		);
	}
}

?>