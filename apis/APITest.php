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
				$mode = isset($_POST["mode"]) && strlen($_POST["mode"]) > 0 ? intval($_POST["mode"], 10) : null;
				
				if (isset($_POST["list_ids"]))
				{
					$list_ids = explode(",", $_POST["list_ids"]);
					
					foreach ($list_ids as $list_id)
					{
						if (($list = EntryList::select_by_id($list_id))
							&& $list->session_user_can_read())
						{
							$test->entries_add_from_list($list, $mode);
						}
					}
				}
				
				if (isset($_POST["entry_ids"]))
				{
					$entry_ids = explode(",", $_POST["entry_ids"]);
					
					foreach ($entry_ids as $entry_id)
					{
						if (($entry = Entry::select_by_id($entry_id)))
						{
							$test->entries_add($entry, $mode);
						}
					}
				}
			
				Session::get()->set_result_assoc($test->json_assoc());
			}
		}
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_GET, "test_id", "Test")))
		{
			Session::get()->set_result_assoc($test->json_assoc_detailed(false));
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
	
	public function unexecute()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			if (!$test->unexecute())
			{
				Session::get()->set_error_assoc("Test Unexecution", Test::unset_error_description());
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
				if (is_array($entries = $test->entries()))
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
			$mode = isset($_POST["mode"]) && strlen($_POST["mode"]) > 0 ? intval($_POST["mode"], 10) : null;
			
			if (isset($_POST["list_ids"]))
			{
				$list_ids = explode(",", $_POST["list_ids"]);
				
				foreach ($list_ids as $list_id)
				{
					if (($list = EntryList::select_by_id($list_id))
						&& $list->session_user_can_read())
					{
						$test->entries_add_from_list($list, $mode);
					}
				}
			}
			
			if (isset($_POST["entry_ids"]))
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					$test->entries_add(Entry::select_by_id($entry_id), $mode);
				}
			}
			
			self::return_array_as_json($test->entries());
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
				
				self::return_array_as_json($test->entries());
			}
		}
	}
	
	public function entries_randomize()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			$remode = isset($_POST["remode"]) && !!$_POST["remode"];
			$renumber = isset($_POST["renumber"]) && !!$_POST["renumber"];
			
			if (!$test->entries_randomize($renumber, $remode))
			{
				Session::get()->set_error_assoc("Test-Entries Randomization", Test::unset_error_description());
				return;
			}
			
			self::return_array_as_json($test->entries());
		}
	}
	
	public function entry_update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test"))
			&& ($entry = self::validate_selection_id($_POST, "entry_id", "Entry")))
		{
			$updates = 0;
			
			if (isset($_POST["number"]))
			{
				$updates += !!$test->set_entry_number($entry, $_POST["number"]);
			}
			
			if (isset($_POST["mode"]))
			{
				$mode = intval($_POST["mode"], 10);
				
				$updates += !!$test->set_entry_mode($entry, $mode);
			}
			
			if (isset($_POST["prompts"]))
			{
				$prompts = explode(",", $_POST["prompts"]);
				
				foreach ($prompts as $prompt)
				{
					if (($pattern = Pattern::select_by_test_id_entry_id_contents($test->get_test_id(), $entry->get_entry_id(), $contents)))
					{
						$updates += !!$pattern->set_prompt(true);
					}
				}
			}
			
			self::return_updates_as_json("Test", Test::unset_error_description(), $updates ? $test->json_assoc() : null);
		}
	}
	
	public function entry_prompts()
	{
		return $this->entry_patterns(true);
	}
	
	public function entry_prompts_add()
	{
		if (($pattern = self::validate_selection_id($_POST, "pattern_id", "Pattern"))
			|| (($test = self::validate_selection_id($_POST, "test_id", "Test"))
				&& ($entry = self::validate_selection_id($_POST, "entry_id", "Entry"))
				&& isset($_POST["contents"])))
		{
			Pattern::unset_error_description();
			
			if ($pattern || ($pattern = Pattern::select_by_test_id_entry_id_contents($test->get_test_id(), $entry->get_entry_id(), $contents, true)))
			{
				$pattern->set_prompt(true);
				
				Session::get()->set_result_assoc($pattern->json_assoc());
				return;
			}
			Session::get()->set_error_assoc("Test-Entry Pattern Modification", Pattern::unset_error_description());
		}
	}
	
	public function entry_prompts_remove()
	{
		if (($pattern = self::validate_selection_id($_POST, "pattern_id", "Pattern"))
			|| (($test = self::validate_selection_id($_POST, "test_id", "Test"))
				&& ($entry = self::validate_selection_id($_POST, "entry_id", "Entry"))
				&& isset($_POST["contents"])))
		{
			Pattern::unset_error_description();
			
			if ($pattern || ($pattern = Pattern::select_by_test_id_entry_id_contents($test->get_test_id(), $entry->get_entry_id(), $contents)))
			{
				$pattern->set_prompt(false);
				
				Session::get()->set_result_assoc($pattern->json_assoc());
				return;
			}
			Session::get()->set_error_assoc("Test-Entry Pattern Modification", Pattern::unset_error_description());
		}
	}
	
	public function entry_patterns($prompts_only = false)
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_GET, "test_id", "Test"))
			&& ($entry = self::validate_selection_id($_GET, "entry_id", "Entry")))
		{
			if (($test_entry_id = array_search($entry, $test->entries())))
			{
				self::return_array_as_json(Pattern::select_all_for_test_entry_id($test_entry_id, $prompts_only));
			}
			else
			{
				Session::get()->set_error_assoc("Test-Entry Patterns Selection", "Failed to select test entry where test_id = " . $test->get_test_id() . " and entry_id = " . $entry->get_entry_id() . ".");
			}
		}
	}
	
	public function entry_pattern_update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($pattern = self::validate_selection_id($_POST, "pattern_id", "Pattern"))
			|| (($test = self::validate_selection_id($_POST, "test_id", "Test"))
				&& ($entry = self::validate_selection_id($_POST, "entry_id", "Entry"))
				&& isset($_POST["contents"])))
		{
			Pattern::unset_error_description();
			
			if ($pattern || ($pattern = Pattern::select_by_test_id_entry_id_contents($test->get_test_id(), $entry->get_entry_id(), $contents, false)))
			{
				if (isset($_POST["score"]))
				{
					$pattern->set_score($_POST["score"]);
				}
				
				if (isset($_POST["message"]))
				{
					$pattern->set_message($_POST["message"]);
				}
				
				Session::get()->set_result_assoc($pattern->json_assoc());
				return;
			}
			Session::get()->set_error_assoc("Test-Entry Pattern Modification", Pattern::unset_error_description());
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