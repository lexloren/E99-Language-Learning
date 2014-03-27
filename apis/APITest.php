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
			Session::get()->set_error_assoc("Invalid Post", "Test-insertion post must include unit_id.");
		}
		else
		{
			if (!($unit = Unit::select_by_id(($unit_id = intval($_POST["unit_id"], 10)))))
			{
				Session::get()->set_error_assoc("Unit-Test Insertion", "Failed to insert course unit test: " . Unit::get_error_description());
			}
			else
			{
				$test_name = isset($_POST["test_name"]) && strlen($_POST["test_name"]) > 0 ? $_POST["test_name"] : null;
				
				if (!($test = Test::insert($unit_id, $test_name)))
				{
					$error_description = sprintf("Back end unexpectedly failed to insert course unit test%s",
						!!Test::get_error_description() ? (": " . Test::get_error_description()) : "."
					);
					Session::get()->set_error_assoc("Unit-Test Insertion", $error_description);
				}
				else
				{
					Session::get()->set_result_assoc($test->assoc_for_json());
				}
			}
		}
	}
	
	private function validate_test_id($test_id)
	{
		if (!isset($test_id))
		{
			Session::get()->set_error_assoc("Invalid Request", "Request must include test_id.");
		}
		else if (!($test = Test::select_by_id(($test_id = intval($test_id, 10)))))
		{
			Session::get()->set_error_assoc("Unknown Test", "Back end failed to select test with test_id = $test_id.");
		}
		
		return $test;
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = $this->validate_test_id($_POST["test_id"])))
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
		
		if (($test = $this->validate_test_id($_GET["test_id"])))
		{
			$this->return_array_as_assoc_for_json($test->get_sections());
		}
	}
}

?>