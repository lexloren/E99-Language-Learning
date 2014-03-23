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
			Session::get()->set_error_assoc("Unknown Course", "Back end failed to select course with course_id = $course_id.");
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
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = $this->validate_course_id($_POST["course_id"])))
		{
			if (!isset($_POST["user_ids"]))
			{
				Session::get()->set_error_assoc("Invalid Post", "Course–add-students post must include course_id and user_ids.");
			}
			else if (Session::get()->get_user()->in_array($course->get_instructors()))
			{
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->add_student(User::select($user_id)))
					{
						Session::get()->set_error_assoc("Course-Students Addition", Course::get_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
			else
			{
				Session::get()->set_error_assoc("Course Edit", "Back end failed to add students to course.");
			}
		}
	}
	
	public function instructors_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = $this->validate_course_id($_POST["course_id"])))
		{
			if (!isset($_POST["user_ids"]))
			{
				Session::get()->set_error_assoc("Invalid Post", "Course–add-instructors post must include course_id and user_ids.");
			}
			else if ($course->get_owner()->equals(Session::get()->get_user()))
			{
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->add_instructor(User::select($user_id)))
					{
						Session::get()->set_error_assoc("Course-Instructors Addition", Course::get_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
			else
			{
				Session::get()->set_error_assoc("Course Edit", "Back end failed to add instructors to course.");
			}
		}
	}
	
	public function students_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = $this->validate_course_id($_POST["course_id"])))
		{
			if (!isset($_POST["user_ids"]))
			{
				Session::get()->set_error_assoc("Invalid Post", "Course–remove-students post must include course_id and user_ids.");
			}
			else if (Session::get()->get_user()->in_array($course->get_instructors()))
			{
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->remove_student(User::select($user_id)))
					{
						Session::get()->set_error_assoc("Course-Students Removal", Course::get_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
			else
			{
				Session::get()->set_error_assoc("Course Edit", "Back end failed to remove students from course.");
			}
		}
	}
	
	public function instructors_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = $this->validate_course_id($_POST["course_id"])))
		{
			if (!isset($_POST["user_ids"]))
			{
				Session::get()->set_error_assoc("Invalid Post", "Course–remove-instructors post must include course_id and user_ids.");
			}
			else if ($course->get_owner()->equals(Session::get()->get_user()))
			{
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->remove_instructor(User::select($user_id)))
					{
						Session::get()->set_error_assoc("Course-Instructors Removal", Course::get_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
			else
			{
				Session::get()->set_error_assoc("Course Edit", "Back end failed to remove instructors from course.");
			}
		}
	}
}

?>