<?php

//Tests class list
require_once './backend/classes/unit.php';
require_once './phptests/TestDB.php';

class UnitTest extends PHPUnit_Framework_TestCase
{
        private $db;

        public function setup()
        {
                Session::set(null);
                $this->db = TestDB::create();
                $this->assertNotNull($this->db, "failed to create test database");

                $this->db->add_dictionary_entries(7);
                $this->db->add_users(2);
		$this->db->add_course($this->db->user_ids[0]);
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
}

?>
