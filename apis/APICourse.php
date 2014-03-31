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
		
		if (self::validate_request($_POST, array ("lang_known", "lang_unknw")))
		{
			if (Dictionary::get_lang_id($_POST["lang_known"]) === null
				|| Dictionary::get_lang_id($_POST["lang_unknw"]) === null)
			{
				Session::get()->set_error_assoc("Course Insertion", "Posted lang_known and lang_unknw must be valid two-letter codes within the application.");
			}
			else
			{
				if (!($course = Course::insert($_POST["lang_known"], $_POST["lang_unknw"], isset($_POST["course_name"]) ? $_POST["course_name"] : null)))
				{
					Session::get()->set_error_assoc("Course Insertion", COurse::get_error_description());
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
		//  message
		
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if ($course->session_user_can_write())
			{
				$updates = 0;
				
				if (isset($_POST["course_name"]))
				{
					$updates += !!$course->set_course_name($_POST["course_name"]);
				}
				
				if (isset($_POST["message"]))
				{
					$updates += !!$course->set_message($_POST["message"]);
				}
				
				if (!$updates)
				{
					$failure_message = !!Course::get_error_description() ? Course::get_error_description() : "Course failed to update.";
					Session::get()->set_error_assoc("Course Modification", $failure_message);
				}
				else
				{
					Session::get()->set_result_assoc($course->assoc_for_json());
				}
			}
		}
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (!$course->delete())
			{
				Session::get()->set_error_assoc("Course Deletion", Course::get_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($course->assoc_for_json());
			}
		}
	}
	
	public function lists()
	{
		if (!Session::get()->reauthenticate()) return;
		
		//  session_user_can_read() here?
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_assoc_for_json($course->get_lists());
		}
	}
	
	public function tests()
	{
		if (!Session::get()->reauthenticate()) return;
		
		//  session_user_can_read() here?
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_assoc_for_json($course->get_tests());
		}
	}
	
	public function units()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_assoc_for_json($course->get_units());
		}
	}
	
	public function students()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_assoc_for_json($course->get_students());
		}
	}
	
	public function students_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (self::validate_request($_POST, "user_ids"))
			{
				if ($course->session_user_can_write())
				{
					foreach (explode(",", $_POST["user_ids"]) as $user_id)
					{
						if (!$course->students_add(User::select_by_id($user_id)))
						{
							Session::get()->set_error_assoc("Course-Students Addition", Course::get_error_description());
							return;
						}
					}
					
					Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
				else
				{
					Session::get()->set_error_assoc("Course Edit", "Session user is not course instructor.");
				}
			}
		}
	}
	
	public function instructors()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_assoc_for_json($course->get_instructors());
		}
	}
	
	public function instructors_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (self::validate_request($_POST, "user_ids"))
			{
				if ($course->session_user_is_owner())
				{
					foreach (explode(",", $_POST["user_ids"]) as $user_id)
					{
						if (!$course->instructors_add(User::select_by_id($user_id)))
						{
							Session::get()->set_error_assoc("Course-Instructors Addition", Course::get_error_description());
							return;
						}
					}
					
					Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
				else
				{
					Session::get()->set_error_assoc("Course Modification", "Session user is not course owner.");
				}
			}
		}
	}
	
	public function students_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (self::validate_request($_POST, "user_ids"))
			{
				if ($course->session_user_can_write())
				{
					foreach (explode(",", $_POST["user_ids"]) as $user_id)
					{
						if (!$course->students_remove(User::select_by_id($user_id)))
						{
							Session::get()->set_error_assoc("Course-Students Removal", Course::get_error_description());
							return;
						}
					}
					
					Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
				else
				{
					Session::get()->set_error_assoc("Course Edit", "Session user is not course instructor.");
				}
			}
		}
	}
	
	public function instructors_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (self::validate_request($_POST, "user_ids"))
			{
				if ($course->session_user_is_owner())
				{
					foreach (explode(",", $_POST["user_ids"]) as $user_id)
					{
						if (!$course->instructors_remove(User::select_by_id($user_id)))
						{
							Session::get()->set_error_assoc("Course-Instructors Removal", Course::get_error_description());
							return;
						}
					}
					
					Session::get()->set_result_assoc($course->assoc_for_json());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
				else
				{
					Session::get()->set_error_assoc("Course Edit", "Session user is not course owner.");
				}
			}
		}
	}
}

?>