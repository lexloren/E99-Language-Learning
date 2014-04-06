<?php

//Tests class list
require_once './backend/classes/unit.php';
require_once './backend/classes/list.php';
require_once './backend/classes/timeframe.php';
require_once './phptests/TestDB.php';

class UnitTest extends PHPUnit_Framework_TestCase
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

	public function test_unit_insert()
	{
		Session::get()->set_user(null);
                $name = "test_unit1";
                $unit = Unit::insert($this->db->course_ids[0], $name);
                $this->assertNull($unit);
                
		// user-don't-have-write-access
                $user_obj = User::select_by_id($this->db->user_ids[1]);
                Session::get()->set_user($user_obj);
                $unit = Unit::insert($this->db->course_ids[0], $name);
                $this->assertNull($unit);

		// course-id-not-present
                $user_obj = User::select_by_id($this->db->user_ids[0]);
                Session::get()->set_user($user_obj);
                $unit = Unit::insert(100, $name);
                $this->assertNull($unit);

                $unit = Unit::insert($this->db->course_ids[0], $name);
                $this->assertNotNull($unit);
                $this->assertEquals($unit->get_unit_name(), $name);
	}

	public function test_unit_select()
	{
		$user_obj = User::select_by_id($this->db->user_ids[0]);
                Session::get()->set_user($user_obj);
                $name = "test_unit1";
		$unit_expect = Unit::insert($this->db->course_ids[0], $name);
		$this->assertNotNull($unit_expect);

		$unit_actual = Unit::select_by_id($unit_expect->get_unit_id());
		$this->assertNotNull($unit_actual);
		$this->assertEquals($unit_expect->get_unit_name(), $unit_actual->get_unit_name());
		$this->assertEquals($unit_expect->get_number(), $unit_actual->get_number());
		$this->assertEquals($unit_expect->get_course_id(), $unit_actual->get_course_id());
		$this->assertEquals($unit_expect->get_course(), $unit_actual->get_course());
		$this->assertEquals($unit_expect->get_lists(), $unit_actual->get_lists());
		$this->assertEquals($unit_expect->get_timeframe(), $unit_actual->get_timeframe());
	}

	public function test_unit_set_attributes()
	{
		$this->unit->set_unit_name("new_name");
		$this->assertEquals($this->unit->get_unit_name(), "new_name");
		$open = 1396382400000;
		$close = 1396468800000;
		$timeframe = new Timeframe($open, $close);
		$this->unit->set_timeframe($timeframe);
		$this->assertEquals($this->unit->get_timeframe(), $timeframe);
		$this->unit->set_open($open+5);
		$this->assertEquals($this->unit->get_timeframe()->get_open(), $open+5);
		$this->unit->set_close($close+5);
		$this->assertEquals($this->unit->get_timeframe()->get_close(), $close+5);
		$this->unit->set_message("test_message");
		$this->assertEquals($this->unit->get_message(), "test_message");
	}

	public function test_unit_lists()
	{
		$entries = $this->db->add_dictionary_entries(5);
		$list_id = $this->db->add_list($this->db->user_ids[0], $entries);
		$unit = $this->unit->lists_add(EntryList::select_by_id($list_id));
		$got_lists = $unit->get_lists();
		$this->assertEquals($got_lists[0]->get_list_id(), $list_id);
	}
}

?>
