<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APITest extends APIBase
{
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (($test = Connection::transact(
				function () use ($unit)
				{
					$name = isset($_POST["name"]) && strlen($_POST["name"]) > 0 ? $_POST["name"] : null;
					$timeframe = isset($_POST["open"]) && isset($_POST["close"]) ? new Timeframe($_POST["open"], $_POST["close"]) : null;
					$message = isset($_POST["message"]) && strlen($_POST["message"]) > 0 ? $_POST["message"] : null;
					$timer = isset($_POST["timer"]) ? intval($_POST["timer"], 10) : null;
					
					if (($test = Test::insert($unit->get_unit_id(), $name, $timeframe, $timer, $message)))
					{
						$errors = 0;
						
						$mode = isset($_POST["mode_id"]) && strlen($_POST["mode_id"]) > 0 ? intval($_POST["mode_id"], 10) : (isset($_POST["mode"]) && strlen($_POST["mode"]) > 0 ? intval($_POST["mode"], 10) : null);
						
						if (isset($_POST["list_ids"]))
						{
							$list_ids = explode(",", $_POST["list_ids"]);
							
							foreach ($list_ids as $list_id)
							{
								if (($list = EntryList::select_by_id($list_id))
									&& $list->session_user_can_read())
								{
									$errors += !$test->entries_add_from_list($list, $mode);
								}
								else 
								{
									$errors ++;
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
									$errors += !$test->entries_add($entry, $mode);
								}
								else 
								{
									$errors ++;
								}
							}
						}
						
						if (!$errors) return $test;
					}
					
					return null;
				}
			))) Session::get()->set_result_assoc($test->json_assoc());
			else Session::get()->set_error_assoc("Test Insertion", Test::errors_unset());
		}
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_GET, "test_id", "Test")))
		{
			if ($test->session_user_can_administer())
			{
				if (($result_assoc = $test->json_assoc_detailed()))
				{
					Session::get()->set_result_assoc($result_assoc);
				}
				else Session::get()->set_error_assoc("Test Selection", Test::errors_unset());
			}
			else
			{
				Session::get()->set_error_assoc("Test Selection", "Session user cannot select test.");
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
				Session::get()->set_error_assoc("Test Deletion", Test::errors_unset());
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
				Session::get()->set_error_assoc("Test Unexecution", Test::errors_unset());
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
			if (Connection::transact(
				function () use ($test)
				{
					$errors = 0;
						
					if (isset($_POST["name"]))
					{
						$errors += !$test->set_name($_POST["name"]);
					}
					
					if (isset($_POST["message"]))
					{
						$errors += !$test->set_message($_POST["message"]);
					}
					
					if (isset($_POST["timer"]))
					{
						$errors += !$test->set_timer($_POST["timer"]);
					}
					
					if (isset($_POST["disclosed"]))
					{
						$errors += !$test->set_disclosed(intval($_POST["disclosed"], 10));
					}
					
					if (isset($_POST["open"]) && isset($_POST["close"]))
					{
						$errors += !$test->set_timeframe(!!$_POST["open"] || !!$_POST["close"] ? new Timeframe($_POST["open"], $_POST["close"]) : null);
					}
					else
					{
						if (isset($_POST["open"]))
						{
							$errors += !$test->set_open($_POST["open"]);
						}
						if (isset($_POST["close"]))
						{
							$errors += !$test->set_close($_POST["close"]);
						}
					}
					
					if ($errors) return null;
					
					return $test;
				}
			)) Session::get()->set_result_assoc($test->json_assoc());
			else Session::get()->set_error_assoc("Test Modification", Test::errors_unset());
		}
	}
	
	public function sittings()
	{
		if (!Session::get()->reauthenticate()) return;
		
		//  session_user_can_read() here?
		if (($test = self::validate_selection_id($_GET, "test_id", "Test"))
				&& $test->session_user_can_administer())
		{
			self::return_array_as_json($test->sittings());
		}
	}
	
	public function entries()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_GET, "test_id", "Test")))
		{
			if ($test->session_user_can_administer())
			{
				if (is_array($entries_json_array = $test->entries_json_array()))
				{
					Session::get()->set_result_assoc($entries_json_array);
				}
				else
				{
					Session::get()->set_error_assoc("Test Entries", Test::errors_unset());
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
			$mode = isset($_POST["mode_id"]) && strlen($_POST["mode_id"]) > 0 ? intval($_POST["mode_id"], 10) : (isset($_POST["mode"]) && strlen($_POST["mode"]) > 0 ? intval($_POST["mode"], 10) : null);
			
			if (Connection::transact(
				function () use ($test, $mode)
				{
					$entries = array ();
					if (isset($_POST["list_ids"]))
					{
						$list_ids = explode(",", $_POST["list_ids"]);
						
						foreach ($list_ids as $list_id)
						{
							if (($list = EntryList::select_by_id($list_id))
								&& $list->session_user_can_read())
							{
								foreach ($list->entries() as $entry)
								{
									if ($entry->in($entries) === null)
									{
										array_push($entries, $entry);
									}
								}
							}
						}
					}
					
					if (isset($_POST["entry_ids"]))
					{
						foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
						{
							if (($entry = Entry::select_by_id($entry_id)))
							{
								if ($entry->in($entries) === null)
								{
									array_push($entries, $entry);
								}
							}
						}
					}
					
					foreach ($entries as $entry)
					{
						$test->entries_add($entry, $mode);
					}
					
					$test->uncache_entries();
					
					return $test;
				}
			)) Session::get()->set_result_assoc($test->entries_json_array());
			else Session::get()->set_error_assoc("Test-Entries Addition", Test::errors_unset());
		}
	}
	
	public function entries_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			if (self::validate_request($_POST, "entry_ids"))
			{
				if (Connection::transact(
					function () use ($test)
					{
						$errors = 0;
						
						foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
						{
							if (!$test->entries_remove(Entry::select_by_id($entry_id)))
							{
								$errors ++;
							}
						}
						
						if ($errors) return null;
						
						return $test;
					}
				)) Session::get()->set_result_assoc($test->entries_json_array());
				else Session::get()->set_error_assoc("Test-Entries Removal", Test::errors_unset());
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
				Session::get()->set_error_assoc("Test-Entries Randomization", Test::errors_unset());
			}
			else Session::get()->set_result_assoc($test->entries_json_array());
		}
	}
	
	public function entry_update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test"))
			&& ($entry = self::validate_selection_id($_POST, "entry_id", "Entry")))
		{
			if (Connection::transact(
				function () use ($test, $entry)
				{
					$errors = 0;
					
					if (isset($_POST["number"]))
					{
						$errors += !$test->set_entry_number($entry, $_POST["number"]);
					}
					
					$mode = isset($_POST["mode_id"]) && strlen($_POST["mode_id"]) > 0 ? intval($_POST["mode_id"], 10) : (isset($_POST["mode"]) && strlen($_POST["mode"]) > 0 ? intval($_POST["mode"], 10) : null);
					if ($mode !== null)
					{
						$errors += !$test->set_entry_mode($entry, $mode);
					}
					
					if (isset($_POST["options"]))
					{
						$options = explode(",", $_POST["options"]);
						
						foreach ($options as $option)
						{
							if (!Pattern::insert($test->get_test_id(), $entry->get_entry_id(), $option, true))
							{
								$errors ++;
							}
						}
						
						foreach ($test->entry_options($entry) as $option)
						{
							if (!in_array($option->get_contents(), $options))
							{
								$errors += !$option->set_prompt(false);
							}
						}
					}
					
					if ($errors) return null;
					
					return $test;
				}
			)) Session::get()->set_result_assoc($test->json_assoc());
			else Session::get()->set_error_assoc("Test Modification", self::errors_collect(array ("Test", "Pattern")));
		}
	}
	
	public function entry_options()
	{
		if (!Session::get()->reauthenticate()) return;
		
		return $this->entry_patterns(true);
	}
	
	public function entry_options_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($pattern = self::validate_selection_id($_POST, "pattern_id", "Pattern"))
			|| (($test = self::validate_selection_id($_POST, "test_id", "Test"))
				&& ($entry = self::validate_selection_id($_POST, "entry_id", "Entry"))
				&& isset($_POST["contents"])))
		{
			Pattern::errors_unset();
			
			if ($pattern || ($pattern = Pattern::insert($test->get_test_id(), $entry->get_entry_id(), $_POST["contents"], true)))
			{
				if ($pattern->set_prompt(true))
				{
					Session::get()->set_result_assoc($pattern->json_assoc());
				}
				else Session::get()->set_error_assoc("Test-Entry Pattern Modification", Pattern::errors_unset());
			}
			else Session::get()->set_error_assoc("Test-Entry Pattern Modification", Pattern::errors_unset());
		}
	}
	
	public function entry_options_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($pattern = self::validate_selection_id($_POST, "pattern_id", "Pattern"))
			|| (($test = self::validate_selection_id($_POST, "test_id", "Test"))
				&& ($entry = self::validate_selection_id($_POST, "entry_id", "Entry"))
				&& isset($_POST["contents"])))
		{
			Pattern::errors_unset();
			
			if ($pattern || ($pattern = Pattern::select_by_test_id_entry_id_contents_mode($test->get_test_id(), $entry->get_entry_id(), $_POST["contents"])))
			{
				if ($pattern->set_prompt(false))
				{
					Session::get()->set_result_assoc($pattern->json_assoc());
				}
				else Session::get()->set_error_assoc("Test-Entry Pattern Modification", Pattern::errors_unset());
			}
			else Session::get()->set_error_assoc("Test-Entry Pattern Modification", Pattern::errors_unset());
		}
	}
	
	public function entry_patterns($prompts_only = false)
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_GET, "test_id", "Test"))
			&& ($entry = self::validate_selection_id($_GET, "entry_id", "Entry")))
		{
			if (($test_entry_id = $test->get_test_entry_id_for_entry($entry)) > 0)
			{
				if ($test->session_user_can_administer())
				{
					self::return_array_as_json(Pattern::select_all_for_test_entry_id($test_entry_id, $prompts_only));
				}
				else
				{
					;
				}
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
			Pattern::errors_unset();
			
			if ($pattern || ($pattern = Pattern::select_by_test_id_entry_id_contents_mode($test->get_test_id(), $entry->get_entry_id(), $_POST["contents"])))
			{
				if (Connection::transact(
					function () use ($pattern)
					{
						$errors = 0;
						
						if (isset($_POST["score"]))
						{
							$errors += !$pattern->set_score($_POST["score"]);
						}
						
						if (isset($_POST["message"]))
						{
							$errors += !$pattern->set_message($_POST["message"]);
						}
						
						if ($errors) return null;
						
						return $pattern;
					}
				))
				{
					Session::get()->set_result_assoc($pattern->json_assoc());
					return;
				}
			}
			Session::get()->set_error_assoc("Test-Entry Pattern Modification", Pattern::errors_unset());
		}
	}
	
	public function execute()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($test = self::validate_selection_id($_POST, "test_id", "Test")))
		{
			if (($result = Connection::transact(
				function () use ($test)
				{
					if (!($sitting = $test->execute_for_session_user())) return null;
					
					$prev_json_assoc = null;
					if (isset($_POST["test_entry_id"]) && isset($_POST["contents"]))
					{
						if (!($response = Response::insert($_POST["test_entry_id"], $_POST["contents"])))
						{
							return null;
						}
						else $prev_json_assoc = $response->json_assoc();
					}
					
					$next_json_assoc = $sitting->next_json_assoc();
					
					return array ("prev" => $prev_json_assoc, "next" => $next_json_assoc);
				}
			))) Session::get()->set_result_assoc($result);
			else Session::get()->set_error_assoc("Test Execution", self::errors_collect(array ("Test", "Sitting", "Response")));
		}
	}
}

?>
