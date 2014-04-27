<?php
	
require_once './apis/APICourse.php';
require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class APICourseTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $obj;
	public function setup()
	{
		Session::set(null);
		
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
		
		$this->db->add_users(1);
		$session_mock = $this->getMock('Session', array('session_start', 'session_end', 'session_regenerate_id'));
		
		// Configure the stub.
		$session_mock->expects($this->any())
		->method('session_start')
		->will($this->returnValue($this->db->sessions[0]));
		
		$session_mock->expects($this->any())
		->method('session_regenerate_id')
		->will($this->returnValue($this->db->sessions[0]));
		
		$this->assertNotNull($session_mock, "failed to create session mock");
		Session::set($session_mock);
		$this->assertEquals(Session::get(), $session_mock);
		
		
		$this->obj = new APICourse(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APICourse");
	}
	

	public function test_select()
	{
		$_GET["course_id"] =  $this->db->add_course($this->db->user_ids[0]);
		
		//Session user not set
		$this->obj->select();
		$this->assertTrue(Session::get()->has_error());
		
		//Session user set
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->select();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
	}

	public function test_course_find_by_user_ids()
	{
		$this->db->add_users(3);
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		
		$course1_id = $this->db->add_course($this->db->user_ids[0], 1);
		$course2_id = $this->db->add_course($this->db->user_ids[1], 1);
		$course3_id = $this->db->add_course($this->db->user_ids[0], 1);
		
		$_SESSION["handle"] = $this->db->handles[0];
		
		$_GET["user_ids"] = implode(",", array($this->db->user_ids[0]));
		$this->obj->find();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
		$this->assertCount(2, $result);
		
		$result0 = $result[0];
		$result1 = $result[1];
		
		$this->assertTrue($result0["courseId"] != $result1["courseId"]);
		$this->assertTrue($result0["courseId"] == $course1_id || $result0["courseId"] == $course3_id );
		$this->assertTrue($result1["courseId"] == $course1_id || $result1["courseId"] == $course3_id );
	}
	
	public function test_course_find_by_course_ids()
	{
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		
		$course1_id = $this->db->add_course($this->db->user_ids[0]);
		$course2_id = $this->db->add_course($this->db->user_ids[0]);
		$course3_id = $this->db->add_course($this->db->user_ids[0]);
		
		$_SESSION["handle"] = $this->db->handles[0];
		
		$_GET["course_ids"] = implode(",", array($course1_id, $course3_id));
		$this->obj->find();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(2, $result);
		$result0 = $result[0];
		$result1 = $result[1];
		
		$this->assertTrue($result0["courseId"] != $result1["courseId"]);
		$this->assertTrue($result0["courseId"] == $course1_id || $result0["courseId"] == $course3_id );
		$this->assertTrue($result1["courseId"] == $course1_id || $result1["courseId"] == $course3_id );
	}
	
	public function test_practice_report()
	{
		$this->db->add_users(3);
		$this->db->add_dictionary_entries(10);
		
		$course_id = $this->db->add_course($this->db->user_ids[1]);
		$course_unit_id = $this->db->add_course_unit($this->db->course_ids[0]);
		$list_id = $this->db->add_list($this->db->user_ids[1], $this->db->entry_ids);
		$this->db->add_unit_list($course_unit_id, $list_id);
		
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[2]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[2], 3);
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[3]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[3], 2);

		$this->db->add_course_researcher($this->db->course_ids[0], $this->db->user_ids[0]);
		
		$_GET["course_id"] =  $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		
		$this->obj->practice_report();
		
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
		//print $result;
	}

	public function test_student_practice_report()
	{
		$this->db->add_users(3);
		$this->db->add_dictionary_entries(10);
		
		$course_id = $this->db->add_course($this->db->user_ids[1]);
		$course_unit_id = $this->db->add_course_unit($this->db->course_ids[0]);
		$list_id = $this->db->add_list($this->db->user_ids[1], $this->db->entry_ids);
		$this->db->add_unit_list($course_unit_id, $list_id);
		
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[0]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[0], 3);
		
		$_GET["course_id"] =  $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		
		$this->obj->student_practice_report();
		$this->assertFalse(Session::get()->has_error());

		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
		//print $result;
	}
}
	
?>