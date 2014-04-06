<?php

require_once './apis/APIEntry.php';
require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class APIEntryTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $obj;
	public function setup()
	{
		Session::set(null);

		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");

		$this->db->add_dictionary_entries(3);
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
		

		$this->obj = new APIEntry(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APIEntry");
	}
	
	public function test_select()
	{
		$_GET["entry_id"] =  $this->db->entry_ids[0];

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
		$this->assertNotNull($this->db->word_0s[0], $result["words"][TestDB::$lang_code_0]);
		$this->assertNotNull($this->db->word_1s[0], $result["words"][TestDB::$lang_code_1]);
	}
	
	public function test_update()
	{
	}

	public function test_find()
	{
	}

	public function test_annotations()
	{
	}

}



?>
