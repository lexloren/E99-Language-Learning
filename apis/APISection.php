<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APISection extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}
	
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (self::validate_request($_POST, "test_id"))
		{
			if (!($test = self::validate_selection_id($_POST, "test_id", "Test")))
			{
				Session::get()->set_error_assoc("Test Selection", Test::unset_error_description());
			}
			else
			{
				$section_name = isset($_POST["section_name"]) && strlen($_POST["section_name"]) > 0 ? $_POST["section_name"] : null;
				$message = isset($_POST["message"]) && strlen($_POST["message"]) > 0 ? $_POST["message"] : null;
				
				if (!($section = Section::insert($test_id, $section_name, $message)))
				{
					Session::get()->set_error_assoc("Section Insertion", Section::unset_error_description());
				}
				else
				{
					Session::get()->set_result_assoc($section->assoc_for_json());
				}
			}
		}
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($section = self::validate_selection_id($_POST, "section_id", "Section")))
		{
			if (!$section->delete())
			{
				Session::get()->set_error_assoc("Section Deletion", Section::unset_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($section->assoc_for_json());
			}
		}
	}
	
	public function update()
	{
		//  section_name
		//  message
		
		if (!Session::get()->reauthenticate()) return;
		
		if (($section = self::validate_selection_id($_POST, "section_id", "Section")))
		{
			$updates = 0;
				
			if (isset($_POST["section_name"]))
			{
				$updates += !!$section->set_section_name($_POST["section_name"]);
			}
			
			if (isset($_POST["message"]))
			{
				$updates += !!$section->set_message($_POST["message"]);
			}
			
			self::return_updates_as_json("Section", Section::unset_error_description(), $updates ? $section->assoc_for_json() : null);
		}
	}
}

?>