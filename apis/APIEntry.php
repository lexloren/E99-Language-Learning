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
	
	public function annotations()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($entry = self::validate_selection_id($_GET, "entry_id", "Entry")))
		{
			$entry = $entry->copy_for_session_user();
			
			$this->return_array_as_assoc_for_json($entry->get_annotations());
		}
	}
}
?>