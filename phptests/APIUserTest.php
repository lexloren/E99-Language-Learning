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

		$session_mock = $this->getMock('Session', array('session_start', 'session_end', 'session_regenerate_id'));

		// Configure the stub.
		$session_mock->expects($this->any())
					 ->method('session_start')
					 ->will($this->returnValue($this->db->sessions[0]));
					 
		$session_mock->expects($this->any())
					 ->method('session_regenerate_id')
					 ->will($this->returnValue($this->db->sessions[0]));

		$this->assertNotNull($session_mock, "failed to create session mock");
		
		$session_mock->set_allow_email(false);
		Session::set($session_mock);
		$this->assertEquals(Session::get(), $session_mock);
		

		$this->obj = new APIUser(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APIUser");
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
	
	public function test_deuthenticate()
	{
		$this->obj->deauthenticate();
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertFalse(Session::get()->has_error());
		
		$_SESSION["handle"] = $this->db->handles[0];
		Session::get()->reauthenticate();
		$this->assertNull(Session::get()->get_user());
		$this->assertTrue(Session::get()->has_error());
	}
	
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
		$this->assertArrayHasKey('name', $result[0]);
		$this->assertArrayHasKey('owner', $result[0]);
		$this->assertArrayHasKey('isPublic', $result[0]);
		
		$this->assertEquals($result[0]["name"], $this->db->list_names[0]);
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

		//  Removed 'owner' from Entry JSON because it's not used by front end
		//      and incurs heavy queries on the database for no reason
		$this->assertArrayHasKey('entryId', $result[0]);
		$this->assertArrayHasKey('words', $result[0]);
		$this->assertArrayHasKey('pronuncations', $result[0]);

		$result_0_prefix = '11';
		$this->assertEquals($result[0]["words"][TestDB::$lang_code_0], TestDB::$word_0.$result_0_prefix);
		$this->assertEquals($result[0]["words"][TestDB::$lang_code_1], TestDB::$word_1.$result_0_prefix);
		$this->assertEquals($result[0]["pronuncations"][TestDB::$lang_code_1], TestDB::$word_1_pronun.$result_0_prefix);
	}

	public function testPracticeWrongListIds()
	{
		$_SESSION["handle"] = $this->db->handles[0];

		$this->obj->practice();
		$this->assertTrue(Session::get()->has_error());

		$_GET["list_ids"] = '';
                Session::get()->set_result_assoc(null);
		$this->obj->practice();
		$this->assertTrue(Session::get()->has_error());

		$_GET["list_ids"] = -5;
                Session::get()->set_result_assoc(null);
		$this->obj->practice();
		$this->assertTrue(Session::get()->has_error());

		$_GET["list_ids"] = 0;
                Session::get()->set_result_assoc(null);
		$this->obj->practice();
		$this->assertTrue(Session::get()->has_error());

		$_GET["list_ids"] = 'x,y';
                Session::get()->set_result_assoc(null);
		$this->obj->practice();
		$this->assertTrue(Session::get()->has_error());
	}

	public function testPracticeResponse()
	{
		$this->db->add_practice_data($this->db->user_ids[0], 2, 10);
		$_SESSION["handle"] = $this->db->handles[0];

		$_POST["entry_id"] = $this->db->practice_entry_ids[0];
		$grade = Grade::select_by_point(4);
		$_POST["grade_id"] = $grade->get_grade_id();
                Session::get()->set_result_assoc(null);
		$this->obj->practice_response();
		$this->assertFalse(Session::get()->has_error());
		$entry = Session::get()->get_result_assoc();
		$this->assertNotNull($entry["result"]);

		$this->assertNotNull($entry["result"]["words"]);
		$this->assertCount(2, $entry["result"]["words"]);
		$this->assertEquals($entry["result"]["entryId"], $this->db->practice_entry_ids[0]);
	}

	public function testPracticeResponseWrongEntryId()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$_GET["grade_id"] = 5;

		$this->obj->practice_response();
		$this->assertTrue(Session::get()->has_error());

		$_GET["entry_id"] = '0';
		Session::get()->set_result_assoc(null);
		$this->obj->practice_response();
		$this->assertTrue(Session::get()->has_error());

		$_GET["entry_id"] = 'x';
		Session::get()->set_result_assoc(null);
		$this->obj->practice_response();
		$this->assertTrue(Session::get()->has_error());

		$_GET["entry_id"] = -2;
		Session::get()->set_result_assoc(null);
		$this->obj->practice_response();
		$this->assertTrue(Session::get()->has_error());
	}

	public function testPracticeResponseWrongGradeId()
	{
		$this->db->add_practice_data($this->db->user_ids[0], 2, 10);
		$_SESSION["handle"] = $this->db->handles[0];
		$_GET["entry_id"] =  $this->db->practice_entry_ids[0];

		$this->obj->practice_response();
		$this->assertTrue(Session::get()->has_error());

		$_GET["grade_id"] = '0';
		Session::get()->set_result_assoc(null);
		$this->obj->practice_response();
		$this->assertTrue(Session::get()->has_error());

		$_GET["grade_id"] = 'x';
		Session::get()->set_result_assoc(null);
		$this->obj->practice_response();
		$this->assertTrue(Session::get()->has_error());

		$_GET["grade_id"] = -2;
		Session::get()->set_result_assoc(null);
		$this->obj->practice_response();
		$this->assertTrue(Session::get()->has_error());
	}
	
	private function find($query, $should_find)
	{
		$_GET["query"] = $query;
		
		$this->obj->find();
		$this->assertFalse(Session::get()->has_error());

		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];		
		$this->assertNotNull($result);
		
		if ($should_find)
		{
			$this->assertCount(1, $result);
			$result0 = $result[0];
			$this->assertEquals($this->db->handles[0], $result0["handle"]);
		}
		else
			$this->assertCount(0, $result);
	}
	public function testFind()
	{
		$this->find("junk", false);
		$this->find($this->db->handles[0], true);
		$this->find($this->db->emails[0], true);
	}
	
	public function test_select()
	{
		//Session not set
		$this->obj->select();
		$this->assertTrue(Session::get()->has_error());
		
		//Session set
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->select();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		$result = $result_assoc["result"];
		$this->assertEquals($result["userId"], $this->db->user_ids[0]);
	}
	
	public function test_languages()
	{
		//Session not set
		$this->obj->languages();
		$this->assertTrue(Session::get()->has_error());
		
		//Session set
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->languages();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		$result = $result_assoc["result"];
		$this->assertCount(0, $result);
	}
	
	public function testUpdate()
	{
		$_POST["email"] = "newemail1@domain.com";
		$_POST["name_given"] = "NewGiven";
		$_POST["name_family"] = "NewFamily";
		$_POST["email"] = "newemail1@domain.com";
		
		$_POST["langs"] = implode(",", array("en=5", "cn=2"));

		//Session not set
		$this->obj->update();
		$this->assertTrue(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		
		//Session set
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->update();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		
		$this->assertFalse(Session::get()->has_error());
		$this->assertNotNull(Session::get()->get_user());
		$this->assertEquals($_POST["email"], Session::get()->get_user()->get_email());
		
		$result = $result_assoc["result"];
		$this->assertEquals($result["email"], $_POST["email"]);
		$this->assertEquals($result["nameGiven"], $_POST["name_given"]);
		$this->assertEquals($result["nameFamily"], $_POST["name_family"]);
		
		$langYears = $result["languageYears"];
		$this->assertCount(2, $langYears);
		
		$this->assertTrue(array_key_exists("en", $langYears[0]) || array_key_exists("en", $langYears[1]));
		$this->assertTrue(array_key_exists("cn", $langYears[0]) || array_key_exists("cn", $langYears[1]));
		
		$langYearEN = $langYears[0];
		$langYearCN = $langYears[1];

		if (!array_key_exists("en", $langYearEN))
		{
			$langYearEN = $langYears[1];
			$langYearCN = $langYears[0];
		}

		$this->assertEquals($langYearEN["en"], 5);
		$this->assertEquals($langYearCN["cn"], 2);
		
		//print_r($result);
	}
	
	public function test_languages_add()
	{
		//No handle set
		$_POST["langs"] = implode(",", array(TestDB::$lang_code_0, TestDB::$lang_code_1));
		$this->obj->languages_add();
		$this->assertTrue(Session::get()->has_error());

		//handle set, correct langs
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->languages_add();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		$result = $result_assoc["result"];
		
		$langYears = $result["languageYears"];
		$this->assertCount(2, $langYears);
		$this->assertTrue(array_key_exists(TestDB::$lang_code_0, $langYears[0]) || array_key_exists(TestDB::$lang_code_0, $langYears[1]));
		$this->assertTrue(array_key_exists(TestDB::$lang_code_1, $langYears[0]) || array_key_exists(TestDB::$lang_code_1, $langYears[1]));
	}
	
	public function test_languages_remove()
	{
		//No handle set
		$_POST["langs"] = implode(",", array(TestDB::$lang_code_0, TestDB::$lang_code_1));
		$this->obj->languages_remove();
		$this->assertTrue(Session::get()->has_error());

		//handle set
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->languages_remove();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();		
		$this->assertNotNull($result_assoc);
		$result = $result_assoc["result"];
		
		//$langYears = $result["languageYears"];
		//$this->assertCount(1, $langYears);
		//$this->assertTrue(array_key_exists(TestDB::$lang_code_0, $langYears[0]));
	}
	
	public function test_courses()
	{
		$this->obj->courses();
		$this->assertTrue(Session::get()->has_error());

		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->courses();
		$this->assertFalse(Session::get()->has_error());
	}
	
	public function test_student_courses()
	{
		$this->obj->student_courses();
		$this->assertTrue(Session::get()->has_error());

		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->student_courses();
		$this->assertFalse(Session::get()->has_error());
	}

	public function test_instructor_courses()
	{
		$this->obj->instructor_courses();
		$this->assertTrue(Session::get()->has_error());

		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->instructor_courses();
		$this->assertFalse(Session::get()->has_error());
	}
}

?>
