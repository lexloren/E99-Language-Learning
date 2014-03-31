<?php

//Tests class User
require_once './backend/classes/session.php';
require_once './backend/classes/user.php';
require_once './phptests/TestDB.php';

class UserTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		Session::set(null);
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
	}
	
	public function tearDown()
	{
		//if (isset($this->db))
		//	$this->db->close();
	}

	public function testInsert()
	{
		$email = "some1@somewhere.com";
		$handle = "username1";
		$password = "P@ssword1";
		$family = "SomeFamily1";
		$given = "SomeGiven1";

		$user_obj = User::insert($email, $handle, $password, $family, $given);
		Session::get()->set_user($user_obj);
				
		//Check database
		$link = $this->db->link;
		$result = $link->query(sprintf("SELECT * FROM users WHERE handle LIKE '%s'", $link->escape_string($handle)));
		$this->assertEquals($result->num_rows, 1);
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
		$this->db->add_users(5);
		$link = $this->db->link;
		$result = $link->query(sprintf("SELECT * FROM users WHERE handle LIKE '%s'",
			$link->escape_string($this->db->handles[0])
		));
		
		$user_assoc = $result->fetch_assoc();
		$result->close();
		
		$user_obj = User::select_by_id($user_assoc["user_id"]);
		Session::get()->set_user($user_obj);
		
		$this->assertEquals($user_obj->get_user_id(), $user_assoc["user_id"]);
		$this->assertEquals($user_obj->get_email(), $this->db->emails[0]);
		$this->assertEquals($user_obj->get_handle(), $this->db->handles[0]);
		$this->assertEquals($user_obj->get_name_family(), $this->db->names_family[0]);
		$this->assertEquals($user_obj->get_name_given(), $this->db->names_given[0]);
	}
	
	public function test_find()
	{
		$this->db->add_users(5);
		$result = User::find("");
		$this->assertCount(0, $result);
		$result = User::find($this->db->emails[0]);
		$this->assertNotNull($result);
		$this->assertCount(1, $result);
		$this->assertEquals($result[0]->get_user_id(), $this->db->user_ids[0]);
		$result = User::find($this->db->handles[0]);
		$this->assertNotNull($result);
		$this->assertCount(1, $result);
		$this->assertEquals($result[0]->get_user_id(), $this->db->user_ids[0]);
	}
}
?>

















