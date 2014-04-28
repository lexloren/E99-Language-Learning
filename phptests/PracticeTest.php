<?php

//Tests class User
require_once './backend/classes/session.php';
require_once './backend/classes/practice.php';
require_once './phptests/TestDB.php';

class PracticeTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
			Session::set(null);
			$this->db = TestDB::create();
			$this->assertNotNull($this->db, "failed to create test database");

	$this->db->add_users(1);
	$this->db->add_practice_data($this->db->user_ids[0], 2, 10);
	$user_obj = User::select_by_id($this->db->user_ids[0]);
			Session::get()->set_user($user_obj);
	}
	
	public function tearDown()
	{
			//if (isset($this->db))
			//      $this->db->close();
	}

	public function testGenerate()
	{
                $practice = Practice::generate($this->db->practice_list_ids, 50);

		$this->assertNotNull($practice);
		$this->assertCount(count($this->db->practice_entry_ids), $practice->entries());
		$this->assertEquals($this->db->practice_entry_ids, $practice->get_entry_ids());
	}

	public function testGenerateEmptyList()
	{
		$this->db->add_list($this->db->user_ids[0], array ());

		$practice = Practice::generate($this->db->list_ids, 50);
		$this->assertNotNull($practice);
		$this->assertEmpty($practice->entries());	
		$this->assertEmpty($practice->get_entry_ids());	
	}

	public function testGenerateWrongInput()
	{
                $practice = Practice::generate($this->db->practice_list_ids, -6);
		$this->assertNotNull($practice);
		$this->assertCount(count($this->db->practice_entry_ids), $practice->entries());
		
		$practice = Practice::generate(array (), -2);
		$this->assertNotNull($practice);
                $this->assertEmpty($practice->entries());
                $this->assertEmpty($practice->get_entry_ids());
	}

	public function testUpdatePracticeResponse()
	{
		$entry = Practice::update_practice_response($this->db->practice_entry_ids[0], 100000);
		$this->assertTrue(Session::get()->has_error());

		Session::get()->set_result_assoc(null);
		$grade = Grade::select_by_point(3);
		$entry = Practice::update_practice_response($this->db->practice_entry_ids[0], $grade->get_grade_id());
		$this->assertFalse(Session::get()->has_error());
		$this->assertNotNull($entry);
		$this->assertEquals($entry->get_entry_id(), $this->db->practice_entry_ids[0]);
	}
	
}
?>
