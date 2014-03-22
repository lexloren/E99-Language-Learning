<?php

require_once "./APIBase.php";
require_once "./backend/classes.php";

class APICourse  extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}
	
	public function insert()
	{
		if (!Session::reauthenticate()) return;
		
		if (!isset($_POST["lang_known"]) || !isset($_POST["lang_unknw"]))
		{
			Session::set_error_assoc("Invalid Post", "Course-insertion post must include lang_known and lang_unknw.");
		}
		else
		{
			if (Dictionary::get_lang_id($_POST["lang_known"]) === null
				|| Dictionary::get_lang_id($_POST["lang_unknw"]) === null)
			{
				Session::set_error_assoc("Unknown Language(s)", "Posted lang_known and lang_unknw must be valid two-letter codes within the application.");
			}
			else
			{
				if (!($course = Course::insert($_POST["lang_known"], $_POST["lang_unknw"], isset($_POST["course_name"]) ? $_POST["course_name"] : null)))
				{
					$error_description = sprintf("Back end unexpectedly failed to insert course%s",
						!!Course::get_error_description() ? (": " . Course::get_error_description()) : "."
					);
					Session::set_error_assoc("Course Insertion", $error_description);
				}
				else
				{
					Session::set_result_assoc($course->assoc_for_json());//, Session::database_result_assoc(array ("didInsert" => true)));
				}
			}
		}
	}
	
	public function delete()
	{
		if (!Session::reauthenticate()) return;

		if (!isset($_POST["course_id"]))
		{
			Session::set_error_assoc("Invalid Post", "Course-deletion post must include list_id.");
		}
		else if (!($course = Course::select(($course_id = intval($_POST["course_id"], 10)))))
		{
			Session::set_error_assoc("Unknown Course", "Back end failed to select course for deletion with posted course_id = $course_id.");
		}
		else
		{
			$course->delete();
			Session::set_result_assoc($course->assoc_for_json());//, Session::database_result_assoc(array ("didDelete" => true)));
		}
	}
	
	public function lists()
	{
	
	}
	
	public function units()
	{
	
	}
	
	public function timeframe_update()
	{
	
	}
	
	public function name_update()
	{
	
	}
	
	public function students_add()
	{
	
	}
	
	public function instructors_add()
	{
	
	}
	
	public function students_remove()
	{
	
	
	}
	
	public function instructors_remove()
	{
	
	}
	
	/*
	//  Maybe just call APIUnit.insert from here (?)
	public function units_add()
	{
	
	}
	
	//  Maybe just call APIUnit.delete from here (?)
	public function units_remove()
	{
	
	}
	
	//  DEPRECATED (?)
	//      I'm not sure whether we actually need this method.
	public function select()
	{
		if (!Session::reauthenticate())
			return;
		Session::set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}
	*/
}

?>