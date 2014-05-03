<?php

require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class GradeTest extends PHPUnit_Framework_TestCase
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

	public function testGradeInsert()
	{
		Grade::insert(0);
		$this->assertNotNull(Grade::errors_unset());
	}

	public function testGradeDelete()
	{
		$grade_id = $this->db->grade_ids[0];
		$grade = Grade::select_by_id($grade_id);
		$this->assertNotNull($grade);
		$this->assertEquals($grade->get_grade_id(), $grade_id);
		$grade->delete();
		$this->assertNotNull(Grade::errors_unset());
	}

	public function testGradeSelect()
	{
		$grade_id = $this->db->grade_ids[0];
                $grade = Grade::select_by_id($grade_id);
		$this->assertNotNull($grade);
                $this->assertEquals($grade->get_grade_id(), $grade_id);
		$grade_by_point = Grade::select_by_point($grade->get_point());
		$this->assertEquals($grade_by_point->get_grade_id(), $grade_id);
		$this->assertEquals($grade->get_desc_long(), $grade_by_point->get_desc_long());
		$this->assertEquals($grade->get_desc_short(), $grade_by_point->get_desc_short());
	}

	public function testGradeJsonAssoc()
	{
		$grade_id = $this->db->grade_ids[0];
                $grade = Grade::select_by_id($grade_id);
                $this->assertNotNull($grade);
                $this->assertEquals($grade->get_grade_id(), $grade_id);
		$grade_assoc = $grade->json_assoc();
		$this->assertArrayHasKey("gradeId", $grade_assoc);
		$this->assertArrayHasKey("point", $grade_assoc);
		$this->assertEquals($grade_assoc["gradeId"], $grade_id);
	}
}
?>
