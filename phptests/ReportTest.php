<?php

require_once "./backend/classes.php";
require_once './phptests/TestDB.php';

class ReportTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		Session::set(null);
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
		
		$this->db->add_grades();
		$this->db->add_users(5);
		$this->db->add_dictionary_entries(10);
		
		$course_id = $this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_instructor($this->db->course_ids[0], $this->db->user_ids[0]);
		$course_unit_id = $this->db->add_course_unit($this->db->course_ids[0]);
		$list_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$this->db->add_unit_list($course_unit_id, $list_id);

		
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[1]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[1], 3);
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[2]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[2], 2);

		//list not practiced
		$new_entries = $this->db->add_dictionary_entries(10);
		$list_id = $this->db->add_list($this->db->user_ids[0], $new_entries);
		$this->db->add_unit_list($course_unit_id, $list_id);

		$this->db->add_course_researcher($this->db->course_ids[0], $this->db->user_ids[3]);
	}
	
	public function test_get_course_student_practice_report()
	{
		//set instructor as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$this->verify_course_student_practice_report(1);
		$this->verify_course_student_practice_report(2);
		
		//set researcher as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[3]));
		$report = Report::get_course_student_practice_report($this->db->course_ids[0], $this->db->user_ids[1]);
		$this->assertNull($report);

		//set student as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[1]));
		$this->verify_course_student_practice_report(1);
		$report = Report::get_course_student_practice_report($this->db->course_ids[0], $this->db->user_ids[2]);
		$this->assertNull($report);
	}

	public function test_get_course_practice_report()
	{
		//set instructor as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$report = Report::get_course_practice_report($this->db->course_ids[0]);		
		$this->verify_course_practice_report($report);

		//set researcher as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[3]));
		$report = Report::get_course_practice_report($this->db->course_ids[0]);		
		$this->verify_course_practice_report($report);

		//set student as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[1]));
		$report = Report::get_course_practice_report($this->db->course_ids[0]);		
		$this->assertNull($report);
		$this->assertTrue(Session::get()->has_error());

		//set outsider as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[4]));
		$report = Report::get_course_practice_report($this->db->course_ids[0]);		
		$this->assertNull($report);
		$this->assertTrue(Session::get()->has_error());
	}

	public function verify_course_practice_report($report)
	{
		//print json_encode($report);
		
		$this->assertNotNull($report);
		$this->assertNotNull($report["studentPracticeReports"]);
		$this->assertCount(2, $report["studentPracticeReports"]);
		
		$studentPracticeReport = $report["studentPracticeReports"];

		for($i=0; $i<2; $i++)
		{
			$studentReport = $studentPracticeReport[$i];
			$this->assertNotNull($studentReport["name"]);
			$this->assertEquals($studentReport["progressPercent"], 0.5);
		}
		
		$this->assertNotNull($report["difficultEntries"]);
		//print_r ($report["difficultEntries"]);
		$this->assertCount(10, $report["difficultEntries"]);
		
		$difficult_entries = $report["difficultEntries"];
		$prev_entry = $difficult_entries[0];
		for($i=1; $i<10; $i++)
		{
			$entry = $difficult_entries[$i];
			$this->assertTrue($prev_entry["classGradePointAverage"] <= $report["difficultEntries"]);
			$prev_entry = $entry;
		}

		$this->assertEquals($report["courseName"], $this->db->course_names[0]);
	}
	
	public function verify_course_student_practice_report($index)
	{
		$report = Report::get_course_student_practice_report($this->db->course_ids[0], $this->db->user_ids[$index]);

		$this->assertNotNull($report, "failed for student ".$index);
		$this->assertEquals($report["name"], $this->db->names_given[$index].' '.$this->db->names_family[$index]);
		$this->assertNotNull($report["progressPercent"]);
		$this->assertNotNull($report["unitReports"]);
		$this->assertNotNull($report["entryReports"]);

		$this->assertCount(10, $report["entryReports"]);
		
		for($i=0; $i<10; $i++)
		{
			$entry_report = $report["entryReports"][$i];
			$this->assertNotNull($entry_report);
			if ($index == 1)
				$this->assertEquals(3, $entry_report["practiceCount"]);
			else
				$this->assertEquals(2, $entry_report["practiceCount"]);
		}
		
		//if ($s == 1) print json_encode($report);
		//print_r($report);
	}
}
?>

















