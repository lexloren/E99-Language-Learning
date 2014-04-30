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

		$this->db->add_users(2);
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
		$entries_count = 50;
		$user_obj = User::select_by_id($this->db->user_ids[1]);
                Session::get()->set_user($user_obj);
                $practice_set = Practice::generate($this->db->practice_list_ids, "unknown", "known", $entries_count);
		$this->assertNotNull($practice_set);
		$this->assertCount(20, $practice_set);
		$this->assertContains($practice_set[0]->get_entry()->get_entry_id(), $this->db->practice_entry_ids);

		$user_obj = User::select_by_id($this->db->user_ids[0]);
                Session::get()->set_user($user_obj);
		$practice_set = Practice::generate($this->db->practice_list_ids, "unknown", "known", $entries_count);
		$this->assertNotNull($practice_set);
                $this->assertCount(20, $practice_set);
		$entry_id = $practice_set[1]->get_entry()->get_entry_id();
		$this->assertContains($entry_id, $this->db->practice_entry_ids);
		$practice_entry_id = $practice_set[1]->get_practice_entry_id();
		$this->assertContains($practice_entry_id, $this->db->practice_ids);
		$user_entry_id = $practice_set[1]->get_user_entry_id();
		$this->assertContains($user_entry_id, $this->db->practice_user_entry_ids);
		$interval = $practice_set[1]->get_interval();
		$this->assertEquals($interval, 0);
		$efactor = $practice_set[1]->get_efactor();
		$this->assertEquals($efactor, 2.50);
		$mode = $practice_set[1]->get_mode();
		$this->assertEquals($interval, 0);
	}

	public function testGenerateEmptyList()
	{
		$this->db->add_list($this->db->user_ids[0], array ());

		$practice_set = Practice::generate($this->db->list_ids, "unknown", "known", 50);
		$this->assertEmpty($practice_set);	
		$this->assertNotNull(Practice::errors_unset());
	}

	public function testGenerateWrongInput()
	{
                $practice_set = Practice::generate($this->db->practice_list_ids, "known", "unknown", -6);
		$this->assertNotNull($practice_set);
		$this->assertCount(20, $practice_set);
		
		$practice = Practice::generate(array (), "known", "unknown", -2);
                $this->assertEmpty($practice);
		$this->assertNotNull(Practice::errors_unset());
	}

	public function testUpdatePracticeResponse()
	{
		$practice = Practice::select_by_id($this->db->practice_ids[0]);
		$old_interval = $practice->get_interval();
		$old_efactor = $practice->get_efactor();
		$practice->update_practice_response($this->db->grade_ids[4]);
		$user_entry_results_count = $practice->get_user_entry_results_count();
		$this->assertNull(Practice::errors_get());
		$this->assertEquals(1, $practice->get_interval());
		$this->assertEquals(1, $user_entry_results_count);

		$practice->update_practice_response($this->db->grade_ids[2]);
		$user_entry_results_count = $practice->get_user_entry_results_count();
		$this->assertNull(Practice::errors_get());
		$this->assertEquals(6, $practice->get_interval());
		$this->assertEquals(2, $user_entry_results_count);

		$practice->update_practice_response($this->db->grade_ids[2]);
		$user_entry_results_count = $practice->get_user_entry_results_count();
		$this->assertNull(Practice::errors_get());
		$this->assertEquals(13.0, $practice->get_interval());
		$this->assertEquals(3, $user_entry_results_count);
	}
	
}
?>
