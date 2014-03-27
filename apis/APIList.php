<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIList extends APIBase
{
	public function __construct($user, $mysqli) {	
		parent::__construct($user, $mysqli);
	}
	
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (!($list = EntryList::insert(isset($_POST["list_name"]) ? $_POST["list_name"] : null)))
		{
			Session::get()->set_error_assoc("List Insertion", EntryList::get_error_description());
		}
		else
		{
			Session::get()->set_result_assoc($list->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
		}
	}
	
	private function validate_list_id($list_id)
	{
		$list = null;
		if (!isset($list_id) || $list_id === null)
		{
			Session::get()->set_error_assoc("Request Invalid", "Request must include list_id.");
		}
		else if (!($list = EntryList::select_by_id(($list_id = intval($list_id, 10)))))
		{
			Session::get()->set_error_assoc("List Selection", EntryList::get_error_description());
		}
		
		return $list;
	}
	
	public function update()
	{
		//  list_name
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;

		if (($list = $this->validate_list_id($_POST["list_id"])))
		{
			if (!$list->delete())
			{
				Session::get()->set_error_assoc("List Deletion", EntryList::get_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($list->assoc_for_json());
			}
		}
	}
	
	public function entries()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = $this->validate_list_id($_GET["list_id"])))
		{
			$entries = $list->get_entries();
		
			$entries_returnable = array ();
			foreach ($entries as $entry)
			{
				array_push($entries_returnable, $entry->assoc_for_json());
			}
			
			Session::get()->set_result_assoc($entries_returnable);
		}
	}
	
	public function entries_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = $this->validate_list_id($_POST["list_id"])))
		{
			if (!isset($_POST["entry_ids"]))
			{
				Session::get()->set_error_assoc("Request Invalid", "List–add-entries post must include list_id and entry_ids.");
			}
			else if ($list->session_user_can_write())
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					if (!$list->entries_add(Entry::select_by_id($entry_id)))
					{
						Session::get()->set_error_assoc("List-Entries Addition", EntryList::get_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($list->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
			else
			{
				Session::get()->set_error_assoc("List Modification", "Session user is not list owner.");
			}
		}
	}
	
	public function entries_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = $this->validate_list_id($_POST["list_id"])))
		{
			if (!isset($_POST["entry_ids"]))
			{
				Session::get()->set_error_assoc("Request Invalid", "List–remove-entries post must include list_id and entry_ids.");
			}
			else if ($list->session_user_can_write())
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					if (!$list->entries_remove(Entry::select_by_id($entry_id)))
					{
						Session::get()->set_error_assoc("List-Entries Removal", EntryList::get_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($list->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
			else
			{
				Session::get()->set_error_assoc("List Modification", "Session user is not list owner.");
			}
		}
	}
}
?>