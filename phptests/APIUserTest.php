<?php

require_once './apis/APIUser.php';
require_once './backend/connection.php';
require_once './tools/database.php';

class APIUserTest extends PHPUnit_Framework_TestCase
{
	private $link;
	private $obj;
	public function setup()
	{
		$this->link = database::recreate_database('cscie99test');
		
		$this->assertNotNull($this->link, "No database connection");

		Connection::set_shared_instance($this->link);

		$this->obj = new APIUser(null, $this->link);
		$this->assertNotNull($this->obj, "Null APIUser");
	}
	
	public function tearDown()
	{
		//if (isset($this->link))
		//	$this->link->close();
	}
	
	public function testRegister()
	{
		$_POST["email"] = 'someone@somewhere.com';
		$_POST["handle"] = 'username1';
		$_POST["password"] = 'P@ssword1';
		$this->obj->register();
		
		$result = $$this->link->query("SELECT * FROM users WHERE handle = username2");
		
		$this->assertNotNull($result, "Null result");
		$this->assertNotNull($result->fetch_assoc(), "Null assoc");

	}
	
	public function testAuthenticate()
	{
	}
	
}
?>