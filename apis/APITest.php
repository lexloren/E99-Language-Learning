<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APITest  extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}
	
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (self::validate_request($_POST, "unit_id"))
		{
			if (!($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
			{
				Session::get()->set_error_assoc("Unit Selection", Unit::get_error_description());
			}
			else
			{
				$test_name = isset($_POST["test_name"]) && strlen($_POST["test_name"]) > 0 ? $_POST["test_name"] : null;
				
				if (!($test = Test::insert($unit_id, $test_name)))
				{
					Session::get()->set_error_assoc("Test Insertion", Test::get_error_description());
				}
				else
				{
					Session::get()->set_result_assoc($test->assoc_for_json());
				}
			}
		}
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			if (!$test->delete())
			{
				Session::get()->set_error_assoc("Test Deletion", Test::get_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($test->assoc_for_json());
			}
		}
	}
	
	public function update()
	{
		//  test_name
		//  timeframe
		//  message
		
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			if ($test->session_user_can_write())
			{
				$updates = 0;
				
				if (isset($_POST["test_name"]))
				{
					$updates += !!$test->set_test_name($_POST["test_name"]);
				}
				
				if (isset($_POST["message"]))
				{
					$updates += !!$test->set_message($_POST["message"]);
				}
				
				if (!$updates)
				{
					$failure_message = !!Test::get_error_description() ? Test::get_error_description() : "Test failed to update.";
					Session::get()->set_error_assoc("Test Modification", $failure_message);
				}
				else
				{
					Session::get()->set_result_assoc($test->assoc_for_json());
				}
			}
		}
	}
	
	public function sections()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			self::return_array_as_assoc_for_json($test->get_sections());
		}
	}
}

?>