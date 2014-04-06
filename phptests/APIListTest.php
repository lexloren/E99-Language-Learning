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
		Session::set(null);
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
		$this->db->add_users(3);
		$this->db->add_dictionary_entries(5);
		$this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		
		$this->obj = new APIList(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APIList");
		
		
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
	}
	
	public function test_insert()
	{
		$_SESSION["handle"] = $this->db->handles[0];

		for($i=0; $i<2; $i++)
		{
			if (1 == $i)
				$_POST["name"] = 'new_list1';

			$this->obj->insert();
		
			$this->assertFalse(Session::get()->has_error());
			
			$result_assoc = Session::get()->get_result_assoc();		
			$this->assertNotNull($result_assoc);
			
			$result = $result_assoc["result"];		
			$this->assertNotNull($result);
			
			$this->assertArrayHasKey('listId', $result);
			$this->assertArrayHasKey('name', $result);
			$this->assertArrayHasKey('owner', $result);
			$this->assertArrayHasKey('isPublic', $result);
			
			$this->assertEquals($result["name"], $i == 0 ? null : $_POST["name"]);
			$this->assertEquals($result["owner"]["handle"], $this->db->handles[0]);
		}
	}
	
	public function test_insert_no_session()
	{
		$_POST["name"] = 'new_list1';
		$this->obj->insert();	
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_delete()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["list_id"] = $this->db->list_ids[0];
		$this->obj->delete();
		$this->assertFalse(Session::get()->has_error());

		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		
		$this->assertNotNull($result);

		$this->assertEquals($result["name"], $this->db->list_names[0]);
	}

	public function test_delete_no_id()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->delete();	
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_delete_no_session()
	{
		$_POST["list_id"] = $this->db->list_ids[0];
		$this->obj->delete();	
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_entries()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$_GET["list_id"] = $this->db->list_ids[0];
		$this->obj->entries();	

		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		
		$this->assertNotNull($result);

		$this->assertCount(5, $result);
		
		//Hans, pleace check this. Result is wrong, 'words' are empty. 
		//print_r($result[0]);
		//$this->assertCount(2, $result[0]["words"]);
		//$this->assertNotNull($result[0]["words"][TestDB::$lang_code_0]);
		//$this->assertNotNull($result[0]["words"][TestDB::$lang_code_1]);
	}
	
	public function test_select()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$_GET["list_id"] = $this->db->list_ids[0];
		$this->obj->select();
		$this->assertFalse(Session::get()->has_error());

		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		

		$this->assertNotNull($result);
		$this->assertEquals($result["name"], $this->db->list_names[0]);
	}
	
	public function test_select_no_session()
	{
		$_GET["list_id"] = $this->db->list_ids[0];
		$this->obj->select();
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_select_wrong_id()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$_GET["list_id"] = 0;
		$this->obj->select();
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_update()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["list_id"] = $this->db->list_ids[0];
		$_POST["name"] = "new-list-Name";
		$this->obj->update();
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];	
		$this->assertEquals("new-list-Name", $result["name"]);
	}
	
	public function test_update_no_session()
	{
		$_POST["list_id"] = $this->db->list_ids[0];
		$_POST["name"] = "new-list-Name";
		$this->obj->update();
		
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_entries_add()
	{
		$new_entries = $this->db->add_dictionary_entries(2);
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["list_id"] = $this->db->list_ids[0];
		$_POST["entry_ids"] = implode(",", $new_entries);
		
		$this->obj->entries_add();	

		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		
		$this->assertNotNull($result);
		
		//TODO: Check database
	}
	
	public function test_entries_remove()
	{
		$entries_to_remove = Array($this->db->entry_ids[0], $this->db->entry_ids[4]);
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["list_id"] = $this->db->list_ids[0];
		$_POST["entry_ids"] = implode(",", $entries_to_remove);
		
		$this->obj->entries_remove();	

		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		
		$this->assertNotNull($result);
		//TODO: Check database
	}
}
?>
