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
			$error_description = sprintf("Back end unexpectedly failed to insert list%s",
				!!EntryList::get_error_description() ? (": " . EntryList::get_error_description()) : "."
			);
			Session::get()->set_error_assoc("List Insertion", $error_description);
		}
		else
		{
			Session::get()->set_result_assoc($list->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
		}
	}
	
	public function update()
	{
		//  list_name
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;

		if (!isset($_POST["list_id"]))
		{
			Session::get()->set_error_assoc("Invalid Post", "List-deletion post must include list_id.");
		}
		else if (!($list = EntryList::select(($list_id = intval($_POST["list_id"], 10)))))
		{
			Session::get()->set_error_assoc("List Deletion", "Back end failed to find list for deletion with posted list_id = $list_id.");
		}
		else
		{
			$list->delete();		
			Session::get()->set_result_assoc($list->assoc_for_json());//, Session::get()->database_result_assoc(array ("didDelete" => true)));
		}
	}
	
	private function validate_list_id($list_id)
	{
		$list = null;
		if (!isset($list_id) || $list_id === null)
		{
			Session::get()->set_error_assoc("Invalid Request", "Request must include list_id.");
		}
		else if (!($list = EntryList::select(($list_id = intval($list_id, 10)))))
		{
			Session::get()->set_error_assoc("Unknown List", "Back end failed to select list with list_id = $list_id.");
		}
		
		return $list;
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
				Session::get()->set_error_assoc("Invalid Post", "List–add-entries post must include list_id and entry_ids.");
			}
			else if ($list->session_user_can_write())
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					if (!$list->add_entry(Entry::select($entry_id)))
					{
						Session::get()->set_error_assoc("List-Entries Addition", EntryList::get_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($list->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
			else
			{
				Session::get()->set_error_assoc("List Edit", "Back end failed to add entries to list.");
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
				Session::get()->set_error_assoc("Invalid Post", "List–remove-entries post must include list_id and entry_ids.");
			}
			else if ($list->session_user_can_write())
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					if (!$list->remove_entry(Entry::select($entry_id)))
					{
						Session::get()->set_error_assoc("List-Entries Removal", EntryList::get_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($list->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
			else
			{
				Session::get()->set_error_assoc("List Edit", "Back end failed to remove entries from list.");
			}
		}
	}
	
	/*
	//  Functionality moved to list_entries()
	//  Do we actually need this method? I'm not sure anymore...
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (!isset($_GET["list_id"]))
		{
			Session::get()->set_error_assoc("Invalid Get", "List-description get must include list_id.");
		}
		else if (!($list = EntryList::select($_GET["list_id"])))
		{
			$error_description = sprintf("Back end failed to describe list%s",
				!!EntryList::get_error_description() ? (": " . EntryList::get_error_description()) : "."
			);
			
			Session::get()->set_error_assoc("List Description", $error_description);
		}
		else
		{
			Session::get()->set_result_assoc($list->assoc_for_json());
		}
	}
	*/
}
?>