<?php

require_once './backend/classes/unit.php';
require_once './backend/classes/list.php';
require_once './backend/classes/test.php';
require_once './phptests/TestDB.php';

class TestClassTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $unit;

	public function setup()
	{
		Session::set(null);
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
		
		$this->db->add_dictionary_entries(7);
		$this->db->add_users(2);
		$this->db->add_course($this->db->user_ids[0]);
		
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		$this->unit = Unit::insert($this->db->course_ids[0], "unit_setup");
	}
	
	public function test_insert()
	{
		Session::get()->set_user(null);
		$test = Test::insert($this->unit->get_unit_id());
		$this->assertNull($test);
		
		// user-don't-have-write-access
		$user_obj = User::select_by_id($this->db->user_ids[1]);
		Session::get()->set_user($user_obj);
		$test = Test::insert($this->unit->get_unit_id());
		$this->assertNull($test);
		
		// unit-id-not-present
		$user_obj = $this->unit->get_owner();
		Session::get()->set_user($user_obj);
		$test = Test::insert(-5);
		$this->assertNull($test);
		
		$name = "test_setup";
		$test = Test::insert($this->unit->get_unit_id(), $name);
		$this->assertEquals($user_obj, $test->get_owner());
		$this->assertNotNull($test);
		$this->assertNotNull($test->get_test_id());
		$this->assertEquals($test->get_test_name(), $name);
		$this->assertEquals($test->get_unit_id(), $this->unit->get_unit_id());
		$this->assertEquals($test->get_unit(), $this->unit);
		$this->assertEquals($test->get_course(), $this->unit->get_course());
		$this->assertNull($test->get_timeframe());
	}

	public function test_entries_add()
	{
		$user0 = $this->unit->get_owner();
		$user1 = User::select_by_id($this->db->user_ids[1]);
		$this->assertNotEquals($user0, $user1);
		
		Session::get()->set_user($user0);
		
		$name = "test_setup";
		$test = Test::insert($this->unit->get_unit_id(), $name);
		
		//  wrong user
		Session::get()->set_user($user1);
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$this->assertNull($test->entries_add($entry));
		
		//  right user
		Session::get()->set_user($user0);
		$this->assertCount(0, $test->entries());
		$this->assertTrue($test->session_user_can_write());
		$this->assertNotNull($test->entries_add($entry, ($mode = 4)));
		$this->assertCount(1, $test->entries());
		$this->assertEquals($mode, $test->get_entry_mode($entry));
		$test_entries = $test->entries();
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$this->assertEquals($entry->copy_for_user($test->get_owner()), $test_entries[0]);
		$this->assertCount(7, $test->entry_options($entry));
	}
	
	public function test_entries_add_from_list()
	{
		$user0 = $this->unit->get_owner();
		$user1 = User::select_by_id($this->db->user_ids[1]);
		$this->assertNotEquals($user0, $user1);
		
		Session::get()->set_user($user0);
		$this->db->add_list($user0->get_user_id(), $this->db->entry_ids);
		$user_lists = $user0->lists();
		$list = $user_lists[0];
		
		$name = "test_setup";
		$test = Test::insert($this->unit->get_unit_id(), $name);
		
		//  wrong user
		Session::get()->set_user($user1);
		$this->assertNull($test->entries_add_from_list($list));
		
		//  right user
		Session::get()->set_user($user0);
		$this->assertCount(0, $test->entries());
		$this->assertNotNull($test->entries_add_from_list($list, ($mode = 4)));
		$list_entries = $list->entries();
		$test_entries = $test->entries();
		$this->assertCount(count($list_entries), $test_entries);
		for ($i = 0; $i < count($list_entries); $i ++)
		{
			$this->assertEquals($test_entries[$i], $list_entries[$i]);
			$this->assertEquals($mode, $test_entries[$i]->get_mode());
		}
	}
}

?>
