<?php

require_once './apis/APIList.php';
require_once './phptests/TestDB.php';
require_once './backend/classes/session.php';

class APIListTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $obj;
	public function setup()
	{
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");

		$this->obj = new APIList(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APIList");
		
		
		$session_mock = $this->getMock('Session', array('session_start', 'session_end', 'session_regenerate_id'));

        // Configure the stub.
        $session_mock->expects($this->any())
					 ->method('session_start')
					 ->will($this->returnValue(TestDB::$session));
		
		$session_mock->expects($this->any())
					 ->method('session_regenerate_id')
					 ->will($this->returnValue(TestDB::$session));
					 
		$this->assertNotNull($session_mock, "failed to create session mock");
		Session::set($session_mock);
		$this->assertEquals(Session::get(), $session_mock);
	}
	
	public function test_insert()
	{
		$_SESSION["handle"] = TestDB::$handle;

		for($i=0; $i<2; $i++)
		{
			if (1 == $i)
				$_POST["list_name"] = 'new_list1';

			$this->obj->insert();
		
			$this->assertFalse(Session::get()->has_error());
			
			$result_assoc = Session::get()->get_result_assoc();		
			$this->assertNotNull($result_assoc);
			
			$result = $result_assoc["result"];		
			$this->assertNotNull($result);
			
			$this->assertArrayHasKey('listId', $result);
			$this->assertArrayHasKey('listName', $result);
			$this->assertArrayHasKey('owner', $result);
			$this->assertArrayHasKey('isPublic', $result);
			
			$this->assertEquals($result["listName"], $i == 0 ? null : $_POST["list_name"]);
			$this->assertEquals($result["owner"]["handle"], TestDB::$handle);
		}
	}
	
	public function test_insert_no_session()
	{
		$_POST["list_name"] = 'new_list1';
		$this->obj->insert();	
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_delete()
	{
		$_SESSION["handle"] = TestDB::$handle;
		$_POST["list_id"] = TestDB::$list_id;
		$this->obj->delete();	
		$this->assertFalse(Session::get()->has_error());

		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		
		$this->assertNotNull($result);

		$this->assertEquals($result["listName"], TestDB::$list_name);
	}
	
	public function test_delete_no_id()
	{
		$_SESSION["handle"] = TestDB::$handle;
		$this->obj->delete();	
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_delete_no_session()
	{
		$_POST["list_id"] = TestDB::$list_id;
		$this->obj->delete();	
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_entries()
	{
		$_SESSION["handle"] = TestDB::$handle;
		$_GET["list_id"] = TestDB::$list_id;
		$this->obj->entries();	

		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		
		$this->assertNotNull($result);

		//TODO
		//print_r($result);
	}
	
	public function test_entries_add()
	{
		
	}
	
	public function test_entries_remove()
	{
		
	}
	
}
?>