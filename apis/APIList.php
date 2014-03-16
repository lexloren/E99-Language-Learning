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
		self::exit_if_not_authenticated();
		
		if (isset ($_POST["title"]))
		{
			$entry_list = EntryList::insert($_POST["title"]);
			if (isset($entry_list))
				Session::exit_with_result($entry_list->assoc_for_json());
			else
				Session::exit_with_error("Insert failed", "list/lnsert failed to create a list.");
		}
		else 
		{
			Session::exit_with_error("Invalid insert", "list/lnsert must include title.");
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
	
	public function enumerate()
	{
		self::exit_if_not_authenticated();
		Session::exit_with_error("TODO", "Yet to be implemented");
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





