<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIUnit extends APIBase
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
			$name = isset($_POST["name"]) && strlen($_POST["name"]) > 0 ? $_POST["name"] : null;
			$timeframe = isset($_POST["open"]) && isset($_POST["close"]) ? new Timeframe($_POST["open"], $_POST["close"]) : null;
			$message = isset($_POST["message"]) && strlen($_POST["message"]) > 0 ? $_POST["message"] : null;
			
			if (!($unit = Unit::insert($course->get_course_id(), $name, $timeframe, $message)))
			{
				Session::get()->set_error_assoc("Unit Insertion", Unit::errors_unset());
			}
			else
			{
				if (isset($_POST["list_ids"]))
				{
					$list_ids = explode(",", $_POST["list_ids"]);
					
					foreach ($list_ids as $list_id)
					{
						if (($list = EntryList::select_by_id($list_id))
							&& $list->session_user_can_read())
						{
							$unit->lists_add($list);
						}
					}
				}
				
				Session::get()->set_result_assoc($unit->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_GET, "unit_id", "Unit")))
		{
			Session::get()->set_result_assoc($unit->json_assoc_detailed(false));
		}
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (!$unit->delete())
			{
				Session::get()->set_error_assoc("Unit Deletion", Unit::errors_unset());
			}
			else
			{
				Session::get()->set_result_assoc($unit->json_assoc());
			}
		}
	}
	
	public function update()
	{
		//  name
		//  num
		
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			$updates = 0;
			
			if (isset($_POST["name"]))
			{
				$updates += !!$unit->set_unit_name($_POST["name"]);
			}
			
			if (isset($_POST["num"]))
			{
				$updates += !!$unit->set_number($_POST["num"]);
			}
			
			if (isset($_POST["message"]))
			{
				$updates += !!$unit->set_message($_POST["message"]);
			}
			
			if (isset($_POST["open"]) && isset($_POST["close"]))
			{
				$updates += !!$unit->set_timeframe(!!$_POST["open"] || !!$_POST["close"] ? new Timeframe($_POST["open"], $_POST["close"]) : null);
			}
			else
			{
				if (isset($_POST["open"]))
				{
					$updates += !!$unit->set_open($_POST["open"]);
				}
				if (isset($_POST["close"]))
				{
					$updates += !!$unit->set_close($_POST["close"]);
				}
			}
			
			self::return_updates_as_json("Unit", Unit::errors_unset(), $updates ? $unit->json_assoc() : null);
		}
	}
	
	public function lists()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_GET, "unit_id", "Unit")))
		{
			self::return_array_as_json($unit->lists());
		}
	}
	
	public function lists_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (self::validate_request($_POST, "list_ids"))
			{
				foreach (explode(",", $_POST["list_ids"]) as $list_id)
				{
					if (!$unit->lists_add(EntryList::select_by_id($list_id)))
					{
						Session::get()->set_error_assoc("Unit-Lists Addition", Unit::errors_unset());
						return;
					}
				}
				
				Session::get()->set_result_assoc($unit->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
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
				foreach (explode(",", $_POST["list_ids"]) as $list_id)
				{
					if (!$unit->lists_remove(EntryList::select_by_id($list_id)))
					{
						Session::get()->set_error_assoc("Unit-Lists Removal", Unit::errors_unset());
						return;
					}
				}
				
				Session::get()->set_result_assoc($unit->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function tests()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_GET, "unit_id", "Unit")))
		{
			self::return_array_as_json($unit->tests());
		}
	}
}

?>