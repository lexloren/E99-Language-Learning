<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Report extends ErrorReporter
{
	protected static $errors = null;
	public static function get_course_practice_report($course_id)
	{
		$session_user = Session::get()->get_user();
		if (!$session_user)
			return static::errors_push("Session user has not reauthenticated.");
	
		$course = Course::select_by_id($course_id);
		if (!$course)
			return static::errors_push("Invalid course id.");

		$instructors = $course->instructors();
		if (!in_array($session_user, $instructors))
			return static::errors_push("Do not have access to this information.");
		
		$students = $course->students();
		
		$progress_stat = self::generate_class_progress_stat($course_id);
		if (!$progress_stat)
			return null;
		
		$course_report = Array();

		$studentPracticeReports = Array();

		foreach($students as $student)
		{
			$user_id = $student->get_user_id();
			$studentReport = self::create_course_practice_report_for_student($course_id, $user_id, $progress_stat);
			$studentReport["student"] = $student->json_assoc(false);
			array_push($studentPracticeReports, $studentReport);
		}
		
		$course = Course::select_by_id($course_id);
		
		$course_report["course"] = $course->json_assoc();
		$course_report["studentPracticeReports"] = $studentPracticeReports;
		$course_report["difficultEntries"] = self::create_difficult_entries_report($progress_stat);
		
		return $course_report;
	}
	
	private static function create_course_practice_report_for_student($course_id, $user_id, $progress_stat)
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
			

			$sql = sprintf("SELECT * FROM user_entry_results WHERE user_entry_id = %d", $user_entry_id);
			$result_practice = Connection::query($sql);
			
			if (!!($error = Connection::query_error_clear()))
			{
				return static::errors_push("Failed to create course report for student: $error.");
			}
			
			$num_practiced = $result_practice->num_rows;
			if (0 == $num_practiced)
				continue;

			$entryReport = Array();

			$entry = Entry::select_by_id($entry_id);
			$entryReport["entry"] = $entry->json_assoc();
			$entryReport["practiceCount"] = $num_practiced;
			$entryReport["averageGradePoint"] = self::get_student_average_point_for_entry($entry_id, $user_id);
			
			array_push($entryReports, $entryReport);
			if ($num_practiced > 0)
				$num_user_entries_practiced = $num_user_entries_practiced + 1.0;
		}
		
		$report = Array();
		$report["progressPercent"] = intval(0.5 + 100 * ($progress_stat["num_entries"] > 0 ? $num_user_entries_practiced / $progress_stat["num_entries"] : 0));
		$report["unitReports"] = self::create_units_report($course_id, $user_id);
		$report["entryReports"] = $entryReports;
		return $report;
	}
	
	private static function create_units_report($course_id, $user_id)
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
			$unit = Unit::select_by_id($result_assoc["unit_id"]);
			$unitReport["unit"] = $unit->json_assoc();
			$unitReport["progressPercent"] = self::get_student_practice_percent($result_assoc["unit_id"], $user_id);
			array_push($unitsReport, $unitReport);
		}
		
		return $unitsReport;
	}
	
	private static function get_student_practice_percent($unit_id, $user_id)
	{
		$sql_unit_list_entries_count = "SELECT COUNT(*) FROM list_entries WHERE list_id IN
			(SELECT list_id FROM course_unit_lists WHERE unit_id = $unit_id)";

		$result = Connection::query($sql_unit_list_entries_count);
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to create units report: $error.");
		}
		
		$result_assoc = $result->fetch_assoc();
		$unit_list_entries_count = $result_assoc["COUNT(*)"];
		
		if (0 == $unit_list_entries_count)
			return 0;
		
		$sql_practiced_count = "SELECT COUNT(DISTINCT user_entry_id) FROM user_entry_results WHERE user_entry_id IN
			(SELECT user_entry_id FROM user_entries WHERE user_id = $user_id and entry_id IN
			(SELECT entry_id FROM user_entries WHERE user_entry_id IN
			(SELECT user_entry_id FROM list_entries WHERE list_id IN
			(SELECT list_id FROM course_unit_lists WHERE unit_id = $unit_id))))";
		$result = Connection::query($sql_practiced_count);

		if (!!($error = Connection::query_error_clear()))
		{
			echo $error;
			exit;
			return static::errors_push("Failed to create units report: $error.");
		}
		
		$result_assoc = $result->fetch_assoc();
		$practiced_entries_count = $result_assoc["COUNT(DISTINCT user_entry_id)"];
		
		$percent = intval(0.5 + 100.0 * $practiced_entries_count / $unit_list_entries_count);
		
		return $percent;
	}
		
	private static function get_class_average_point_for_entry($entry_id, $course_id)
	{
		$sql = sprintf("SELECT AVG(grades.point) FROM grades, user_entry_results, user_entries ".
				"AND grades.grade_id = user_entry_results.grade_id ".
				"AND user_entry_results.user_entry_id = user_entries.user_entry_id ".
				"AND user_entries.entry_id = %d ".
				"AND user_entries.user_id ".
				"IN (SELECT user_id FROM course_students WHERE course_id = %d)",
				$entry_id, $course_id
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
	
	private static function get_student_average_point_for_entry($entry_id, $user_id)
	{
		$sql = sprintf("SELECT AVG(grades.point) FROM grades, user_entry_results, user_entries
				AND grades.grade_id = user_entry_results.grade_id 
				AND user_entry_results.user_entry_id = user_entries.user_entry_id
				AND user_entries.entry_id = %d
				AND user_entries.user_id = %d",
				$entry_id, $user_id);
				
		//print($sql);
		
		$result = Connection::query($sql);
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to get student average point for entry: $error.");
		}

		$result_assoc = $result->fetch_assoc();
		
		if (!!$result_assoc)
			return intval($result_assoc['AVG(grades.point)']);
		else
			return 0;
	}
	
	private static function generate_class_progress_stat($course_id)
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
			$point = self::get_class_average_point_for_entry($entry_id, $course_id);
			if ($point >= 0)
				$entry_to_points[$entry_id] = $point;
		}
		
		$progress_stat["entry_to_points"] = $entry_to_points;

		return $progress_stat;
	}
	
	private static function create_difficult_entries_report($progress_stat)
	{
		$entry_to_points = $progress_stat["entry_to_points"];
		
		$difficult_entries = array ();
		if( asort ($entry_to_points) )
		{
			foreach($entry_to_points as $k => $a)
			{
				$entry = Entry::select_by_id($k);
				$difficult_entry = array();
				$difficult_entry["entry"] = $entry->json_assoc();
				$difficult_entry["averageGradePoint"] = intval($a);
				array_push($difficult_entries, $difficult_entry);
			}
		}
		
		return $difficult_entries;
	}
	
	public static function get_course_test_report($course_id)
	{
		$session_user = Session::get()->get_user();
		if (!$session_user)
			return static::errors_push("Session user has not reauthenticated.");

		$course = Course::select_by_id($course_id);
		if (!$course)
			return static::errors_push("Invalid course id.");
		
		$instructors = $course->instructors();
		if (!in_array($session_user, $instructors))
			return static::errors_push("Do not have access to this information.");

		$students = $course->students();
		$tests = $course->tests(false);
		$testReports = array();

		foreach($tests as $test)
		{
			$time_frame = $test->get_timeframe();
			if (null != $time_frame && !$time_frame->is_closed())
				continue;

			$studentTestReports = array();
			$student_no = 0;
			foreach($students as $student)
			{
				$student_no++;
				$studentTestReport = self::creatre_student_test_report($student, $test);
				array_push($studentTestReports, $studentTestReport);
			}
			
			$testReport = array();
			$testReport["test"] = $test->json_assoc(false);
			$testReport["studentTestReports"] = $studentTestReports;
			
			array_push($testReports, $testReport);
		}
		
		$report = array();
		$report["course"] = $course->json_assoc();
		$report["testReports"] = $testReports;
		return $report;
	}

	private static function creatre_student_test_report($student, $test)
	{
		$sitting = Sitting::select_by_test_id_user_id($test->get_test_id(), $student->get_user_id());
		
		$entryReports = array();

		$entries = $test->entries();
		foreach($entries as $entry)
		{
			$entryReport = array();
			$entryReport["entry"] = $entry->json_assoc(false);
			$entryReport["score"] = !!$sitting ? $sitting->get_response_for_entry($entry) : 0;
			array_push($entryReports, $entryReport);
		}
		
		$studentTestReport = array();
		$studentTestReport["student"] = $student->json_assoc(false);
		$studentTestReport["entryReports"] = $entryReports;
		return $studentTestReport;
	}

	private static function get_student_data()
	{
		$sql = "SELECT user_id, IF(u.status_id IS NULL, '', (SELECT s.desc FROM user_statuses s WHERE s.status_id = u.status_id)) AS status " +
		       "users u";
	}

	public static function get_data_dump()
	{
		return array(
			array("name 1", "age 1", "city 1"),
			array("name 2", "age 2", "city 2"),
			array("name 3", "age 3", "city 3"));
	}
}	



?>
