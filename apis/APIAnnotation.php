<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIAnnotation extends APIBase
{
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($annotation = self::validate_selection_id($_GET, "annotation_id", "Annotation")))
		{
			Session::get()->set_result_assoc($annotation->json_assoc_detailed());
		}
	}
	
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($entry = self::validate_selection_id($_POST, "entry_id", "Entry")))
		{
			if (self::validate_request($_POST, "contents"))
			{
				if (!($entry = $entry->copy_for_session_user()))
				{
					Session::get()->set_error_assoc("Annotation Insertion", Entry::errors_unset());
				}
				else if (!Annotation::insert($entry->get_user_entry_id(), $_POST["contents"]))
				{
					Session::get()->set_error_assoc("Annotation Insertion", Annotation::errors_unset());
				}
				else
				{
					Session::get()->set_result_assoc($entry->json_assoc());
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
				Session::get()->set_error_assoc("Annotation Deletion", Annotation::errors_unset());
			}
			else
			{
				Session::get()->set_result_assoc($annotation->json_assoc());
			}
		}
	}
}
?>