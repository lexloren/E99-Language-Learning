<?php

require_once './apis/APIUser.php';
require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class APIUserTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $obj;
	public function setup()
	{
		Session::set(null);

		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");

		$this->db->add_users(1);
		$this->db->add_grades();

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
		$_POST["email"] = "someone@somewhere.com";
		$_POST["handle"] = "usernameone";
		$_POST["password"] = "P@ssword1";
		$this->obj->register();
		
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		
		$this->assertNotNull($result);

		$this->assertEquals($result["handle"], $_POST["handle"]);
		$this->assertEquals($result["email"], $_POST["email"]);
	}
	
	public function testRegisterNoEmail()
	{
		$_POST["handle"] = "usernameone";
		$_POST["password"] = "P@ssword1";
		$this->obj->register();
		
		$this->assertTrue(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$error = $result_assoc["errorTitle"];		
		$this->assertNotNull($error);
	}
	
	public function testRegisterNoHandle()
	{
		$_POST["email"] = "someone@somewhere.com";
		$_POST["password"] = "P@ssword1";
		$this->obj->register();
		
		$this->assertTrue(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$error = $result_assoc["errorTitle"];		
		$this->assertNotNull($error);
	}

	public function testRegisterNoPassword()
	{
		$_POST["handle"] = "usernameone";
		$_POST["email"] = "someone@somewhere.com";

		$this->obj->register();
		
		$this->assertTrue(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$error = $result_assoc["errorTitle"];		
		$this->assertNotNull($error);
	}

	public function testAuthenticate()
	{
		$_POST["handle"] = $this->db->handles[0];
		$_POST["password"] = $this->db->passwords[0];
		$this->obj->authenticate();
		
		$this->assertFalse(Session::get()->has_error());
		$this->assertNotNull(Session::get()->get_user());
		$this->assertEquals(Session::get()->get_user()->get_handle(), $this->db->handles[0]);
	}

	public function testAuthenticateNoHandle()
	{
		$_POST["password"] = $this->db->passwords[0];
		$this->obj->authenticate();
		
		$this->assertTrue(Session::get()->has_error());
		$this->assertNull(Session::get()->get_user());
	}

	public function testAuthenticateNoPassword()
	{
		$_POST["handle"] = $this->db->handles[0];
		$this->obj->authenticate();
		
		$this->assertTrue(Session::get()->has_error());
		$this->assertNull(Session::get()->get_user());
	}
	
	/*public function test_deuthenticate()
	{
		$this->obj->deauthenticate();
	}*/
	
	public function test_lists()
	{
		$this->db->add_dictionary_entries(1);
		$this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);

		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->lists();
		
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		
		$this->assertNotNull($result);
		$this->assertCount(1, $result);
		
		$this->assertArrayHasKey('listId', $result[0]);
		$this->assertArrayHasKey('listName', $result[0]);
		$this->assertArrayHasKey('owner', $result[0]);
		$this->assertArrayHasKey('isPublic', $result[0]);
		
		$this->assertEquals($result[0]["listName"], $this->db->list_names[0]);
		$this->assertEquals($result[0]["owner"]["handle"], $this->db->handles[0]);
	}

	public function testPractice()
	{
		$this->db->add_practice_data($this->db->user_ids[0], 2, 10);
		$_SESSION["handle"] = $this->db->handles[0];
		$_GET["list_ids"] = join(', ', $this->db->practice_list_ids);
		$this->obj->practice();

		$this->assertFalse(Session::get()->has_error());

		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);

		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(count($this->db->practice_entry_ids), $result);

		$this->assertArrayHasKey('entryId', $result[0]);
		$this->assertArrayHasKey('owner', $result[0]);
		$this->assertArrayHasKey('words', $result[0]);
		$this->assertArrayHasKey('pronuncations', $result[0]);

		$result_0_prefix = '11';
		$this->assertEquals($result[0]["owner"]["handle"], $this->db->handles[0]);
		$this->assertEquals($result[0]["words"][TestDB::$lang_code_0], TestDB::$word_0.$result_0_prefix);
		$this->assertEquals($result[0]["words"][TestDB::$lang_code_1], TestDB::$word_1.$result_0_prefix);
		$this->assertEquals($result[0]["pronuncations"][TestDB::$lang_code_1], TestDB::$word_1_pronun.$result_0_prefix);
	}

	public function testPracticeWrongListIds()
	{
		$_SESSION["handle"] = $this->db->handles[0];

		$_GET["list_ids"] = '';
		$this->obj->practice();
		$this->assertTrue(Session::get()->has_error());

		$_GET["list_ids"] = -5;
		$this->obj->practice();
		$this->assertTrue(Session::get()->has_error());

		$_GET["list_ids"] = 0;
		$this->obj->practice();
		$this->assertTrue(Session::get()->has_error());

		$_GET["list_ids"] = 'x,y';
		$this->obj->practice();
		$this->assertTrue(Session::get()->has_error());
	}

        public function testPracticeResponse()
        {
		$this->db->add_practice_data($this->db->user_ids[0], 2, 10);
                $_SESSION["handle"] = $this->db->handles[0];

                $_GET["entry_id"] = $this->db->practice_entry_ids[0];
                $_GET["grade_id"] = 5;
                $this->obj->practice_response();
                $this->assertFalse(Session::get()->has_error());
        }

        public function testPracticeResponseWrongInputs()
        {
                $_SESSION["handle"] = $this->db->handles[0];
                $_GET["grade_id"] = 5;

                $_GET["entry_id"] = '-1';
                $this->obj->practice_response();
                $this->assertTrue(Session::get()->has_error());

                $_GET["entry_id"] = '0';
                Session::get()->set_error_assoc(null, null);
                $this->obj->practice_response();
                $this->assertTrue(Session::get()->has_error());

                $_GET["entry_id"] = '2x';
                $this->obj->practice_response();
                $this->assertTrue(Session::get()->has_error());

                $_GET["entry_id"] = 2;
                $_GET["grade_id"] = -5;
                $this->obj->practice_response();
                $this->assertTrue(Session::get()->has_error());
        }

}



?>
