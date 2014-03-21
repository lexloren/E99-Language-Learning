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
		if(!Session::reauthenticate())
			return;
		
		if (!($list = EntryList::insert(isset ($_POST["list_name"]) ? $_POST["list_name"] : null)))
		{
			$error_description = sprintf("Back end unexpectedly failed to insert list%s",
				!!EntryList::get_error_description() ? (": " . EntryList::get_error_description()) : "."
			);
			Session::set_error_assoc("List Insertion", $error_description);
		}
		else
			Session::set_result_assoc($list->assoc_for_json(), Session::database_result_assoc(array ("didInsert" => true)));
	}
	
	public function delete()
	{
		if (!Session::reauthenticate()) return;

		if (!isset ($_POST["list_id"]))
		{
			Session::set_error_assoc("Invalid Post", "List-deletion post must include list_id.");
		}
		else if (!($list = EntryList::select(($list_id = intval($_POST["list_id"])))))
		{
			Session::set_error_assoc("List Deletion", "Back end failed to find list for deletion with posted list_id = $list_id.");
		}
		else
		{
			$list->delete();		
			Session::set_result_assoc($list->assoc_for_json(), Session::database_result_assoc(array ("didDelete" => true)));
		}
	}
	
	public function entries()
	{
		//  Because we're getting rid of the entries from List.assoc_for_json, we need a new function to get a list's entries
	}
	
	//  Arunabha, please expose this functionality to the front end.
	public function entries_add()
	{
		if (!Session::reauthenticate()) return;
		
		if (!isset ($_POST["list_id"]) || !isset ($_POST["entry_ids"]))
		{
			Session::set_error_assoc("Invalid Post", "List–add-entries post must include list_id and entry_ids.");
		}
		else
		{
			$list = EntryList::select($_POST["list_id"]);
			
			if (isset($list))
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					$list->add_entry(Entry::select($entry_id));
				}
				
				Session::set_result_assoc($list->assoc_for_json(), Session::database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function entries_remove()
	{
		if (!Session::reauthenticate()) return;
		
		//  Arunabha, please implement this method.
	}
	
	//  Do we actually need this method? I'm not sure anymore...
	public function describe()
	{
		if (!Session::reauthenticate()) return;
		
		if (!isset ($_GET["list_id"]))
		{
			Session::set_error_assoc("Invalid Get", "List-description get must include list_id.");
		}
		else if (!($list = EntryList::select($_GET["list_id"])))
		{
			$error_description = sprintf("Back end failed to describe list%s",
				!!EntryList::get_error_description() ? (": " . EntryList::get_error_description()) : "."
			);
			
			Session::set_error_assoc("List Description", $error_description);
		}
		else
		{
			Session::set_result_assoc($list->assoc_for_json());
		}
	}
}
?>