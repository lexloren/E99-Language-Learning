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
		Session::reauthenticate();
		
		if (!($list = EntryList::insert(isset ($_POST["list_name"]) ? $_POST["list_name"] : null)))
		{
			$error_description = sprintf("Back end unexpectedly failed to insert list%s",
				!!EntryList::get_error_description() ? (": " . EntryList::get_error_description()) : "."
			);
			Session::exit_with_error("List Insertion", $error_description);
		}
		
		Session::exit_with_result($list->assoc_for_json(), Session::database_result_assoc(array ("didInsert" => true)));
	}
	
	//  Arunabha, please expose this functionality to the front end.
	public function add_entries()
	{
		Session::reauthenticate();
		
		if (!isset ($_POST["list_id"]) || !isset ($_POST["entry_ids"]))
		{
			Session::exit_with_error("Invalid Post", "List–add-entries post must include list_id and entry_ids.");
		}
		
		$list = EntryList::select($_POST["list_id"]);
		foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
		{
			$list->add_entry(Entry::select($entry_id));
		}
		
		Session::exit_with_result($list->assoc_for_json(), Session::database_result_assoc(array ("didInsert" => true)));
	}
	
	public function remove_entries()
	{
		Session::reauthenticate();
		
		//  Arunabha, please implement this method.
	}
	
	public function delete()
	{
		Session::reauthenticate();

		if (!isset ($_POST["list_id"]))
		{
			Session::exit_with_error("Invalid Post", "List-deletion post must include list_id.");
		}
		
		if (!($list = EntryList::select(($list_id = intval($_POST["list_id"])))))
		{
			Session::exit_with_error("List Deletion", "Back end failed to find list for deletion with posted list_id = $list_id.");
		}
		
		$list->delete();
		
		Session::exit_with_result($list->assoc_for_json(), Session::database_result_assoc(array ("didDelete" => true)));
	}
	
	public function describe()
	{
		Session::reauthenticate();
		
		if (!isset ($_GET["list_id"]))
		{
			Session::exit_with_error("Invalid Get", "List-description get must include list_id.");
		}
		
		if (!($list = EntryList::select($_GET["list_id"])))
		{
			$error_description = sprintf("Back end failed to describe list%s",
				!!EntryList::get_error_description() ? (": " . EntryList::get_error_description()) : "."
			);
			
			Session::exit_with_error("List Description", $error_description);
		}
		
		Session::exit_with_result($list->assoc_for_json());
	}
}
?>