<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APICourse extends APIBase
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
				$timeframe = isset($_POST["open"]) && isset($_POST["close"]) ? new Timeframe($_POST["open"], $_POST["close"]) : null;
				$message = isset($_POST["message"]) && strlen($_POST["message"]) > 0 ? $_POST["message"] : null;
				$public = isset($_POST["public"]) ? !!intval($_POST["public"], 10) : false;
				
				if (!($course = Course::insert($_POST["lang_known"], $_POST["lang_unknw"], isset($_POST["name"]) ? $_POST["name"] : null, $timeframe, $message, $public)))
				{
					Session::get()->set_error_assoc("Course Insertion", Course::unset_error_description());
				}
				else
				{
					Session::get()->set_result_assoc($course->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
				}
			}
		}
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")))
		{
			Session::get()->set_result_assoc($course->detailed_json_assoc(false));
		}
	}
	
	public function update()
	{
		//  name
		//  timeframe
		//  message
		//  public
		
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			$updates = 0;
				
			if (isset($_POST["name"]))
			{
				$updates += !!$course->set_course_name($_POST["name"]);
			}
			
			if (isset($_POST["message"]))
			{
				$updates += !!$course->set_message($_POST["message"]);
			}
			
			if (isset($_POST["public"]))
			{
				$updates += !!$course->set_public(intval($_POST["public"], 10));
			}
			
			if (isset($_POST["open"]) && isset($_POST["close"]))
			{
				$updates += !!$course->set_timeframe(!!$_POST["open"] || !!$_POST["close"] ? new Timeframe($_POST["open"], $_POST["close"]) : null);
			}
			else
			{
				if (isset($_POST["open"]))
				{
					$updates += !!$course->set_open($_POST["open"]);
				}
				if (isset($_POST["close"]))
				{
					$updates += !!$course->set_close($_POST["close"]);
				}
			}
			
			self::return_updates_as_json("Course", Course::unset_error_description(), $updates ? $course->json_assoc() : null);
		}
	}
	
	public function find()
	{
		if (!Session::get()->reauthenticate()) return;

		if (self::validate_request($_GET, "query"))
		{
			$exact_matches_only = isset($_GET["exact"]) ? !!intval($_GET["exact"], 10) : false;
			$lang = isset($_GET["langs"]) ? explode(",", $_GET["langs"]) : array();

			self::return_array_as_json(Course::find($_GET["query"], $lang, $exact_matches_only));
		}
	}

	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (!$course->delete())
			{
				Session::get()->set_error_assoc("Course Deletion", Course::unset_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($course->json_assoc());
			}
		}
	}
	
	public function lists()
	{
		if (!Session::get()->reauthenticate()) return;
		
		//  session_user_can_read() here?
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_json($course->get_lists());
		}
	}
	
	public function tests()
	{
		if (!Session::get()->reauthenticate()) return;
		
		//  session_user_can_read() here?
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_json($course->get_tests());
		}
	}
	
	public function units()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_json($course->get_units());
		}
	}
	
	public function students()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_json($course->get_students());
		}
	}
	
	public function students_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (self::validate_request($_POST, "user_ids"))
			{
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->students_add(User::select_by_id($user_id)))
					{
						Session::get()->set_error_assoc("Course-Students Addition", Course::unset_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($course->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function instructors()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_GET, "course_id", "Course")) && $course->session_user_can_read())
		{
			self::return_array_as_json($course->get_instructors());
		}
	}
	
	public function instructors_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (self::validate_request($_POST, "user_ids"))
			{
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->instructors_add(User::select_by_id($user_id)))
					{
						Session::get()->set_error_assoc("Course-Instructors Addition", Course::unset_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($course->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
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
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->students_remove(User::select_by_id($user_id)))
					{
						Session::get()->set_error_assoc("Course-Students Removal", Course::unset_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($course->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
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
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->instructors_remove(User::select_by_id($user_id)))
					{
						Session::get()->set_error_assoc("Course-Instructors Removal", Course::unset_error_description());
						return;
					}
				}
				
				Session::get()->set_result_assoc($course->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
}

?>
