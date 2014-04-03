<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APITest extends APIBase
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
				Session::get()->set_error_assoc("Unit Selection", Unit::unset_error_description());
			}
			else
			{
				$test_name = isset($_POST["test_name"]) && strlen($_POST["test_name"]) > 0 ? $_POST["test_name"] : null;
				$timeframe = isset($_POST["open"]) && isset($_POST["close"]) ? new Timeframe($_POST["open"], $_POST["close"]) : null;
				$message = isset($_POST["message"]) && strlen($_POST["message"]) > 0 ? $_POST["message"] : null;
				
				if (!($test = Test::insert($unit_id, $test_name, $timeframe, $message)))
				{
					Session::get()->set_error_assoc("Test Insertion", Test::unset_error_description());
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
				Session::get()->set_error_assoc("Test Deletion", Test::unset_error_description());
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
			$updates = 0;
				
			if (isset($_POST["test_name"]))
			{
				$updates += !!$test->set_test_name($_POST["test_name"]);
			}
			
			if (isset($_POST["message"]))
			{
				$updates += !!$test->set_message($_POST["message"]);
			}
			
			if (isset($_POST["open"]))
			{
				$updates += !!$test->set_open($_POST["open"]);
			}
			
			if (isset($_POST["close"]))
			{
				$updates += !!$test->set_close($_POST["close"]);
			}
			
			self::return_updates_as_json("Test", Test::unset_error_description(), $updates ? $test->assoc_for_json() : null);
		}
	}
	
	public function sections()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			self::return_array_as_json($test->get_sections());
		}
	}
}

?>