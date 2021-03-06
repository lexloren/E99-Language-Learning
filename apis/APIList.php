<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIList extends APIBase
{
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (!($list = EntryList::insert(isset($_POST["name"]) ? $_POST["name"] : null)))
		{
			Session::get()->set_error_assoc("List Insertion", EntryList::errors_unset());
		}
		else
		{
			Session::get()->set_result_assoc($list->json_assoc());
		}
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = self::validate_selection_id($_GET, "list_id", "EntryList")))
		{
			Session::get()->set_result_assoc($list->json_assoc_detailed());
		}
	}
	
	public function update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = self::validate_selection_id($_POST, "list_id", "EntryList")))
		{
			if (Connection::transact(
				function () use ($list)
				{
					$errors = 0;
				
					if (isset($_POST["name"]))
					{
						$errors += !$list->set_name($_POST["name"]);
					}
					
					if ($errors) return null;
					
					return $list;
				}
			)) Session::get()->set_result_assoc($list->json_assoc());
			else Session::get()->set_error_assoc("List Modification", EntryList::errors_unset());
		}
	}

	public function find()
	{
		if (!Session::get()->reauthenticate()) return;
		
		$lists = array ();
		if (isset($_GET["list_ids"]))
		{
			foreach (explode(",", $_GET["list_ids"]) as $list_id)
			{
				if (($list = EntryList::select_by_id($list_id))
					&& ($list->session_user_can_read()))
				{
					$lists[$list_id] = $list;
				}
			}
		}
		
		if (isset($_GET["user_ids"]))
		{
			if (is_array($more = EntryList::find_by_user_ids(explode(",", $_GET["user_ids"]))))
			{
				$lists = array_merge($lists, $more);
			}
			else
			{
				Session::get()->set_error_assoc("List Find", EntryList::errors_unset());
				return;
			}
		}
		
		if (isset($_GET["user_query"]))
		{
			if (is_array($more = EntryList::find_by_user_query(explode(",", $_GET["user_query"]))))
			{
				$lists = array_merge($lists, $more);
			}
			else
			{
				Session::get()->set_error_assoc("List Find", EntryList::errors_unset());
				return;
			}
		}
		
		if (isset($_GET["entry_ids"]))
		{
			if (is_array($more = EntryList::find_by_entry_ids(explode(",", $_GET["entry_ids"]))))
			{
				$lists = array_merge($lists, $more);
			}
			else
			{
				Session::get()->set_error_assoc("List Find", EntryList::errors_unset());
				return;
			}
		}
		
		if (isset($_GET["langs"]))
		{
			if (isset($_GET["entry_query"]))
			{
				if (is_array($more = EntryList::find_by_entry_query(explode(",", $_GET["entry_query"]), explode(",", $_GET["langs"]))))
				{
					$lists = array_merge($lists, $more);
				}
				else
				{
					Session::get()->set_error_assoc("List Find", EntryList::errors_unset());
					return;
				}
			}
			
			/*
			if (is_array($more = Course::find_by_languages(explode(",", $_GET["langs"]))))
			{
				$courses = array_merge($courses, $more);
			}
			else
			{
				Session::get()->set_error_assoc("Course Find", Course::errors_unset());
				return;
			}
			*/
		}
		
		self::return_array_as_json($lists);
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;

		if (($list = self::validate_selection_id($_POST, "list_id", "EntryList")))
		{
			if (!$list->delete())
			{
				Session::get()->set_error_assoc("List Deletion", EntryList::errors_unset());
			}
			else
			{
				Session::get()->set_result_assoc($list->json_assoc());
			}
		}
	}
	
	public function entries()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = self::validate_selection_id($_GET, "list_id", "EntryList")))
		{
			if ($list->session_user_can_read())
			{
				$entries = $list->entries();
			
				$entries_returnable = array ();
				foreach ($entries as $entry)
				{
					array_push($entries_returnable, $entry->json_assoc());
				}
				
				Session::get()->set_result_assoc($entries_returnable);
			}
			else
			{
				Session::get()->set_error_assoc("List Selection", "Session user cannot read list.");
			}
		}
	}
	
	public function entries_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = self::validate_selection_id($_POST, "list_id", "EntryList")))
		{
			if (Connection::transact(
				function () use ($list)
				{
					foreach (APIBase::collect_entries() as $entry)
					{
						if (!$list->entries_add($entry)) return null;
					}
					
					return $list;
				}
			)) self::return_array_as_json($list->entries());
			else Session::get()->set_error_assoc("List Modification", EntryList::errors_unset());
		}
	}
	
	public function entries_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = self::validate_selection_id($_POST, "list_id", "EntryList")))
		{
			if (self::validate_request($_POST, "entry_ids"))
			{
				if (Connection::transact(
					function () use ($list)
					{
						foreach (APIBase::collect_entries() as $entry)
						{
							if (!$list->entries_remove($entry)) return null;
						}
						
						return $list;
					}
				)) self::return_array_as_json($list->entries());
				else Session::get()->set_error_assoc("List-Entries Removal", EntryList::errors_unset());
			}
		}
	}
}
?>
