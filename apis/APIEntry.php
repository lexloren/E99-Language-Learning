<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIEntry extends APIBase
{
	public function __construct($user, $mysqli)
	{
		parent::__construct($user, $mysqli);
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
			
			self::return_updates_as_json("Entry", Entry::get_error_description(), $updates ? $entry->assoc_for_json() : null);
		}
	}
	
	public function find()
	{
		if (self::validate_request($_GET, array ("query", "langs")))
		{
			$langs = $_GET["langs"];		
			$pagination = null;
			if (isset($_GET["page_size"]) && isset($_GET["page_num"]))
			{
				$pagination = array (
					"size" => $_GET["page_size"],
					"num" => $_GET["page_num"]
				);
			}

			if (($entries = Dictionary::query($_GET["query"], $langs, $pagination)))
			{
				self::return_array_as_json($entries);
			}
			else
			{
				Session::get()->set_error_assoc("Entry Find", Dictionary::get_error_description());
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