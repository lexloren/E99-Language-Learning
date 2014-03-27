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
		// word_0
		// word_1
		// word_1_pronun
	}
	
	private function validate_entry_id($entry_id)
	{
		$entry = null;
		if (!isset($entry_id))
		{
			Session::get()->set_error_assoc("Request Invalid", "Request must include entry_id.");
		}
		else if (!($entry = Entry::select_by_id(($entry_id = intval($entry_id, 10)))))
		{
			Session::get()->set_error_assoc("Entry Selection", Entry::get_error_description());
		}
		
		return $entry;
	}
	
	public function annotations()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($entry = $this->validate_entry_id($_GET["entry_id"])))
		{
			$entry = $entry->copy_for_session_user();
			
			$this->return_array_as_assoc_for_json($entry->get_annotations());
		}
	}
}
?>