<?php

require_once './apis/APIAnnotation.php';
require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class APIAnnotationTest extends PHPUnit_Framework_TestCase
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
		

		$this->obj = new APIAnnotation(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APIAnnotation");
	}

	public function test_select()
	{
		$entries = $this->db->add_dictionary_entries(3);
		$this->db->add_list($this->db->user_ids[0], $entries);
		
		$_GET["annotation_id"] = $this->db->annotation_ids[0];
		
		//No handle
		$this->obj->select();
		$this->assertTrue(Session::get()->has_error());
		
		//handle
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->select();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertEquals($result["annotationId"], $_GET["annotation_id"]);
		$this->assertEquals($result["contents"], TestDB::$entry_annotation);		
	}
	
	public function test_insert()
	{
		$entries = $this->db->add_dictionary_entries(3);
		$this->db->add_list($this->db->user_ids[0], $entries);
		
		$_POST["contents"] = "User annotation";
		$_POST["entry_id"] = $entries[2];
		
		//handle
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->insert();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertEquals($result["entryId"], $_POST["entry_id"]);
		$this->assertEquals(2, $result["annotationsCount"]); //first annotation is added by TestDB::add_list
	}
	
	public function test_delete()
	{
		$entries = $this->db->add_dictionary_entries(3);
		$this->db->add_list($this->db->user_ids[0], $entries);
		
		$_POST["annotation_id"] = $this->db->annotation_ids[0];
		
		//No handle
		$this->obj->delete();
		$this->assertTrue(Session::get()->has_error());
		
		//handle
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->delete();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertEquals($result["annotationId"], $_POST["annotation_id"]);
	}
}



?>
