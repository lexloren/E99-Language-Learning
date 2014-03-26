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
		
		if (!isset($_POST["course_id"]))
		{
			Session::get()->set_error_assoc("Invalid Post", "Unit-insertion post must include course_id.");
		}
		else
		{
			if (!($course = Course::select_by_id(($course_id = intval($_POST["course_id"], 10)))))
			{
				Session::get()->set_error_assoc("Course-Unit Insertion", "Failed to insert course unit: " . Course::get_error_description());
			}
			else
			{
				$unit_name = isset($_POST["unit_name"]) && strlen($_POST["unit_name"]) > 0 ? $_POST["unit_name"] : null;
				
				if (!($unit = Unit::insert($course_id, $unit_name)))
				{
					$error_description = sprintf("Back end unexpectedly failed to insert course unit%s",
						!!Unit::get_error_description() ? (": " . Unit::get_error_description()) : "."
					);
					Session::get()->set_error_assoc("Course-Unit Insertion", $error_description);
				}
				else
				{
					Session::get()->set_result_assoc($unit->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
			}
		}
	}
	
	private function validate_unit_id($unit_id)
	{
		$unit = null;
		if (!isset($unit_id))
		{
			Session::get()->set_error_assoc("Invalid Request", "Request must include unit_id.");
		}
		else if (!($unit = Unit::select_by_id(($unit_id = intval($unit_id, 10)))))
		{
			Session::get()->set_error_assoc("Unknown Unit", "Back end failed to select unit with unit_id = $unit_id.");
		}
		
		return $unit;
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = $this->validate_unit_id($_POST["unit_id"])))
		{
			$unit->delete();
			Session::get()->set_result_assoc($unit->assoc_for_json());//, Session::get()->database_result_assoc(array ("didDelete" => true)));
		}
	}
	
	public function update()
	{
		//  unit_name
		//  unit_nmbr
	}
	
	public function lists()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = $this->validate_unit_id($_GET["unit_id"])))
		{
			$this->return_array_as_assoc_for_json($unit->get_lists());
		}
	}
	
	public function lists_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = $this->validate_unit_id($_POST["unit_id"])))
		{
			if (!isset($_POST["list_ids"]))
			{
				Session::get()->set_error_assoc("Invalid Post", "Unit–add-lists post must include unit_id and list_ids.");
			}
			else if ($unit->session_user_is_instructor())
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
				Session::get()->set_error_assoc("Unit Edit", "Back end failed to add lists to unit.");
			}
		}
	}
	
	public function lists_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = $this->validate_unit_id($_POST["unit_id"])))
		{
			if (!isset($_POST["list_ids"]))
			{
				Session::get()->set_error_assoc("Invalid Post", "Course–remove-lists post must include unit_id and list_ids.");
			}
			else if ($unit->session_user_is_instructor())
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
				Session::get()->set_error_assoc("Unit Edit", "Back end failed to remove lists from unit.");
			}
		}
	}
	
	public function tests()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = $this->validate_unit_id($_GET["unit_id"])))
		{
			$this->return_array_as_assoc_for_json($unit->get_tests());
		}
	}
}

?>