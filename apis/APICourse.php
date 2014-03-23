<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APICourse  extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}
	
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (!isset($_POST["lang_known"]) || !isset($_POST["lang_unknw"]))
		{
			Session::get()->set_error_assoc("Invalid Post", "Course-insertion post must include lang_known and lang_unknw.");
		}
		else
		{
			if (Dictionary::get_lang_id($_POST["lang_known"]) === null
				|| Dictionary::get_lang_id($_POST["lang_unknw"]) === null)
			{
				Session::get()->set_error_assoc("Unknown Language(s)", "Posted lang_known and lang_unknw must be valid two-letter codes within the application.");
			}
			else
			{
				if (!($course = Course::insert($_POST["lang_known"], $_POST["lang_unknw"], isset($_POST["course_name"]) ? $_POST["course_name"] : null)))
				{
					$error_description = sprintf("Back end unexpectedly failed to insert course%s",
						!!Course::get_error_description() ? (": " . Course::get_error_description()) : "."
					);
					Session::get()->set_error_assoc("Course Insertion", $error_description);
				}
				else
				{
					Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
			}
		}
	}
	
	public function update()
	{
		//  course_name
		//  timeframe
		//  user_id (owner of the course)
	}
	
	private function validate_course_id($course_id)
	{
		$course = null;
		if (!isset($course_id))
		{
			Session::get()->set_error_assoc("Invalid Request", "Request must include course_id.");
		}
		else if (!($course = Course::select(($course_id = intval($course_id, 10)))))
		{
			Session::get()->set_error_assoc("Unknown Course", "Back end failed to select course with posted course_id = $course_id.");
		}
		
		return $course;
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = $this->validate_course_id($_POST["course_id"])))
		{
			$course->delete();
			Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didDelete" => true)));
		}
	}
	
	public function lists()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = $this->validate_course_id($_GET["course_id"])))
		{
			$this->return_array_as_assoc_for_json($course->get_lists());
		}
	}
	
	public function units()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = $this->validate_course_id($_GET["course_id"])))
		{
			$this->return_array_as_assoc_for_json($course->get_units());
		}
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
		if (!Session::get()->reauthenticate())
			return;
		Session::get()->set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}
	*/
}

?>