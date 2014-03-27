<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIAnnotation extends APIBase
{
	public function __construct($user, $mysqli)
	{
		parent::__construct($user, $mysqli);
	}
	
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($entry = self::validate_selection_id($_POST, "entry_id", "Entry")))
		{
			if (!isset($_POST["contents"]))
			{
				Session::get()->set_error_assoc("Request Invalid", "Annotation-insertion post must include entry_id and contents.");
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
		
		if (($annotation = self::validate_selection_id($_POST, "annotation_id", "Annotation")))
		{
			if (!$annotation->delete())
			{
				Session::get()->set_error_assoc("Annotation Deletion", Annotation::get_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($annotation->assoc_for_json());
			}
		}
	}
}
?>