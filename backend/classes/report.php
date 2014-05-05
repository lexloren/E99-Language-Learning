<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Report extends ErrorReporter
{
	protected static $errors = null;
	public static function get_course_student_practice_report($course_id, $user_id, $mode_id)
	{
		$session_user = Session::get()->get_user();
		if (!$session_user)
			return static::errors_push("Session user has not reauthenticated.");
	
		$course = Course::select_by_id($course_id);
		if (!$course)
			return static::errors_push("Invalid course id.");

		$student_user = User::select_by_id($user_id);
		if (!$student_user)
			return static::errors_push("Invalid student id.");

		$permissions = self::check_permissions($course, $session_user, $student_user);
		
		if (1 != $permissions)
			return static::errors_push("Do not have access to this information.");
		
		$progress_stat = self::generate_class_progress_stat($course_id, $mode_id);
		if (!$progress_stat)
			return null;

		$report = self::create_course_practice_report_for_student($course_id, $user_id, $progress_stat, $mode_id);
		$report["name"] = $student_user->get_name_full();
		
		return $report;
	}

	public static function get_course_practice_report($course_id, $mode_id)
	{
		$session_user = Session::get()->get_user();
		if (!$session_user)
			return static::errors_push("Session user has not reauthenticated.");
	
		$course = Course::select_by_id($course_id);
		if (!$course)
			return static::errors_push("Invalid course id.");

		$permissions = self::check_permissions($course, $session_user, null);
		if (0 == $permissions)
			return static::errors_push("Do not have access to this information.");
		
		$students = $course->students();
		
		$progress_stat = self::generate_class_progress_stat($course_id, $mode_id);
		if (!$progress_stat)
			return null;
		
		$course_report = Array();

		$studentPracticeReports = Array();

		foreach($students as $student)
		{
			$user_id = $student->get_user_id();
			$studentReport = self::create_course_practice_report_for_student($course_id, $user_id, $progress_stat, $mode_id);
			$studentReport["name"] = 2 == $permissions? "Student X" : $student->get_name_full();
			array_push($studentPracticeReports, $studentReport);
		}
		
		$course = Course::select_by_id($course_id);
		
		$course_report["courseName"] = $course->get_name();
		$course_report["studentPracticeReports"] = $studentPracticeReports;
		$course_report["difficultEntries"] = self::create_difficult_entries_report($progress_stat);
		
		return $course_report;
	}
	
	private static function create_course_practice_report_for_student($course_id, $user_id, $progress_stat, $mode_id)
	{
		$sql = sprintf("SELECT unit_id FROM course_units WHERE course_id = %d", $course_id);
		$sql = sprintf("SELECT list_id FROM course_unit_lists WHERE unit_id IN (%s)", $sql);
		$sql = sprintf("SELECT user_entry_id FROM list_entries WHERE list_id IN (%s)", $sql);
		$sql = sprintf("SELECT entry_id FROM user_entries WHERE user_entry_id IN (%s)", $sql);
		$sql = sprintf("SELECT * FROM user_entries WHERE user_id = %d AND entry_id IN (%s)", $user_id, $sql);
		
		$result = Connection::query($sql);
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to create course report for student: $error.");
		}

		$num_user_entries = $result->num_rows;
		
		$entryReports = Array();

		$num_user_entries_practiced = 0.0;
		
		$entry_to_points = $progress_stat["entry_to_points"];
		
		for ($i=0; $i<$num_user_entries; $i++) 
		{
			$result_assoc = $result->fetch_assoc();
			$user_entry_id = $result_assoc['user_entry_id'];
			$entry_id = $result_assoc['entry_id'];
			

			$sql = sprintf("SELECT * FROM user_entry_results WHERE user_entry_id = %d AND mode = %d", $user_entry_id, $mode_id);
			$result_practice = Connection::query($sql);
			
			if (!!($error = Connection::query_error_clear()))
			{
				return static::errors_push("Failed to create course report for student: $error.");
			}
			
			$num_practiced = $result_practice->num_rows;
			if (0 == $num_practiced)
				continue;

			$entryReport = Array();

			$entryReport["entryId"] = $entry_id;
			$entry = Entry::select_by_id($entry_id);
			$entryReport["words"] =  $entry->words();
			$entryReport["practiceCount"] = $num_practiced;
			$entryReport["gradePointAverage"] = self::get_student_average_point_for_entry($entry_id, $user_id, $mode_id);
			$entryReport["classGradePointAverage"] = $entry_to_points[$entry_id];
			
			array_push($entryReports, $entryReport);
			if ($num_practiced > 0)
				$num_user_entries_practiced = $num_user_entries_practiced + 1.0;
		}
		
		$report = Array();
		$report["progressPercent"] = $progress_stat["num_entries"] > 0 ? $num_user_entries_practiced / $progress_stat["num_entries"] : 0;
		$report["unitReports"] = self::create_units_report($course_id);
		$report["entryReports"] = $entryReports;
		return $report;
	}
	
	private static function create_units_report($course_id)
	{
		$sql = "SELECT * FROM course_units WHERE course_id = ".$course_id;
		$result = Connection::query($sql);
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to create units report: $error.");
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
		
		return $unitsReport;
	}
	
		
	private static function get_class_average_point_for_entry($entry_id, $course_id, $mode_id)
	{
		$sql = sprintf("SELECT AVG(grades.point) FROM grades, user_entry_results, user_entries ".
				"WHERE user_entry_results.mode = %d ".
				"AND grades.grade_id = user_entry_results.grade_id ".
				"AND user_entry_results.user_entry_id = user_entries.user_entry_id ".
				"AND user_entries.entry_id = %d ".
				"AND user_entries.user_id ".
				"IN (SELECT user_id FROM course_students WHERE course_id = %d)",
				$mode_id, $entry_id, $course_id
		);
				
		//print($sql);
		
		$result = Connection::query($sql);
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to get class average point for entry: $error.");
		}

		$result_assoc = $result->fetch_assoc();
		
		if (!!$result_assoc && !!$result_assoc['AVG(grades.point)'])
			return (float)$result_assoc['AVG(grades.point)'];
		else
			return -1;
	}
	
	private static function get_student_average_point_for_entry($entry_id, $user_id, $mode_id)
	{
		$sql = sprintf("SELECT AVG(grades.point) FROM grades, user_entry_results, user_entries
				WHERE user_entry_results.mode = %d
				AND grades.grade_id = user_entry_results.grade_id 
				AND user_entry_results.user_entry_id = user_entries.user_entry_id
				AND user_entries.entry_id = %d
				AND user_entries.user_id = %d",
				$mode_id, $entry_id, $user_id);
				
		//print($sql);
		
		$result = Connection::query($sql);
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to get student average point for entry: $error.");
		}

		$result_assoc = $result->fetch_assoc();
		
		if (!!$result_assoc)
			return (float)$result_assoc['AVG(grades.point)'];
		else
			return 0;
	}
	
	private static function generate_class_progress_stat($course_id, $mode_id)
	{
		$sql = sprintf("SELECT unit_id FROM course_units WHERE course_id = %d", $course_id);
		$sql = sprintf("SELECT list_id FROM course_unit_lists WHERE unit_id IN (%s)", $sql);
		$sql = sprintf("SELECT user_entry_id FROM list_entries WHERE list_id IN (%s)", $sql);
		$sql = sprintf("SELECT entry_id FROM user_entries WHERE user_entry_id IN (%s)", $sql);
		
		$result = Connection::query($sql);
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to generate class progress stat: $error.");
		}
		
		$progress_stat = array();
		$progress_stat["num_entries"] = $result->num_rows;

		$entry_to_points = array();
		
		while( !!($result_assoc = $result->fetch_assoc()) )
		{
			$entry_id = $result_assoc["entry_id"];
			$point = self::get_class_average_point_for_entry($entry_id, $course_id, $mode_id);
			if ($point >= 0)
				$entry_to_points[$entry_id] = $point;
		}
		
		$progress_stat["entry_to_points"] = $entry_to_points;

		return $progress_stat;
	}
	
	//returns 0 if no permission, 1 if all , 2 if anonymous
	private static function check_permissions($course, $session_user, $student_user)
	{
		if (!$session_user || !$course)
			return 0;
		
		$instructors = $course->instructors();

		if (in_array($session_user, $instructors))
			return 1;
			
		$researchers = $course->researchers();
		if (in_array($session_user, $researchers))
			return 2;
			
		if ($student_user == $session_user)
		{
			$students = $course->students();
			if (in_array($student_user, $students))
				return 1;
		}
		
		return 0;
	}
	
	private static function create_difficult_entries_report($progress_stat)
	{
		$entry_to_points = $progress_stat["entry_to_points"];
		
		$difficult_entries = array ();
		if( asort ($entry_to_points) )
		{
			foreach($entry_to_points as $k => $a)
			{
				$entry = array(
					"entry_id" => $k,
					"classGradePointAverage" => $a
				);
				array_push($difficult_entries, $entry);
			}
		}
		
		return $difficult_entries;
	}
	
	public static function get_course_student_test_report($course_id, $user_id)
	{
		$session_user = Session::get()->get_user();
		if (!$session_user)
			return static::errors_push("Session user has not reauthenticated.");
	
		$course = Course::select_by_id($course_id);
		if (!$course)
			return static::errors_push("Invalid course id.");

		$student_user = User::select_by_id($user_id);
		if (!$student_user)
			return static::errors_push("Invalid student id.");

		$permissions = self::check_permissions($course, $session_user, $student_user);
		
		if (1 != $permissions)
			return static::errors_push("Do not have access to this information.");
	}
	
	public static function get_course_test_report($course_id)
	{
		$session_user = Session::get()->get_user();
		if (!$session_user)
			return static::errors_push("Session user has not reauthenticated.");

		$course = Course::select_by_id($course_id);
		if (!$course)
			return static::errors_push("Invalid course id.");
		
		$permissions = self::check_permissions($course, $session_user, null);
		if (0 == $permissions)
			return static::errors_push("Do not have access to this information.");


		$tests = $course->tests(false);
		$students = $course->students();
		
		$report = array();
		
		$report["courseId"] = $course_id;
		$studentTestReports = array();

		
		self::create_course_test_stat_for_class($course_id);
		
		$report["studentTestReports"] = $studentTestReports;

		return $report;
	}
	
	private static function create_course_test_stat_for_class($course_id)
	{
		$time_now = time();
		$sql =	"SELECT * FROM course_unit_test_entries ".
				"WHERE test_id IN (".
					"SELECT test_id FROM course_unit_tests WHERE unit_id IN (".
						"SELECT unit_id FROM course_units WHERE course_id = $course_id".
					")".
					" AND course_unit_tests.close < $time_now".
				")";
		
		$result = Connection::query($sql);
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to create course report for student: $error.");
		}
		
		while( !!($result_assoc = $result->fetch_assoc()) )
		{
			//print_r($result_assoc);			
			$test_entry_id = $result_assoc["test_entry_id"];
			self::get_class_average_score($test_entry_id);
		}
	}
	
	private static function get_class_average_score($test_entry_id)
	{
		$sql = "SELECT *, AVG(course_unit_test_entry_patterns.score) ".
				"FROM course_unit_test_sitting_responses ".
				"INNER JOIN course_unit_test_entry_patterns ON course_unit_test_sitting_responses.pattern_id = course_unit_test_entry_patterns.pattern_id ".
				"WHERE course_unit_test_sitting_responses.pattern_id IN ".
				"(SELECT pattern_id FROM course_unit_test_entry_patterns WHERE test_entry_id = $test_entry_id) ";
				
		$result = Connection::query($sql);
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to create course report for student: $error.");
		}
		
		while( !!($result_assoc = $result->fetch_assoc()) )
		{
			//print_r($result_assoc);
			//$test_entry_id = $result_assoc["test_entry_id"];
		}
	}
}

?>
