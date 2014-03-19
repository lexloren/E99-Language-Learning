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
			Session::exit_with_error("List Insertion", "Back end unexpectedly failed to insert list" . !!EntryList::get_error_description() ? ": ".EntryList::get_error_description() : ".");
		}
		
		Session::exit_with_result($list->assoc_for_json(), Session::database_result_assoc(array ("didInsert" => true)));
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
	
	public function lists()
	{
		Session::reauthenticate();
		
		$user = Session::get_user();
		$lists = $user->get_lists();
		
		$lists_returnable = array ();
		foreach ($lists as $list)
		{
			array_push($lists_returnable, $list->assoc_for_json());
		}
		
		Session::exit_with_result($lists_returnable);
	}
	
	public function describe()
	{
		Session::reauthenticate();
		
		if (isset ($_GET["list_id"]))
		{
			$list = EntryList::select($_GET["list_id"]);
			if (!$list)
				Session::exit_with_error("Invalid describe", "Please make sure the lits id is correct.");
				
			Session::exit_with_result($list->assoc_for_json());
		}
		else 
		{
			Session::exit_with_error("Invalid describe", "List/describe must include an id.");
		}
	}
}
?>