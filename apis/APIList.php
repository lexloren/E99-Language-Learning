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
		if (!($list = EntryList::insert(isset ($_POST["list_name"]) ? $_POST["list_name"] : null)))
		{
			Session::exit_with_error("List Insertion", "Back end unexpectedly failed to insert list.");
		}
	}
	
	public function delete()
	{
		self::exit_if_not_authenticated();

		if (isset ($_POST["list_id"]))
		{
			$list = EntryList::select($_POST["list_id"]);
			if (!$list)
				Session::exit_with_error("Invalid delete", "Please make sure the lits id is correct.");
				
			$list->delete();
		}
		else 
		{
			Session::exit_with_error("Invalid insert", "List/delete must include an id.");
		}
	}
	
	public function lists()
	{
		self::exit_if_not_authenticated();
		
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
		self::exit_if_not_authenticated();
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