<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Report
{
	protected static $error_description = null;
	public static function get_user_practice_report($course_id, $user_id)
	{
		$session_user = Session::get()->get_user();
		if (!$session_user)
			return Session::get()->set_error_assoc("Report Error", "Session user has not reauthenticated.");
		
		$student_user = User::select_by_id($user_id);
		if (!$student_user)
			return Session::get()->set_error_assoc("Report Error", "Invalid student id.");

		if (!$session_user)
			return Session::get()->set_error_assoc("Report Error", "Session user has not reauthenticated.");

		$course = Course::select_by_id($course_id);
		if (!$course)
			return Session::get()->set_error_assoc("Report Error", "Invalid course id.");
			
		$instructors = $course->get_instructors();

		if (!in_array($session_user, $instructors))
			return Session::get()->set_error_assoc("Report Error", "Do not have access to this information.");
			
		//$researchers = $course->get_researchers();

		$students = $course->get_students();
		if (!in_array($student_user, $students))
			return Session::get()->set_error_assoc("Report Error", "User is not a student.");
		
		return self::create_practice_report($course, $student_user, false);
	}

	public static function create_practice_report($course, $user, $is_anon)
	{
		$report = Array();
		$report["identifier"] = $is_anon ? "Student X" : $user->get_name_full();
		$report["progressPercent"] = 0;
		$report["unitReports"] = Array();
		$report["entryReports"] = Array();
		return $report;
	}
}

?>
