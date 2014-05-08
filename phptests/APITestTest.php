<?php

require_once './apis/APITest.php';
require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class APITestTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $obj;
	public function setup()
	{
		Session::set(null);
		
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
		
		$this->db->add_users(2);
		$this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_unit($this->db->course_ids[0]);
		$this->unit_id = $this->db->course_unit_ids[0];
		
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
		
		
		$this->obj = new APITest(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APITest");
	}
	
	public function test_insert()
	{
		//No handle
		$_POST["unit_id"] = $this->db->course_unit_ids[0];
		$this->obj->insert();
		$this->assertTrue(Session::get()->has_error());
		
		//handle
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->insert();
		$this->assertFalse(Session::get()->has_error());
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($test_assoc["result"]);
		
		$entry_ids = $this->db->add_dictionary_entries(5);
		$this->db->add_list($this->db->user_ids[0], $entry_ids);
		$list_ids = implode(",", $this->db->list_ids);
		$_POST["list_ids"] = $list_ids;
		$this->obj->insert();
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertFalse(Session::get()->has_error());
		$this->assertNotNull($test_assoc);
		
		$_POST["list_ids"] = "27,40,95,103,".$list_ids;
		$this->obj->insert();
		$this->assertFalse(Session::get()->has_error());
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($test_assoc);
		
		$_POST["list_ids"] = $list_ids;
		$entries = $this->db->add_dictionary_entries(3);
		$entry_ids = implode(",", $entries);
		$_POST["entry_ids"] = $entry_ids;
		$_POST["name"] = "test-name";
		$_POST["open"] = time();
		$_POST["close"] = time() + (7 * 24 * 60 * 60);
		$this->obj->insert();
		$this->assertFalse(Session::get()->has_error());
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($test_assoc);
		$this->assertArrayHasKey("testId", $test_assoc["result"]);
		$this->assertArrayHasKey("name", $test_assoc["result"]);
		
		$_POST["entry_ids"] = "122,255,968";
		$this->obj->insert();
		$this->assertFalse(Session::get()->has_error());
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($test_assoc);
	}
	
	public function test_select()
	{
		//No handle
		$test_id = $this->db->add_unit_test($this->unit_id);
		$_GET["test_id"] = $test_id;
		$this->obj->select();
		$this->assertTrue(Session::get()->has_error());
		
		//wrong handle
		$_SESSION["handle"] = $this->db->handles[1];
		$this->obj->select();
		$this->assertTrue(Session::get()->has_error());
		
		//handle
		$_SESSION["handle"] = $this->db->handles[0];
		$this->db->add_unit_test_entries($test_id, $this->db->user_ids[0], 5);
		$this->obj->select();
		$this->assertFalse(Session::get()->has_error());
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($test_assoc["result"]);
		$this->assertEquals($test_assoc["result"]["testId"], $test_id);
	}
	
	public function test_delete()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$_POST["test_id"] = $test_id;
		$this->obj->delete();
		$this->assertFalse(Session::get()->has_error());
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertEquals($test_assoc["result"]["testId"], $test_id);
		
		$test_id = $this->db->add_unit_test($this->unit_id);
		$_POST["test_id"] = $test_id;
		$this->db->add_unit_test_entries($test_id, $this->db->user_ids[0], 5);
		$this->obj->delete();
		$this->assertFalse(Session::get()->has_error());
		$test_obj = Test::select_by_id($test_id);
		$this->assertNull($test_obj);
	}
	
	public function test_unexecute()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$student_id = $this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[1]);
		$sitting_id = $this->db->add_unit_test_sittings($test_id, $student_id);
		
		$_POST["test_id"] = $test_id;
		$this->obj->unexecute();
		$this->assertFalse(Session::get()->has_error());
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($test_assoc["result"]);
		$this->assertEquals($test_assoc["result"]["testId"], $test_id);
		
		// Get student to unexecute the test sitting.
		$_SESSION["handle"] = $this->db->handles[1];
		$this->obj->unexecute();
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_update()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$student_id = $this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[1]);
		
		$new_name = "updated-name";
		$new_open = time() + (1 * 24 * 60 * 60);
		$new_close = time() + (2 * 24 * 60 * 60);
		$new_timeframe = new Timeframe($new_open, $new_close);
		$new_timer = 60 * 60;
		$new_msg = "updated-mdg";
		$_POST["test_id"] = $test_id; $_POST["name"] = $new_name;
		$_POST["open"] = $new_open; $_POST["close"] = $new_close;
		$_POST["timer"] = $new_timer; $_POST["message"] = $new_msg;
		$_POST["disclosed"] = 1;
		$this->obj->update();
		$test_assoc = Session::get()->get_result_assoc();
		$this->assertFalse(Session::get()->has_error());
		$this->assertNotNull($test_assoc["result"]);
		$updated_test = Test::select_by_id($test_id);
		$this->assertEquals($updated_test->get_test_id(), $test_id);
		$this->assertEquals($updated_test->get_name(), $new_name);
		$this->assertEquals($updated_test->get_message(), $new_msg);
		$this->assertEquals($updated_test->get_timer(), $new_timer);
		$this->assertEquals($updated_test->get_timeframe()->get_open(), $new_open);
		$this->assertEquals($updated_test->get_timeframe()->get_close(), $new_close);
		
		$new_close = time() + (3 * 24 * 60 * 60);
		unset($_POST["open"]);
		$_POST["close"] = $new_close;
		$this->obj->update();
		$updated_test = Test::select_by_id($test_id);
		$this->assertEquals($updated_test->get_timeframe()->get_close(), $new_close);
		$new_open = time() + (2 * 24 * 60 * 60);
		unset($_POST["close"]);
		$_POST["open"] = $new_open;
		$this->obj->update();
		$updated_test = Test::select_by_id($test_id);
		$this->assertEquals($updated_test->get_timeframe()->get_open(), $new_open);
	}
	
	public function _update_executed()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$student_id = $this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[1]);
		$this->db->add_unit_test_entries($test_id, $this->db->user_ids[0], 5);
		
		$_POST["test_id"] = $test_id;
		$_POST["test_entry_id"] = $this->db->test_entry_ids[0];
		$_POST["contents"] = "testing";
		$this->obj->execute();
		$new_timer = 60 * 60;
		$_POST["timer"] = $new_timer;
		$this->obj->update();
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_sittings_empty()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$_GET["test_id"] = $test_id;
		
		$this->obj->sittings();
		$result = Session::get()->get_result_assoc();
		$this->assertTrue(empty($result["result"]));
	}
	
	public function test_sittings()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$_GET["test_id"] = $test_id;
		$student_id = $this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[1]);
		$sitting_id = $this->db->add_unit_test_sittings($test_id, $student_id);
		$this->obj->sittings();
		
		$result = Session::get()->get_result_assoc();
		$result_assoc = $result["result"][0];
		$this->assertEquals($sitting_id, $result_assoc["sittingId"]);
	}
	
	public function test_entries()
	{
		$_SESSION["handle"] = $this->db->handles[1];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$test = Test::select_by_id($test_id);
		$_GET["test_id"] = $test_id;
		$this->obj->entries();
		$this->assertFalse($test->session_user_can_administer());
		$result = Session::get()->get_result_assoc();
		$result_assoc = $result["result"];
		$this->assertNull($result_assoc);
		
		$_SESSION["handle"] = $test->get_owner()->get_handle();
		
		$this->db->add_unit_test_entries($test_id, $this->db->user_ids[0], 5);
		$this->obj->entries();
		$this->assertTrue($test->session_user_can_administer());
		$result = Session::get()->get_result_assoc();
		$result_assoc = $result["result"];
		$this->assertCount(5, $result_assoc);
		$this->assertArrayHasKey("entryId", $result_assoc[0]);
		$this->assertArrayHasKey("languages", $result_assoc[0]);
		
		$_SESSION["handle"] = $this->db->handles[1];
		$this->obj->entries();
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_entries_add_remove()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$entry_ids = $this->db->add_dictionary_entries(5);
		$_POST["test_id"] = $test_id;
		$_POST["entry_ids"] = implode(",", $entry_ids);
		//	Test::errors_unset();
		$this->obj->entries_add();
		$this->assertFalse(Session::get()->has_error());
		$result = Session::get()->get_result_assoc();
		$result_assoc = $result["result"];
		//	print_r($result);
		//	print_r(Test::errors_unset());
		$this->assertCount(5, $result_assoc);
		$this->assertContains($result_assoc[0]["entryId"], $entry_ids);
		
		$new_entry_ids = $this->db->add_dictionary_entries(5);
		$this->db->add_list($this->db->user_ids[0], $new_entry_ids);
		$_POST["list_ids"] = implode(",", $this->db->list_ids);
		$this->obj->entries_add();
		$this->assertFalse(Session::get()->has_error());
		$result = Session::get()->get_result_assoc();
		$result_assoc = $result["result"];
		$this->assertCount(10, $result_assoc);
		$this->assertContains($result_assoc[6]["entryId"], $new_entry_ids);
		
		$_POST["entry_ids"] = "847830555";
		$_POST["list_ids"] = "-2";
		$this->obj->entries_add();
		$this->assertEquals($result, Session::get()->get_result_assoc());
		
		$remove_entry_ids = array($entry_ids[0], $new_entry_ids[0]);
		$_POST["entry_ids"] = implode(",", $remove_entry_ids);
		$this->obj->entries_remove();
		$this->assertFalse(Session::get()->has_error());
		$result = Session::get()->get_result_assoc();
		$result_assoc = $result["result"];
		$this->assertCount(8, $result_assoc);
		$current_entry_ids = array();
		foreach($result_assoc as $assoc)	array_push($current_entry_ids, $assoc["entryId"]);
		$this->assertNotContains($entry_ids[0], $current_entry_ids);
		$this->assertNotContains($new_entry_ids[0], $current_entry_ids);
		$this->assertContains($new_entry_ids[1], $current_entry_ids);
		$this->assertContains($entry_ids[1], $current_entry_ids);
		
		$_POST["entry_ids"] = "847830555";
		$this->obj->entries_remove();
		$this->assertFalse(Session::get()->has_error());
		$result = Session::get()->get_result_assoc();
		$result_assoc = $result["result"];
		$this->assertCount(8, $result_assoc);
	}
	
	public function test_entries_randomize()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$entry_ids = $this->db->add_dictionary_entries(5);
		$_POST["test_id"] = $test_id;
		$_POST["entry_ids"] = implode(",", $entry_ids);
		$this->obj->entries_add();
		$this->assertFalse(Session::get()->has_error());
		
		$this->obj->entries_randomize();
		$this->assertTrue(Session::get()->has_error());
		
		$_POST["renumber"] = 1;
		$this->obj->entries_randomize();
		$this->assertFalse(Session::get()->has_error());
		
		$_POST["remode"] = 1;
		$this->obj->entries_randomize();
		$this->assertFalse(Session::get()->has_error());
		
		$_POST["renumber"] = 0;
		$this->obj->entries_randomize();
		$this->assertFalse(Session::get()->has_error());
	}
	
	public function test_entry_update_and_options()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$entry_ids = $this->db->add_dictionary_entries(5);
		$_POST["test_id"] = $test_id;
		$_POST["entry_id"] = $entry_ids[0];
		$_POST["entry_ids"] = implode(",", $entry_ids);
		$this->obj->entries_add();
		$this->assertFalse(Session::get()->has_error());
		$_POST["mode"] = 3; //update test.php to expose entry_mode
		$this->obj->entry_update();
		$this->assertFalse(Session::get()->has_error());
		$result = Session::get()->get_result_assoc();
		$result_assoc = $result["result"];
		$old_pattern_count = $result_assoc["patternsCount"];
		
		Session::get()->unset_result_assoc();
		$_POST["options"] = "test-option,test-option1";
		$this->obj->entry_update();
		$result = Session::get()->get_result_assoc();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = $result["result"];
		$this->assertEquals($old_pattern_count + 2, $result_assoc["patternsCount"]);
		
		$_POST["number"] = 3; //update test.php to expose entry_number
		$this->obj->entry_update();
		$this->assertFalse(Session::get()->has_error());
		
		$_GET["test_id"] = $test_id;
		$_GET["entry_id"] = $entry_ids[0];
		$this->obj->entry_options();
		$result = Session::get()->get_result_assoc();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = $result["result"];
		$this->assertCount(2, $result_assoc);
		
		$_GET["entry_id"] = "847830555";
		$this->obj->entry_options();
		$this->assertTrue(Session::get()->has_error());
	}
	
	public function test_entry_options_add_remove()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$entry_ids = $this->db->add_dictionary_entries(5);
		$_POST["test_id"] = $test_id;
		$_POST["entry_ids"] = implode(",", $entry_ids);
		$this->obj->entries_add();
		$_POST["entry_id"] = $entry_ids[0];
		$_POST["contents"] = "test-content";
		$this->obj->entry_options_add();
		$result = Session::get()->get_result_assoc();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = $result["result"];
		
		$this->assertEquals($result_assoc["testId"], $test_id);
		$this->assertEquals($result_assoc["entryId"], $entry_ids[0]);
		$this->assertEquals($result_assoc["contents"], "test-content");
		
		$pattern_id = $result_assoc["patternId"];
		$this->obj->entry_options_remove();
		$result = Session::get()->get_result_assoc();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = $result["result"];
		$this->assertEquals($result_assoc["patternId"], $pattern_id);
	}
	
	public function _test_entry_pattern_update()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		$test_id = $this->db->add_unit_test($this->unit_id);
		$entry_ids = $this->db->add_dictionary_entries(5);
		$_POST["test_id"] = $test_id;
		$_POST["entry_id"] = $entry_ids[0];
		$_POST["entry_ids"] = implode(",", $entry_ids);
		$_POST["contents"] = "test-content";
		$this->obj->entries_add();
		
		$_POST["score"] = 10;
		$_POST["message"] = "test-message";
		$this->obj->entry_pattern_update();
		$result_assoc = $result["result"];
		$this->assertFalse(Session::get()->has_error());
		
	}
}



?>
