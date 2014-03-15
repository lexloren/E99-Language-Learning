<?php

require_once "./apis/APIBase.php";
require_once './backend/support.php';
require_once './backend/classes/list.php';

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
			$list = EntryList::insert($_POST["title"]);
			exit_with_result($list->assoc_for_json());
		}
		else 
		{
			exit_with_error("Invalid insert", "list/lnsert must include title.");
		}
	}
	
	public function delete()
	{
		self::exit_if_not_authenticated();

		if (isset ($_POST["list_id"]))
		{
			$list = EntryList::select($_POST["list_id"]);
			if (!$list)
				exit_with_error("Invalid delete", "Please make sure the lits id is correct.");
				
			$list->delete();
		}
		else 
		{
			exit_with_error("Invalid insert", "List/delete must include an id.");
		}
	}
	
	public function enumerate()
	{
		self::exit_if_not_authenticated();
		exit_with_error("TODO", "Yet to be implemented");
	}
	
	public function describe()
	{
		self::exit_if_not_authenticated();
		if (isset ($_GET["list_id"]))
		{
			$list = EntryList::select($_POST["list_id"]);
			if (!$list)
				exit_with_error("Invalid describe", "Please make sure the lits id is correct.");
				
			exit_with_result($list->assoc_for_json());
		}
		else 
		{
			exit_with_error("Invalid describe", "List/describe must include an id.");
		}
	}
}
?>





