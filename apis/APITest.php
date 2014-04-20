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
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			$name = isset($_POST["name"]) && strlen($_POST["name"]) > 0 ? $_POST["name"] : null;
			$timeframe = isset($_POST["open"]) && isset($_POST["close"]) ? new Timeframe($_POST["open"], $_POST["close"]) : null;
			$message = isset($_POST["message"]) && strlen($_POST["message"]) > 0 ? $_POST["message"] : null;
			
			if (!($test = Test::insert($unit->get_unit_id(), $name, $timeframe, $message)))
			{
				Session::get()->set_error_assoc("Test Insertion", Test::unset_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($test->json_assoc());
			}
		}
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_GET, "test_id", "Test")))
		{
			Session::get()->set_result_assoc($test->detailed_json_assoc(false));
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
				Session::get()->set_result_assoc($test->json_assoc());
			}
		}
	}
	
	public function update()
	{
		//  name
		//  timeframe
		//  message
		
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			$updates = 0;
				
			if (isset($_POST["name"]))
			{
				$updates += !!$test->set_test_name($_POST["name"]);
			}
			
			if (isset($_POST["message"]))
			{
				$updates += !!$test->set_message($_POST["message"]);
			}
			
			if (isset($_POST["open"]) && isset($_POST["close"]))
			{
				$updates += !!$test->set_timeframe(!!$_POST["open"] || !!$_POST["close"] ? new Timeframe($_POST["open"], $_POST["close"]) : null);
			}
			else
			{
				if (isset($_POST["open"]))
				{
					$updates += !!$test->set_open($_POST["open"]);
				}
				if (isset($_POST["close"]))
				{
					$updates += !!$test->set_close($_POST["close"]);
				}
			}
			
			self::return_updates_as_json("Test", Test::unset_error_description(), $updates ? $test->json_assoc() : null);
		}
	}
	
	public function entries()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_GET, "test_id", "Test")))
		{
			if ($test->session_user_can_write())
			{
				if (is_array($entries = $test->get_entries()))
				{
					self::return_array_as_json($entries);
				}
				else
				{
					Session::get()->set_error_assoc("Test Entries", Test::unset_error_description());
				}
			}
			else
			{
				Session::get()->set_error_assoc("Test-Entries Selection", "Session user cannot read test entries.");
			}
		}
	}
	
	public function entries_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			if (self::validate_request($_POST, "entry_ids"))
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					if (!$test->entries_add(Entry::select_by_id($entry_id)))
					{
						Session::get()->set_error_assoc("Test-Entries Addition", Test::unset_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($test->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function entries_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			if (self::validate_request($_POST, "entry_ids"))
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					if (!$test->entries_remove(Entry::select_by_id($entry_id)))
					{
						Session::get()->set_error_assoc("Test-Entries Removal", Test::unset_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($test->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function entry_update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test"))
			&& ($entry = self::validate_selection_id($_POST, "entry_id", "Entry")))
		{
			if (self::validate_request($_POST, "number"))
			{
				if ($test->set_entry_number($entry, $_POST["number"]))
				{
					Session::get()->set_result_assoc($test->json_assoc());
				}
				else
				{
					Session::get()->set_error_assoc("Test-Entry Modification", Test::unset_error_description());
				}
			}
		}
	}
	
	public function execute()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			if (!($sitting = $test->execute_for_session_user()))
			{
				Session::get()->set_error_assoc("Test Execution", Test::unset_error_description());
				return;
			}
			
			if (isset($_POST["test_entry_id"]) && isset($_POST["contents"]))
			{
				if (!Response::insert($_POST["test_entry_id"], $_POST["contents"]))
				{
					Session::get()->set_error_assoc("Test Execution", Response::unset_error_description());
					return;
				}
			}
			
			if (!($next_json_assoc = $sitting->next_json_assoc()))
			{
				Session::get()->set_error_assoc("Test Execution", Sitting::unset_error_description());
				return;
			}
			
			Session::get()->set_result_assoc($next_json_assoc);
		}
	}
}

?>