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
		$this->assertEquals($test->get_name(), $name);
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
		$entry = Entry::select_by_id($this->db->entry_ids[0])->copy_for_user($test->get_owner());
		$this->assertNull($test->entries_add($entry));
		
		//  right user
		Session::get()->set_user($test->get_owner());
		$this->assertCount(0, $test->entries());
		$this->assertTrue($test->session_user_can_write());
		$this->assertNotNull($test->entries_add($entry, ($mode = 4)));
		$this->assertCount(1, $test->entries());
		$this->assertEquals($mode, $test->get_entry_mode($entry)->get_mode_id());
		$test_entries = $test->entries();
		$this->assertEquals($entry, array_shift($test_entries));
		$this->assertCount(1, $test->entry_options($entry));
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
		$this->assertEquals($list->get_owner(), $test->get_owner());
		$entry = Entry::select_by_id($this->db->entry_ids[0])->copy_for_user($test->get_owner());
		$this->assertNotNull($test->entries_add($entry, ($mode = 4)));
		
		//  wrong user
		Session::get()->set_user($user1);
		$this->assertNull($test->entries_add_from_list($list));
		
		//  right user, but test has already started execution
		Session::get()->set_user($user0);
		$course = $test->get_course();
		$this->assertNotNull($course->students_add($user1));
		$this->assertFalse($test->executed());
		Session::get()->set_user($user1);
		$this->assertNotNull($test->execute_for_session_user());
		$this->assertNull($test->entries_add_from_list($list));
		Session::get()->set_user($test->get_owner());
		$test->unexecute();
		$this->assertFalse($test->executed());
		
		//  right user, and test has not already started
		$this->assertCount(1, $test->entries());
		$this->assertNotNull($test->entries_add_from_list($list, ($mode = 4)));
		$list_entries = $list->entries();
		$this->assertTrue(in_array($entry, $list_entries));
		$test_entries = $test->entries();
		$this->assertCount(count($list_entries), $test_entries);
		foreach ($test_entries as $test_entry)
		{
			$this->assertTrue(in_array($test_entry, $list_entries));
			$this->assertEquals($mode, $test->get_entry_mode($test_entry)->get_mode_id());
		}
		foreach ($list_entries as $list_entry)
		{
			$this->assertTrue(in_array($list_entry, $test_entries));
		}
	}
	
	public function test_entries_randomize()
	{
		
	}
	
	public function test_patterns()
	{
		
	}
	
	public function test_unexecute()
	{
		
	}
	
	public function test_setters()
	{
		// message
		// timer
		// timeframe
		// open, close
	}
	
	public function test_execute_for_session_user()
	{
		
	}
	
	public function test_delete()
	{
		
	}
	
	public function test_entries()
	{
		// entries
		// entries count
		// entry options
		// entries add
		// entries remove
		// entries randomize
		// get mode
		// set mode
		// set number
		// entries by number
	}
	
	public function test_sittings()
	{
		// sittings count
		// get sitting for user
		// sittings
	}
	
	public function test_patterns()
	{
		// patterns
		// count patterns
	}
	
	public function test_seconds_per_entry()
	{
		
	}
	
	public function test_entry_score_max()
	{
		
	}
	
	public function test_score_max()
	{
		
	}
}

?>
