<?php

require_once "./backend/connect.php";
require_once "./backend/support.php";

class EntryList
{
	private $list_id;
	private $title;
	private $entries;
	private $user_id;
	
	private function __construct($list_id, $title = null, $user_id = null)
	{
		$this->list_id = $list_id;
		
	}
	
	public static function select($list_id)
	{
		global $mysqli;
		
		$result = $mysqli->query(sprintf("SELECT * FROM lists WHERE list_id = %d",
			intval($list_id)
		));
		
		if (!!($result_assoc = $result->fetch_assoc()))
		{
			return EntryList::from_mysql_result_assoc($result_assoc);
		}
		
		return null;
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function add_entry($entry)
	{
		
	}
	
	//  Adds an entry to this list
	//      Returns this list
	public function remove_entry($entry)
	{
	
	}
	
	//  Copies this list, setting the copy's owner to some other user
	//      Returns the copy
	public function copy_for_user($user)
	{
		
	}
}

?>