<?php

require_once "./backend/connect.php";
require_once "./backend/support.php";

class EntryList
{
	private $list_id;
	private $list_name;
	private $entries;
	private $user_id;
	
	private function __construct($list_id, $title = null, $user_id = null)
	{
		$this->list_id = $list_id;
		
	}
	
	public function get_entries()
	{
		global $mysqli;
		
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
	
	public static function select($list_id)
	{
		global $mysqli;
		
		$result = $mysqli->query(sprintf("SELECT * FROM lists WHERE list_id = %d",
			intval($list_id)
		));
		
		if (!!$result && !!($result_assoc = $result->fetch_assoc()))
		{
			return EntryList::from_mysql_result_assoc($result_assoc);
		}
		
		return null;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		return new EntryList(
			$result_assoc["list_id"],
			!!$result_assoc["list_name"] && strlen($result_assoc["list_name"]) > 0 ? $result_assoc["list_name"] : null,
			$result_assoc["user_id"]
		);
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function add_entry($entry)
	{
		//  Make sure $this->get_entries() doesn't already contain an entry whose entry_id === $entry->entry_id
		//  Insert into entries from the dictionary, if necessary
		//  Insert into list_entries for $this->list_id and $entry->entry_id
		//  If everything succeeded, then return $this;
		//      otherwise, return null
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function remove_entry($entry_to_remove)
	{
		foreach ($this->get_entries() as $entry)
		{
			if ($entry->entry_id === $entry_to_remove->entry_id)
			{
				//  Delete from database
				//  If deletion succeeded, then delete from $this->entries and return $this
			}
		}
		
		return null;
	}
	
	//  Copies this list, setting the copy's owner to some other user
	//      Returns the copy
	public function copy_for_user($user)
	{
		
	}
}

?>