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
		
		self::create_course_unit_report($report, $course, $user);
		return $report;
	}
	
	public static function create_course_unit_report(&$report, $course, $user)
	{
		$sql = sprintf("SELECT unit_id FROM course_units WHERE course_id = %d", $course->get_course_id());
		$sql = sprintf("SELECT list_id FROM course_unit_lists WHERE unit_id IN (%s)", $sql);
		$sql = sprintf("SELECT user_entry_id FROM list_entries WHERE list_id IN (%s)", $sql);
		$sql = sprintf("SELECT entry_id FROM user_entries WHERE user_entry_id IN (%s)", $sql);
		
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query($sql);
		
		if ($mysqli->error)
		{
			return Session::get()->set_error_assoc("Report Error", "Database query failed.");
		}
		
		$num_unit_entries = $result->num_rows;


		$sql = sprintf("SELECT * FROM user_entries WHERE user_id = %d AND entry_id IN (%s)", $user->get_user_id(), $sql);
		
	
		$result = $mysqli->query($sql);
		
		if ($mysqli->error)
		{
			print $sql;
			print $mysqli->error;
			return Session::get()->set_error_assoc("Report Error", "Database query failed.");
		}

		$num_user_entries = $result->num_rows;
		
		$entryReports = Array();

		for ($i=0; $i<$num_user_entries; $i++) 
		{
			$result_assoc = $result->fetch_assoc();
			$user_entry_id = $result_assoc['user_entry_id'];
			$entry_id = $result_assoc['entry_id'];
			
			$entry = Entry::select_by_id($entry_id);

			$sql = sprintf("SELECT * FROM user_entry_results WHERE user_entry_id = %d", $user_entry_id);
			$result_practice = $mysqli->query($sql);
			
			$num_practiced = $result_practice->num_rows;

			$entryReport = Array();

			$entryReport["entryId"] = $entry_id;
			$entryReport["words"] =  $entry->get_words();
			$entryReport["practiceCount"] = $num_practiced;
			$entryReport["gradePointAverage"] = 0;
			
			while( !!($result_assoc = $result_practice->fetch_assoc() ))
			{
				$grade_id = $result_assoc["grade_id"];
				$grade = Grade::select_by_id($grade_id);
				$entryReport["gradePointAverage"] = $entryReport["gradePointAverage"] + $grade->get_point();
			}
			if ($num_practiced > 1)
				$entryReport["gradePointAverage"] = $entryReport["gradePointAverage"] / $num_practiced;
			
			array_push($entryReports, $entryReport);
		}
		
		$report["progressPercent"] = 0;
		$report["unitReports"] = Array();
		$report["entryReports"] = $entryReports;
	}
}

?>
