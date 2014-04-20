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
		$report["name"] = $is_anon ? "Student X" : $user->get_name_full();
		
		self::create_course_report_for_student($report, $course, $user);
		return $report;
	}
	
	public static function create_course_report_for_student(&$report, $course, $user)
	{
		$mysqli = Connection::get_shared_instance();

		$sql = sprintf("SELECT unit_id FROM course_units WHERE course_id = %d", $course->get_course_id());
		$sql = sprintf("SELECT list_id FROM course_unit_lists WHERE unit_id IN (%s)", $sql);
		$sql = sprintf("SELECT user_entry_id FROM list_entries WHERE list_id IN (%s)", $sql);
		$sql = sprintf("SELECT entry_id FROM user_entries WHERE user_entry_id IN (%s)", $sql);
		$sql = sprintf("SELECT * FROM user_entries WHERE user_id = %d AND entry_id IN (%s)", $user->get_user_id(), $sql);
		
		$result = $mysqli->query($sql);
		
		if ($mysqli->error)
		{
			return Session::get()->set_error_assoc("Report Error", "Database query failed.");
		}

		$num_user_entries = $result->num_rows;
		
		$entryReports = Array();

		for ($i=0; $i<$num_user_entries; $i++) 
		{
			$result_assoc = $result->fetch_assoc();
			$user_entry_id = $result_assoc['user_entry_id'];
			$entry_id = $result_assoc['entry_id'];
			
			$entryReport = Array();

			$sql = sprintf("SELECT * FROM user_entry_results WHERE user_entry_id = %d", $user_entry_id);
			$result_practice = $mysqli->query($sql);
			$num_practiced = $result_practice->num_rows;

			$entryReport["entryId"] = $entry_id;
			$entry = Entry::select_by_id($entry_id);
			$entryReport["words"] =  $entry->get_words();
			$entryReport["practiceCount"] = $num_practiced;
			$entryReport["gradePointAverage"] = self::get_student_avarage_point_for_entry($entry_id, $user->get_user_id());
			$entryReport["classGradePointAverage"] = self::get_class_avarage_point_for_entry($entry_id, $course->get_course_id());
			
			array_push($entryReports, $entryReport);
		}
		
		$report["progressPercent"] = 0;
		self::create_units_report($report, $course->get_course_id(), $user->get_user_id());
		$report["entryReports"] = $entryReports;
	}
	
	private static function create_units_report(&$report, $course_id, $user_id)
	{
		$mysqli = Connection::get_shared_instance();
		
		$sql = "SELECT * FROM course_units WHERE course_id = ".$course_id;
		$result = $mysqli->query($sql);
		
		if ($mysqli->error)
		{
			return Session::get()->set_error_assoc("Report Error", "Database query failed.");
		}
		
		$num_units = $result->num_rows;
		$unitsReport = Array();

		for ($i=0; $i<$num_units; $i++) 
		{
			$result_assoc = $result->fetch_assoc();
			$unitReport = Array();
			$unitReport["unitName"] = $result_assoc["name"];
			$unitReport["progressPercent"] = 0.0;
			$unitReport["classProgressPercentAverage"] = 0.0;
			array_push($unitsReport, $unitReport);
		}
		
		$report["unitReports"] = $unitsReport;
	}
	
		
	private static function get_class_avarage_point_for_entry($entry_id, $course_id)
	{
		$mysqli = Connection::get_shared_instance();
		$sql = sprintf("SELECT AVG(grades.point) FROM grades, user_entry_results, user_entries
				WHERE grades.grade_id = user_entry_results.grade_id 
				AND user_entry_results.user_entry_id = user_entries.user_entry_id
				AND user_entries.entry_id = %d
				AND user_entries.user_id 
				IN(	SELECT user_id FROM course_students WHERE course_id = %d)",
				$entry_id, $course_id);
				
		//print($sql);
		
		$result = $mysqli->query($sql);

		$result_assoc = $result->fetch_assoc();
		
		if (!!$result_assoc)
			return (float)$result_assoc['AVG(grades.point)'];
		else
			return 0;
	}
	
	private static function get_student_avarage_point_for_entry($entry_id, $user_id)
	{
		$mysqli = Connection::get_shared_instance();
		$sql = sprintf("SELECT AVG(grades.point) FROM grades, user_entry_results, user_entries
				WHERE grades.grade_id = user_entry_results.grade_id 
				AND user_entry_results.user_entry_id = user_entries.user_entry_id
				AND user_entries.entry_id = %d
				AND user_entries.user_id = %d",
				$entry_id, $user_id);
				
		//print($sql);
		
		$result = $mysqli->query($sql);

		$result_assoc = $result->fetch_assoc();
		
		if (!!$result_assoc)
			return (float)$result_assoc['AVG(grades.point)'];
		else
			return 0;
	}
	
	public static function get_course_practice_report($course_id)
	{
		$mysqli = Connection::get_shared_instance();
		
		$sql = "SELECT * FROM course_students WHERE course_id = ".$course_id;
		$result = $mysqli->query($sql);
		
		if ($mysqli->error)
		{
			return Session::get()->set_error_assoc("Report Error", "Database query failed.");
		}
		
		$studentPracticeReports = Array();

		while( !!($result_assoc = $result->fetch_assoc()) )
		{
			$user_id = $result_assoc["user_id"];
			$studentReport = self::get_user_practice_report($course_id, $user_id);
			array_push($studentPracticeReports, $studentReport);
		}
		
		$course = Course::select_by_id($course_id);
		
		$report = Array();
		$report["courseName"] = $course->get_course_name();
		$report["studentPracticeReports"] = $studentPracticeReports;
		$report["difficultEntries"] = Array();
		
		return $report;
	}
}

?>
