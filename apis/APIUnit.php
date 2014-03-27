<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIUnit  extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}
	
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			$unit_name = isset($_POST["unit_name"]) && strlen($_POST["unit_name"]) > 0 ? $_POST["unit_name"] : null;
			
			if (!($unit = Unit::insert($course_id, $unit_name)))
			{
				Session::get()->set_error_assoc("Unit Insertion", Unit::get_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($unit->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (!$unit->delete())
			{
				Session::get()->set_error_assoc("Unit Deletion", Unit::get_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($unit->assoc_for_json());
			}
		}
	}
	
	public function update()
	{
		//  unit_name
		//  unit_num
	}
	
	public function lists()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_GET, "unit_id", "Unit")))
		{
			self::return_array_as_assoc_for_json($unit->get_lists());
		}
	}
	
	public function lists_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (self::validate_request($_POST, "list_ids"))
			{
				if ($unit->session_user_can_write())
				{
					foreach (explode(",", $_POST["list_ids"]) as $list_id)
					{
						if (!$unit->lists_add(EntryList::select_by_id($list_id)))
						{
							Session::get()->set_error_assoc("Unit-Lists Addition", Unit::get_error_description());
							return;
						}
					}
					
					Session::get()->set_result_assoc($unit->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
				else
				{
					Session::get()->set_error_assoc("Unit Modification", "Session user is not course instructor.");
				}
			}
		}
	}
	
	public function lists_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (self::validate_request($_POST, "list_ids"))
			{
				if ($unit->session_user_can_write())
				{
					foreach (explode(",", $_POST["list_ids"]) as $list_id)
					{
						if (!$unit->lists_remove(EntryList::select_by_id($list_id)))
						{
							Session::get()->set_error_assoc("Unit-Lists Removal", Unit::get_error_description());
							return;
						}
					}
					
					Session::get()->set_result_assoc($unit->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
				else
				{
					Session::get()->set_error_assoc("Unit Modification", "Session user is not course instructor.");
				}
			}
		}
	}
	
	public function tests()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_GET, "unit_id", "Unit")))
		{
			self::return_array_as_assoc_for_json($unit->get_tests());
		}
	}
}

?>