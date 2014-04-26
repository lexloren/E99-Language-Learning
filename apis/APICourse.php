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
		
		$courses = array ();
		if (isset($_GET["course_ids"]))
		{
			foreach (explode(",", $_GET["course_ids"]) as $course_id)
			{
				if (($course = Course::select_by_id($course_id))
					&& ($course->session_user_can_read()))
				{
					$courses[$course_id] = $course;
				}
			}
		}
		
		if (isset($_GET["user_ids"]))
		{
			if (is_array($more = Course::find_by_user_ids(explode(",", $_GET["user_ids"]))))
			{
				$courses = array_merge($courses, $more);
			}
			else
			{
				Session::get()->set_error_assoc("Course Find", Course::unset_error_description());
				return;
			}
		}
		
		if (isset($_GET["user_query"]))
		{
			if (is_array($more = Course::find_by_user_query(explode(",", $_GET["user_query"]))))
			{
				$courses = array_merge($courses, $more);
			}
			else
			{
				Session::get()->set_error_assoc("Course Find", Course::unset_error_description());
				return;
			}
		}
		
		if (isset($_GET["entry_ids"]))
		{
			if (is_array($more = Course::find_by_entry_ids(explode(",", $_GET["entry_ids"]))))
			{
				$courses = array_merge($courses, $more);
			}
			else
			{
				Session::get()->set_error_assoc("Course Find", Course::unset_error_description());
				return;
			}
		}
		
		if (isset($_GET["langs"]))
		{
			if (isset($_GET["entry_query"]))
			{
				if (is_array($more = Course::find_by_entry_query(explode(",", $_GET["entry_query"]), explode(",", $_GET["langs"]))))
				{
					$courses = array_merge($courses, $more);
				}
				else
				{
					Session::get()->set_error_assoc("Course Find", Course::unset_error_description());
					return;
				}
			}
			
			if (is_array($more = Course::find_by_languages(explode(",", $_GET["langs"]))))
			{
				$courses = array_merge($courses, $more);
			}
			else
			{
				Session::get()->set_error_assoc("Course Find", Course::unset_error_description());
				return;
			}
		}
		
		if (isset($_GET["opened"]) && intval($_GET["opened"], 10) == 1)
		{
			$courses_to_keep = array ();
			foreach ($courses as $course)
			{
				if (!$course->get_timeframe() || $course->get_timeframe()->is_opened())
				{
					array_push($courses_to_keep, $course);
				}
			}
			$courses = $courses_to_keep;
		}
		
		if (isset($_GET["closed"]) && intval($_GET["closed"], 10) == 1)
		{
			$courses_to_keep = array ();
			foreach ($courses as $course)
			{
				if (!$course->get_timeframe() || $course->get_timeframe()->is_closed())
				{
					array_push($courses_to_keep, $course);
				}
			}
			$courses = $courses_to_keep;
		}
		
		self::return_array_as_json($courses);
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
					if (!$course->students_add(($student = User::select_by_id($user_id))))
					{
						Session::get()->set_error_assoc("Course-Students Addition", Course::unset_error_description());
						return;
					}
					Outbox::send($course, $student->get_email(), "Xenogloss: " . $course->get_course_name(), "Dear " . $student->get_handle() . ",\n\nAn instructor has enrolled you in " . $course->get_course_name() . ".\n\nYours,\nThe Xenogloss Team");
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
					if (!$course->instructors_add(($instructor = User::select_by_id($user_id))))
					{
						Session::get()->set_error_assoc("Course-Instructors Addition", Course::unset_error_description());
						return;
					}
					Outbox::send($course, $instructor->get_email(), "Xenogloss: " . $course->get_course_name(), "Dear " . $instructor->get_handle() . ",\n\nThe course owner has designated you as an instructor in " . $course->get_course_name() . ".\n\nYours,\nThe Xenogloss Team");
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
					if (!$course->students_remove(($instructor = User::select_by_id($user_id))))
					{
						Session::get()->set_error_assoc("Course-Students Removal", Course::unset_error_description());
						return;
					}
					Outbox::send($course, $instructor->get_email(), "Xenogloss: " . $course->get_course_name(), "Dear " . $instructor->get_handle() . ",\n\nThe course owner has undesignated you as an instructor in " . $course->get_course_name() . ".\n\nYours,\nThe Xenogloss Team");
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
					if (!$course->instructors_remove(($student = User::select_by_id($user_id))))
					{
						Session::get()->set_error_assoc("Course-Instructors Removal", Course::unset_error_description());
						return;
					}
					Outbox::send($course, $student->get_email(), "Xenogloss: " . $course->get_course_name(), "Dear " . $student->get_handle() . ",\n\nAn instructor has removed you as a student from " . $course->get_course_name() . ".\n\nYours,\nThe Xenogloss Team");
				}
				
				Session::get()->set_result_assoc($course->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function researchers()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_GET, "course_id", "Course"))
			&& ($course->session_user_can_write() || $course->session_user_is_researcher()))
		{
			self::return_array_as_json($course->get_researchers());
		}
	}
	
	public function researchers_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (self::validate_request($_POST, "user_ids"))
			{
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->researchers_add(($researcher = User::select_by_id($user_id))))
					{
						Session::get()->set_error_assoc("Course-Researchers Addition", Course::unset_error_description());
						return;
					}
					Outbox::send($course, $researcher->get_email(), "Xenogloss: " . $course->get_course_name(), "Dear " . $researcher->get_handle() . ",\n\nThe course owner has designated you as a researcher in " . $course->get_course_name() . ".\n\nYours,\nThe Xenogloss Team");
				}
				
				Session::get()->set_result_assoc($course->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}
	
	public function researchers_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (self::validate_request($_POST, "user_ids"))
			{
				foreach (explode(",", $_POST["user_ids"]) as $user_id)
				{
					if (!$course->researchers_remove(($researcher = User::select_by_id($user_id))))
					{
						Session::get()->set_error_assoc("Course-Researchers Removal", Course::unset_error_description());
						return;
					}
					
					Outbox::send($course, $researcher->get_email(), "Xenogloss: " . $course->get_course_name(), "Dear " . $researcher->get_handle() . ",\n\nThe course owner has undesignated you as a researcher in " . $course->get_course_name() . ".\n\nYours,\nThe Xenogloss Team");
				}
				
				Session::get()->set_result_assoc($course->json_assoc());//, Session::get()->database_result_assoc(array ("didInsert" => true)));
			}
		}
	}

	public function practice_report()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (self::validate_request($_GET, "course_id"))
		{
			$report = Report::get_course_practice_report($_GET["course_id"]);
			if (!!$report)
			{
				$output = json_encode(array ("practiceReport" => $report));
				Session::get()->set_result_assoc($output);
			}
			else
			{
				Session::get()->set_error_assoc("Course-practice-report", Report::unset_error_description());
			}
		}
	}
}

?>
