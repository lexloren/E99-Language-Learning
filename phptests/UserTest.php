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
	
	public function testInsert()
	{
		$email = "some1@somewhere.com";
		$handle = "username1";
		$password = "P@ssword1";
		$family = "SomeFamily1";
		$given = "SomeGiven1";

		$user_obj = User::insert("InvalidEmail", $handle, $password, $family, $given);
		$this->assertNull($user_obj);
		
		$user_obj = User::insert($email, "inh", $password, $family, $given);
		$this->assertNull($user_obj);

		$user_obj = User::insert($email, $handle, "inavlidPass", $family, $given);
		$this->assertNull($user_obj);

		$user_obj = User::insert("InvalidEmail", "invalidhandle", $password, $family, $given);
		$this->assertNull($user_obj);

		$user_obj = User::insert($email, $handle, $password, $family, $given);
		$this->assertNotNull($user_obj);
		
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
		
		//Reuse email/handle
		$user_obj = User::insert("some2@somewhere.com", $handle, $password, $family, $given);
		$this->assertNull($user_obj);
		$user_obj = User::insert($email, "username2", $password, $family, $given);
		$this->assertNull($user_obj);
		$user_obj = User::insert("some2@somewhere.com", "username2", $password, $family, $given);
		$this->assertNotNull($user_obj);
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
	
	public function test_set_email()
	{
		$this->db->add_users(1);
		$user = User::select_by_id($this->db->user_ids[0]);
		$this->assertEquals($this->db->emails[0], $user->get_email());
		$ret = $user->set_email("inavlidemail");
		$this->assertNull($ret);
		$ret = $user->set_email("newemail@domain.com");
		$this->assertNull($ret);
		Session::get()->set_user($user);
		$ret = $user->set_email("newemail@domain.com");
		
		$this->assertNotNull($ret);
		$this->assertEquals("newemail@domain.com", $user->get_email());
		User::reset();
		$user = User::select_by_id($this->db->user_ids[0]);
		$this->assertEquals("newemail@domain.com", $user->get_email());
	}
	
	public function test_set_name_given()
	{
		$this->db->add_users(1);
		$user = User::select_by_id($this->db->user_ids[0]);
		$this->assertEquals($this->db->names_given[0], $user->get_name_given());
		$ret = $user->set_name_given("newName");
		$this->assertNull($ret);
		Session::get()->set_user($user);
		$ret = $user->set_name_given("newName");
		
		$this->assertNotNull($ret);
		$this->assertEquals("newName", $user->get_name_given());
		User::reset();
		$user = User::select_by_id($this->db->user_ids[0]);
		$this->assertEquals("newName", $user->get_name_given());
	}
	
	public function test_set_name_family()
	{
		$this->db->add_users(1);
		$user = User::select_by_id($this->db->user_ids[0]);
		$this->assertEquals($this->db->names_family[0], $user->get_name_family());
		$ret = $user->set_name_family("newFamilyName");
		$this->assertNull($ret);
		Session::get()->set_user($user);
		$ret = $user->set_name_family("newFamilyName");
		
		$this->assertNotNull($ret);
		$this->assertEquals("newFamilyName", $user->get_name_family());
		User::reset();
		$user = User::select_by_id($this->db->user_ids[0]);
		$this->assertEquals("newFamilyName", $user->get_name_family());
	}
	
	public function test_get_name_full()
	{
		$this->db->add_users(1);
		$user = User::select_by_id($this->db->user_ids[0]);
		
		Session::get()->set_user($user);
		$full_name = sprintf("%s %s", $this->db->names_given[0], $this->db->names_family[0]); 
		$this->assertEquals($full_name, $user->get_name_full());
		$full_name = sprintf("%s %s", $this->db->names_family[0], $this->db->names_given[0]); 
		$this->assertEquals($full_name, $user->get_name_full(true));
	}
}
?>