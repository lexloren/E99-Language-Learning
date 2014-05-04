<?php

require_once './apis/APITest.php';
require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class APITestTest extends PHPUnit_Framework_TestCase
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
		$this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_unit($this->db->course_ids[0]);

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
		

		$this->obj = new APITest(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APITest");
	}

	public function test_insert()
	{
		//No handle
		$_POST["unit_id"] = $this->db->course_unit_ids[0];
		$this->obj->insert();
		$this->assertTrue(Session::get()->has_error());

		//handle
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->insert();
		$this->assertFalse(Session::get()->has_error());
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($test_assoc["result"]);
		
		$this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$list_ids = implode(",", $this->db->list_ids);
		$_POST["list_ids"] = $list_ids;
		$this->obj->insert();
                $this->assertFalse(Session::get()->has_error());
                $test_assoc = Session::get()->get_result_assoc();
                $this->assertNotNull($test_assoc);

		$_POST["list_ids"] = "27,40,95,103,".$list_ids;
		$this->obj->insert();
                $this->assertTrue(Session::get()->has_error());
                $test_assoc = Session::get()->get_result_assoc();
                $this->assertNotNull($test_assoc);

		$_POST["list_ids"] = $list_ids;
		$entries = $this->db->add_dictionary_entries(3);
		$entry_ids = implode(",", $entries);
		$_POST["entry_ids"] = $entry_ids;
		$_POST["name"] = "test-name";
		$_POST["open"] = time();
		$_POST["close"] = time() + (7 * 24 * 60 * 60);
		$this->obj->insert();
                $this->assertFalse(Session::get()->has_error());
                $test_assoc = Session::get()->get_result_assoc();
                $this->assertNotNull($test_assoc);
		$this->assertArrayHasKey("testId", $test_assoc["result"]);
		$this->assertArrayHasKey("name", $test_assoc["result"]);

		$_POST["entry_ids"] = "122,255,968";
		$this->obj->insert();
		$this->assertTrue(Session::get()->has_error());
                $test_assoc = Session::get()->get_result_assoc();
                $this->assertNotNull($test_assoc);
	}

	public function test_select()
	{
		//No handle
                $unit_id = $this->db->course_unit_ids[0];
		$test_id = $this->db->add_unit_test($unit_id);
                $_GET["test_id"] = $test_id;
                $this->obj->select();
                $this->assertTrue(Session::get()->has_error());

                //handle
                $_SESSION["handle"] = $this->db->handles[0];
                $this->obj->select();
                /*$this->assertFalse(Session::get()->has_error());
                $test_assoc = Session::get()->get_result_assoc();
                $this->assertNotNull($test_assoc["result"]);
		$this->assertEquals($test_assoc["result"]["testId"], $test_id);*/
	}

	public function test_delete()
	{
		
	}
}



?>
