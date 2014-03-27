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
		
		if (!isset($_POST["unit_id"]))
		{
			Session::get()->set_error_assoc("Request Invalid", "Test-insertion post must include unit_id.");
		}
		else
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
	}
	
	public function sections()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			$this->return_array_as_assoc_for_json($test->get_sections());
		}
	}
}

?>