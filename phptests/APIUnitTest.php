<?php

require_once './apis/APIUnit.php';
require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class APIUnitTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $obj;
	public function setup()
	{
		Session::set(null);

		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");

		$this->db->add_dictionary_entries(3);
		$this->db->add_users(2);

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
		

		$this->obj = new APIUnit(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APIUnit");
	}
	
	public function test_select()
	{
		$course_id = $this->db->add_course($this->db->user_ids[0]);
		$_GET["unit_id"] = $this->db->add_course_unit($course_id);
		
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
		$this->assertEquals($result["unitId"], $_GET["unit_id"]);
		$this->assertEquals($result["courseId"], $course_id);
	}
	
	
	public function test_insert()
	{
		$list_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$_POST["course_id"] = $this->db->add_course($this->db->user_ids[0]);
		$_POST["name"] = "Lesson 1";
		$_POST["message"] = "Lesson for dummies";
		$_POST["list_ids"] = implode(",", array($list_id));
		
		//Session user not set
		$this->obj->insert();
		$this->assertTrue(Session::get()->has_error());
		
		//Session user set
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->insert();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);		
		$this->assertNotNull($result["unitId"]);
		$this->assertEquals($result["courseId"], $_POST["course_id"]);
		$this->assertEquals($result["name"], $_POST["name"]);
		$this->assertEquals($result["message"], $_POST["message"]);
		$this->assertEquals($result["listsCount"], 1);
		$this->assertEquals($result["testsCount"], 0);
	}
	
	public function test_delete()
	{
		$course_id = $this->db->add_course($this->db->user_ids[0]);
		$_POST["unit_id"] = $this->db->add_course_unit($course_id);
		
		//wrong session user
		$_SESSION["handle"] = $this->db->handles[1];
		$this->obj->delete();
		$this->assertTrue(Session::get()->has_error());
		
		//Session user set
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->delete();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);		
		$this->assertEquals($result["unitId"], $_POST["unit_id"]);
		$this->assertEquals($result["courseId"], $course_id);
	}
	
	public function test_update()
	{
		$course_id = $this->db->add_course($this->db->user_ids[0]);
		$_POST["unit_id"] = $this->db->add_course_unit($course_id);
		$_POST["name"] = "Lesson 1";
		$_POST["num"] = 1;
		$_POST["message"] = "Lesson for dummies";
		//  My PHP complains that strtotime needs a time zone
		$_POST["open"] = 1397260800;
		$_POST["close"] = 1399852800;

		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->update();
		$this->assertFalse(Session::get()->has_error());

		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
		//print_r($result_assoc);
		
		$this->assertEquals($result["name"], $_POST["name"]);
		$this->assertEquals($result["message"], $_POST["message"]);
		
		$this->assertNotNull($result["timeframe"]);
		$timeframe  = $result["timeframe"];
		$this->assertEquals($timeframe["open"], $_POST["open"]);
		$this->assertEquals($timeframe["close"], $_POST["close"]);
	}
	public function test_lists()
	{
		$list_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$course_id = $this->db->add_course($this->db->user_ids[0]);
		$unit_id = $this->db->add_course_unit($course_id);
		$this->db->add_unit_list($unit_id, $list_id);
		
		$_GET["unit_id"] = $unit_id;
		//Session user not set
		$this->obj->lists();
		$this->assertTrue(Session::get()->has_error());
		
		//Session user set
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->lists();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);		
		//print_r($result);
		$this->assertCount(1, $result);
		$list0 = $result[0];
		$this->assertEquals($list0["listId"], $list_id);
	}
	public function test_lists_add()
	{
		$list1_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$list2_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$course_id = $this->db->add_course($this->db->user_ids[0]);
		$unit_id = $this->db->add_course_unit($course_id);
		$this->db->add_unit_list($unit_id, $list1_id);
		$this->db->add_unit_list($unit_id, $list2_id);
		
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["unit_id"] = $unit_id;
		$_POST["list_ids"] = implode(",", array (0 => $list1_id));
		
		$this->obj->lists_remove();
		
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(1, $result);
	}
	public function test_lists_remove()
	{
		$list1_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$list2_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$course_id = $this->db->add_course($this->db->user_ids[0]);
		$unit_id = $this->db->add_course_unit($course_id);
		
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["unit_id"] = $unit_id;
		$_POST["list_ids"] = implode(",", array($list1_id, $list2_id));
		
		$this->obj->lists_add();
		
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(2, $result);
	}
	public function test_test()
	{
		$list_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$course_id = $this->db->add_course($this->db->user_ids[0]);
		$unit_id = $this->db->add_course_unit($course_id);
		$this->db->add_unit_list($unit_id, $list_id);
		
		$_GET["unit_id"] = $unit_id;
		//Session user not set
		$this->obj->tests();
		$this->assertTrue(Session::get()->has_error());
		
		//Session user set
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->tests();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);		
		//print_r($result);
		$this->assertCount(0, $result);
	}
}



?>
