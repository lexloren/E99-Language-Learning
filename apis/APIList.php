<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIList extends APIBase
{
	public function __construct($user, $mysqli) {	
		parent::__construct($user, $mysqli);
	}
	
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (!($list = EntryList::insert(isset($_POST["name"]) ? $_POST["name"] : null)))
		{
			Session::get()->set_error_assoc("List Insertion", EntryList::unset_error_description());
		}
		else
		{
			Session::get()->set_result_assoc($list->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
		}
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = self::validate_selection_id($_GET, "list_id", "EntryList")))
		{
			Session::get()->set_result_assoc($list->detailed_json_assoc(false));
		}
	}
	
	public function update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = self::validate_selection_id($_POST, "list_id", "EntryList")))
		{
			if (($list = $list->copy_for_session_user()))
			{
				$updates = 0;
				
				if (isset($_POST["name"]))
				{
					$updates += !!$list->set_list_name($_POST["name"]);
				}
				
				self::return_updates_as_json("List", EntryList::unset_error_description(), $updates ? $list->json_assoc() : null);
			}
			else
			{
				Session::get()->set_error_assoc("List Modification", EntryList::unset_error_description());
			}
		}
	}

        public function find()
        {
                if (!Session::get()->reauthenticate()) return;

                if (self::validate_request($_GET, "query"))
                {
                        $exact_matches_only = isset($_GET["exact"]) ? !!intval($_GET["exact"], 10) : false;
                        self::return_array_as_json(EntryList::find($_GET["query"], $exact_matches_only));
                }
        }
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;

		if (($list = self::validate_selection_id($_POST, "list_id", "EntryList")))
		{
			if (!$list->delete())
			{
				Session::get()->set_error_assoc("List Deletion", EntryList::unset_error_description());
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
				$entries = $list->get_entries();
			
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
			if (self::validate_request($_POST, "entry_ids"))
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					if (!$list->entries_add(Entry::select_by_id($entry_id)))
					{
						Session::get()->set_error_assoc("List-Entries Addition", EntryList::unset_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($list->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function entries_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($list = self::validate_selection_id($_POST, "list_id", "EntryList")))
		{
			if (self::validate_request($_POST, "entry_ids"))
			{
				foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
				{
					if (!$list->entries_remove(Entry::select_by_id($entry_id)))
					{
						Session::get()->set_error_assoc("List-Entries Removal", EntryList::unset_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($list->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
}
?>
