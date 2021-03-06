<?php

require_once "./backend/classes.php";
require_once './phptests/TestDB.php';

class ReportTest extends PHPUnit_Framework_TestCase
{
	private $db;

	private $student1;
	public function setup()
	{
		Session::set(null);
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
		
		$this->db->add_users(5);
		$this->db->add_dictionary_entries(10);
		
		$course_id = $this->db->add_course($this->db->user_ids[0]);
		$course_unit_id = $this->db->add_course_unit($this->db->course_ids[0]);
		$list_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$this->db->add_unit_list($course_unit_id, $list_id);

		
		$this->student1 = $this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[1]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[1], 3, $this->db->mode_ids[1]);
		$this->student2 = $this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[2]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[2], 2, $this->db->mode_ids[1]);

		//list not practiced
		$new_entries = $this->db->add_dictionary_entries(10);
		$list_id = $this->db->add_list($this->db->user_ids[0], $new_entries);
		$this->db->add_unit_list($course_unit_id, $list_id);

		$this->db->add_course_researcher($this->db->course_ids[0], $this->db->user_ids[3]);
	}
	
	public function test_get_course_practice_report()
	{
		//set instructor as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$this->verify_course_practice_report();

		//set researcher as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[3]));
		$report = Report::get_course_practice_report($this->db->course_ids[0]);		
		$this->assertNull($report);
		$this->assertTrue(Session::get()->has_error());

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

	private function verify_course_practice_report()
	{
		//use a different mode
		$report = Report::get_course_practice_report($this->db->course_ids[0]);		
		$this->assertNotNull($report);
		$this->assertNotNull($report["studentPracticeReports"]);
		$this->assertCount(2, $report["studentPracticeReports"]);

		//use correct mode
		$report = Report::get_course_practice_report($this->db->course_ids[0]);		
		//print json_encode($report);
		
		$this->assertNotNull($report);
		$this->assertNotNull($report["studentPracticeReports"]);
		$this->assertCount(2, $report["studentPracticeReports"]);
		
		$studentPracticeReport = $report["studentPracticeReports"];

		for($i=0; $i<2; $i++)
		{
			$studentReport = $studentPracticeReport[$i];
			$this->assertNotNull($studentReport["student"]);
			$this->assertEquals($studentReport["progressPercent"], 50);
		}
		
		$this->assertNotNull($report["difficultEntries"]);
		//print_r ($report["difficultEntries"]);
		$this->assertCount(20, $report["difficultEntries"]);
		
		$difficult_entries = $report["difficultEntries"];
		$prev_entry = $difficult_entries[0];
		for($i=1; $i<10; $i++)
		{
			$entry = $difficult_entries[$i];
			$this->assertTrue($prev_entry["averageGradePoint"] <= $entry["averageGradePoint"]);
			$prev_entry = $entry;
		}

		$course = $report["course"];
		$this->assertEquals($course["name"], $this->db->course_names[0]);
		
		//print_r($report);
		//print_r(json_encode($report));
	}
	
	public function test_get_course_test_report()
	{
		//set instructor as session user
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		
		$week = (7 * 24 * 60 * 60);
		$time_now = time();
		$open = $time_now - $week  * 2;
		$close = $time_now - $week;

		
		$test_id = $this->db->add_unit_test($this->db->course_unit_ids[0], $open, $close);
		$test_entry_ids = $this->db->add_unit_test_entries($test_id, $this->db->user_ids[0], 10);
		$sitting_id = $this->db->add_unit_test_sittings($test_id, $this->student1);
		$sitting_id2 = $this->db->add_unit_test_sittings($test_id, $this->student2);
		
		$scores = array();
		
		for($i=0; $i<count($test_entry_ids); $i++)
		{
			$test_entry_id = $test_entry_ids[$i];
			$scores[$test_entry_id] = ($i % 2 ) * 100;
		}
		
		$this->db->add_unit_test_sitting_responses($sitting_id, $scores, "content X");

		for($i=0; $i<count($test_entry_ids); $i++)
		{
			$test_entry_id = $test_entry_ids[$i];
			$scores[$test_entry_id] = ($i % 3 ) * 100;
		}

		$this->db->add_unit_test_sitting_responses($sitting_id2, $scores, "content Y");
		
		$report = Report::get_course_test_report($this->db->course_ids[0]);	
		$this->assertNotNull($report);
		//print_r(json_encode($report));
		//exit;
	}
}
?>

















