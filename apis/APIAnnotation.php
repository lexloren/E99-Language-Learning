<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIAnnotation extends APIBase
{
	public function __construct($user, $mysqli)
	{
		parent::__construct($user, $mysqli);
	}
	
	private function validate_entry_id($entry_id)
	{
		$entry = null;
		if (!isset($entry_id))
		{
			Session::get()->set_error_assoc("Invalid Request", "Request must include entry_id.");
		}
		else if (!($entry = Entry::select_by_id(($entry_id = intval($entry_id, 10)))))
		{
			Session::get()->set_error_assoc("Unknown Entry", "Back end failed to select entry with entry_id = $entry_id.");
		}
		
		return $entry;
	}
	
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($entry = $this->validate_entry_id($_POST["entry_id"])))
		{
			if (!isset($_POST["contents"]))
			{
				Session::get()->set_error_assoc("Invalid Post", "Annotation-insertion post must include entry_id and contents.");
			}
			else
			{
				$entry = $entry->copy_for_session_user();
				
				if (!$entry->annotations_add($_POST["contents"]))
				{
					Session::get()->set_error_assoc("Annotation Insertion", Entry::get_error_description());
				}
				else
				{
					Session::get()->set_result_assoc($entry->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
			}
		}
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (!isset($_POST["annotation_id"]))
		{
			Session::get()->set_error_assoc("Invalid Request", "Request must include annotation_id.");
		}
		else if (($annotation = Annotation::select_by_id($_POST["annotation_id"])))
		{
			$annotation->delete();
			Session::get()->set_result_assoc($annotation->assoc_for_json());//, Session::get()->database_result_assoc(array ("didDelete" => true)));
		}
	}
}
?>