<?php

require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class ModeTest extends PHPUnit_Framework_TestCase
{
        private $db;

        public function setup()
        {
                Session::set(null);
                $this->db = TestDB::create();
                $this->assertNotNull($this->db, "failed to create test database");

                $this->db->add_users(2);
                $user_obj = User::select_by_id($this->db->user_ids[0]);
                Session::get()->set_user($user_obj);
        }

        public function tearDown()
        {
                        //if (isset($this->db))
                        //      $this->db->close();
        }

	public function testModeInsert()
	{
		Mode::insert("testFrom", "testTo");
		$this->assertNotNull(Mode::errors_unset());
	}

	public function testModeDelete()
	{
		$mode_id = $this->db->mode_ids[0];
		$mode = Mode::select_by_id($mode_id);
		$this->assertNotNull($mode);
		$this->assertEquals($mode->get_mode_id(), $mode_id);
		$mode->delete();
		$this->assertNotNull(Mode::errors_unset());
	}

	public function testModeSelect()
	{
		$mode_id = $this->db->mode_ids[0];
                $mode = Mode::select_by_id($mode_id);
		$this->assertNotNull($mode);
                $this->assertEquals($mode->get_mode_id(), $mode_id);
		$mode_by_direction = Mode::select_by_direction($mode->get_direction_from(), $mode->get_direction_to());
		$this->assertEquals($mode_by_direction->get_mode_id(), $mode_id);

		$modes = Mode::select_all();
		$this->assertNotNull($modes);
		$this->assertCount(count($this->db->mode_ids), $modes);
	}

	public function testModeJsonAssoc()
	{
		$mode_id = $this->db->mode_ids[0];
                $mode = Mode::select_by_id($mode_id);
                $this->assertNotNull($mode);
                $this->assertEquals($mode->get_mode_id(), $mode_id);
		$mode_assoc = $mode->json_assoc();
		$this->assertArrayHasKey("modeId", $mode_assoc);
		$this->assertArrayHasKey("directionFrom", $mode_assoc);
		$this->assertArrayHasKey("directionTo", $mode_assoc);
		$this->assertEquals($mode_assoc["modeId"], $mode_id);
	}
}
?>
