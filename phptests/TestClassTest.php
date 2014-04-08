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
                $user_obj = User::select_by_id($this->db->user_ids[0]);
                Session::get()->set_user($user_obj);
                $test = Test::insert(-5);
                $this->assertNull($test);

		$name = "test_setup";
                $test = Test::insert($this->unit->get_unit_id(), $name);
                $this->assertNotNull($test);
		$this->assertNotNull($test->get_test_id());
                $this->assertEquals($test->get_test_name(), $name);
		$this->assertEquals($test->get_unit_id(), $this->unit->get_unit_id());
		$this->assertEquals($test->get_unit(), $this->unit);
		$this->assertEquals($test->get_course(), $this->unit->get_course());
		$this->assertNull($test->get_timeframe());
	}

}

?>
