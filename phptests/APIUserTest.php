<?php

require_once './apis/APIUser.php';
require_once './phptests/TestDB.php';
require_once './backend/classes/session.php';

class APIUserTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $obj;
	public function setup()
	{
		$session_mock = $this->getMock('Session', array('session_start'));

        // Configure the stub.
        $session_mock->expects($this->any())
					 ->method('session_start')
					 ->will($this->returnValue('dummy_session_id'));

		$this->assertNotNull($session_mock, "failed to create session mock");
		Session::set($session_mock);
		$this->assertEquals(Session::get(), $session_mock);
		
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");

		$this->obj = new APIUser(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APIUser");
	}
	
	public function tearDown()
	{
		//if (isset($this->db))
		//	$this->db->close();
	}
	
	public function testRegister()
	{
		$_POST["email"] = 'someone@somewhere.com';
		$_POST["handle"] = 'username1';
		$_POST["password"] = 'P@ssword1';
		$this->obj->register();
		
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc['result'];		
		$this->assertNotNull($result);

		$this->assertEquals($result['handle'], 'username1');
		
		$this->assertEquals($result['email'], null);
	}
	
	public function testRegisterNoEmail()
	{
		$_POST["handle"] = 'username1';
		$_POST["password"] = 'P@ssword1';
		$this->obj->register();
		
		$this->assertTrue(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$error = $result_assoc['errorTitle'];		
		$this->assertNotNull($error);
	}
	
	public function testRegisterNoHandle()
	{
		$_POST["email"] = 'someone@somewhere.com';
		$_POST["password"] = 'P@ssword1';
		$this->obj->register();
		
		$this->assertTrue(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$error = $result_assoc['errorTitle'];		
		$this->assertNotNull($error);
	}

	public function testRegisterNoPassword()
	{
		$_POST["handle"] = 'username1';
		$_POST["email"] = 'someone@somewhere.com';

		$this->obj->register();
		
		$this->assertTrue(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$error = $result_assoc['errorTitle'];		
		$this->assertNotNull($error);
	}
	
	private function session_authenticate_mock()
	{
	}
	
	public function testAuthenticate()
	{
		$_POST["handle"] = TestDB::$handle;
		$_POST["password"] = TestDB::$password;
		$this->obj->authenticate();
		
		$this->assertFalse(Session::get()->has_error());
		$this->assertNotNull(Session::get()->get_user());
		$this->assertEquals(Session::get()->get_user()->get_handle(), TestDB::$handle);
	}
	
}
?>