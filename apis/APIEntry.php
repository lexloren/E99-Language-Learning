<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIEntry extends APIBase
{
	public function __construct($user, $mysqli)
	{
		parent::__construct($user, $mysqli);
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($entry = self::validate_selection_id($_GET, "entry_id", "Entry")))
		{
			Session::get()->set_result_assoc($entry->json_assoc_detailed(false));
		}
	}
	
	public function update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($entry = self::validate_selection_id($_POST, "entry_id", "Entry")))
		{
			$entry = $entry->copy_for_session_user();
			
			$updates = 0;
			
			if (isset($_POST["word_0"]))
			{
				$updates += !!$entry->set_word_0($_POST["word_0"]);
			}
			
			if (isset($_POST["word_1"]))
			{
				$updates += !!$entry->set_word_1($_POST["word_1"]);
			}
			
			if (isset($_POST["word_1_pronun"]))
			{
				$updates += !!$entry->set_word_1_pronunciation($_POST["word_1_pronun"]);
			}
			
			self::return_updates_as_json("Entry", Entry::unset_error_description(), $updates ? $entry->json_assoc() : null);
		}
	}
	
	public function find()
	{
		if (self::validate_request($_GET, array ("query", "langs")))
		{
			$pagination = null;
			if (isset($_GET["page_size"]) && isset($_GET["page_num"]))
			{
				$pagination = array (
					"size" => $_GET["page_size"],
					"num" => $_GET["page_num"]
				);
			}
			
			$exact_matches_only = isset($_GET["exact"]) ? !!intval($_GET["exact"], 10) : false;

			if (($entries = Dictionary::query($_GET["query"], explode(",", $_GET["langs"]), $pagination, $exact_matches_only)))
			{
				self::return_array_as_json($entries, null, array ("pageSize" => Dictionary::$page_size, "pageNumber" => Dictionary::$page_num, "pagesCount" => Dictionary::$pages_count, "entriesFoundCount" => Dictionary::$find_last_count));
			}
			else
			{
				Session::get()->set_error_assoc("Entry Find", Dictionary::unset_error_description());
			}
		}
	}
	
	public function annotations()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($entry = self::validate_selection_id($_GET, "entry_id", "Entry")))
		{
			$entry = $entry->copy_for_session_user();
			
			self::return_array_as_json($entry->get_annotations());
		}
	}
}
?>