<?php

//Tests class User
require_once './backend/classes/user.php';
require_once './backend/connection.php';
require_once './tools/database.php';

class UserTest extends PHPUnit_Framework_TestCase
{
	private $link;

	public function setup()
	{
		$this->link = database::recreate_database('cscie99test');
		
		$this->assertNotNull($this->link, "No database connection");

		Connection::set_shared_instance($this->link);
	}
	
	public function tearDown()
	{
		//if (isset($this->link))
		//	$this->link->close();
	}

	public function testInsert()
	{
		$email = 'some1@somewhere.com';
		$handle = 'username1';
		$password = 'P@ssword1';
		$family = 'SomeFamily1';
		$given = 'SomeGiven1';

		$user_obj = User::insert($email, $handle, $password, $family, $given);
		
		//Check database
		$result = $this->link->query(sprintf("SELECT * FROM users WHERE handle = '%s'", $this->link->escape_string($handle)));
		$this->assertNotNull($result, "Null result");
		$user_assoc = $result->fetch_assoc();
		$this->assertNotNull($user_assoc, "Null user_assoc");
		$this->assertEquals($user_assoc["email"], $email);
		$user_id = $user_assoc["user_id"];
		$this->assertNotEquals($user_id, 0);
		
		//Check user object
		$this->assertEquals($user_obj->get_user_id(), $user_id);
		$this->assertEquals($user_obj->get_email(), $email);
		$this->assertEquals($user_obj->get_handle(), $handle);
		$this->assertEquals($user_obj->get_name_family(), $family);
		$this->assertEquals($user_obj->get_name_given(), $given);
	}
	
	public function testSelect()
	{
		$email = 'some2@somewhere.com';
		$handle = 'username2';
		$password = 'P@ssword2';
		$name_family = 'SomeFamily2';
		$name_given = 'SomeGiven2';
		
		$this->link->query(sprintf("INSERT INTO users (handle, email, pswd_hash, name_given, name_family) VALUES ('%s', '%s', PASSWORD('%s'), '%s', '%s')",
			$this->link->escape_string($handle),
			$this->link->escape_string($email),
			$this->link->escape_string($password),
			$this->link->escape_string($name_given),
			$this->link->escape_string($name_family)
		));
		
		$result = $this->link->query(sprintf("SELECT * FROM users WHERE handle = '%s'",
			$this->link->escape_string($handle)
		));
		
		$user_assoc = $result->fetch_assoc();
		$result->close();
		
		
		$user_obj = User::select($user_assoc['user_id']);
		
		$this->assertEquals($user_obj->get_user_id(), $user_assoc['user_id']);
		$this->assertEquals($user_obj->get_email(), $email);
		$this->assertEquals($user_obj->get_handle(), $handle);
		$this->assertEquals($user_obj->get_name_family(), $name_family);
		$this->assertEquals($user_obj->get_name_given(), $name_given);
	}
}
?>

















