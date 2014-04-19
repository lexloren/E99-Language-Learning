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
		$this->db->add_users(3);
		$this->db->add_dictionary_entries(10);
		
		$this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_instructor($this->db->course_ids[0], $this->db->user_ids[0]);
		//$this->db->add_course_unit($this->db->course_ids[0]);
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[1]);
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[2]);
		
	}
	
	public function test_get_user_practice_report()
	{
		$user = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user);
		$report = Report::get_user_practice_report($this->db->course_ids[0], $this->db->user_ids[1]);

		$this->assertNotNull($report);
		$this->assertEquals($report["identifier"], $this->db->names_given[1].' '.$this->db->names_family[1]);
		$this->assertNotNull($report["progressPercent"]);
		$this->assertNotNull($report["unitReports"]);
		$this->assertNotNull($report["entryReports"]);

		//print_r($report);
	}
}
?>

















