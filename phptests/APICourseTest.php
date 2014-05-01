<?php
	
require_once './apis/APICourse.php';
require_once './phptests/TestDB.php';
require_once './backend/classes.php';

class APICourseTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $obj;
	public function setup()
	{
		Session::set(null);
		
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
		
		$this->db->add_users(5);
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
		$session_mock->set_allow_email(false);
		
		$this->obj = new APICourse(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APICourse");
	}
	
	public function test_insert()
	{
		$_SESSION["handle"] = $this->db->handles[0];
		
		$_POST["lang_known"] = "en";
		$_POST["lang_unknw"] = "xx";
		$_POST["name"] = "new course";
		$this->obj->insert();
		$this->assertTrue(Session::get()->has_error());
		
		$_POST["lang_unknw"] = "cn";
		$this->obj->insert();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertNotNull($result["courseId"]);
		$this->assertEquals($result["name"], $_POST["name"]);
	}
	
	public function test_select()
	{
		$_GET["course_id"] =  $this->db->add_course($this->db->user_ids[0]);
		
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
	}

	public function test_course_find_by_user_ids()
	{
		$this->db->add_users(3);
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		
		$course1_id = $this->db->add_course($this->db->user_ids[0], 1);
		$course2_id = $this->db->add_course($this->db->user_ids[1], 1);
		$course3_id = $this->db->add_course($this->db->user_ids[0], 1);
		
		$_SESSION["handle"] = $this->db->handles[0];
		
		$_GET["user_ids"] = implode(",", array($this->db->user_ids[0]));
		$this->obj->find();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
		$this->assertCount(2, $result);
		
		$result0 = $result[0];
		$result1 = $result[1];
		
		$this->assertTrue($result0["courseId"] != $result1["courseId"]);
		$this->assertTrue($result0["courseId"] == $course1_id || $result0["courseId"] == $course3_id );
		$this->assertTrue($result1["courseId"] == $course1_id || $result1["courseId"] == $course3_id );
	}
	
	public function test_course_find_by_course_ids()
	{
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		
		$course1_id = $this->db->add_course($this->db->user_ids[0]);
		$course2_id = $this->db->add_course($this->db->user_ids[0]);
		$course3_id = $this->db->add_course($this->db->user_ids[0]);
		
		$_SESSION["handle"] = $this->db->handles[0];
		
		$_GET["course_ids"] = implode(",", array($course1_id, $course3_id));
		$this->obj->find();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(2, $result);
		$result0 = $result[0];
		$result1 = $result[1];
		
		$this->assertTrue($result0["courseId"] != $result1["courseId"]);
		$this->assertTrue($result0["courseId"] == $course1_id || $result0["courseId"] == $course3_id );
		$this->assertTrue($result1["courseId"] == $course1_id || $result1["courseId"] == $course3_id );
	}
	
	public function test_course_find_by_langs()
	{
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		
		//two public one private 
		$course1_id = $this->db->add_course($this->db->user_ids[1], 1);
		$course2_id = $this->db->add_course($this->db->user_ids[2], 1);
		$course3_id = $this->db->add_course($this->db->user_ids[3], 0);
		
		$_SESSION["handle"] = $this->db->handles[0];
		
		$_GET["langs"] = implode(",", array(TestDB::$lang_code_0, TestDB::$lang_code_1));

		$this->obj->find();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(2, $result);
	}
	
	public function test_update()
	{
		$_POST["course_id"] =  $this->db->add_course($this->db->user_ids[0]);
		$_SESSION["handle"] = $this->db->handles[0];
		
		$_POST["name"] = "course new name";
		$_POST["message"] = "course new message";
		$_POST["public"] = "1";
		//  My PHP complains that strtotime needs a time zone
		$_POST["open"] = 1397260800;
		$_POST["close"] = 1399852800;
		
		$this->obj->update();
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
		//print_r($result_assoc);
		
		$this->assertEquals($result["name"], $_POST["name"]);
		$this->assertEquals($result["message"], $_POST["message"]);
		$this->assertEquals($result["isPublic"], $_POST["public"]);
		
		$this->assertNotNull($result["timeframe"]);
		$timeframe  = $result["timeframe"];
		$this->assertEquals($timeframe["open"], $_POST["open"]);
		$this->assertEquals($timeframe["close"], $_POST["close"]);
	}
	
	public function test_update_open_time()
	{
		$_POST["course_id"] =  $this->db->add_course($this->db->user_ids[0]);
		$_SESSION["handle"] = $this->db->handles[0];
		
		$_POST["open"] = 1397260800;
		
		//Hans, please fix this
		//$this->obj->update();
	}
	
	public function test_delete()
	{
		$this->db->add_users(1);
		$_POST["course_id"] =  $this->db->add_course($this->db->user_ids[0]);

		//no session user
		$this->obj->delete();
		$this->assertTrue(Session::get()->has_error());
		
		//wrong session user
		$_SESSION["handle"] = $this->db->handles[1];
		$this->obj->delete();
		$this->assertTrue(Session::get()->has_error());

		//correct session user
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->delete();
		$this->assertFalse(Session::get()->has_error());
	}
	
	public function test_lists()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);
		$course_unit_id = $this->db->add_course_unit($course_id);
		$list_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$this->db->add_unit_list($course_unit_id, $list_id);
		
		$_GET["course_id"] = $course_id;
		//no session user
		$this->obj->lists();
		$this->assertTrue(Session::get()->has_error());
		
		//session user
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->lists();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(1, $result);
		$list = $result[0];
		$this->assertEquals($list["listId"], $this->db->list_ids[0]);
	}

	public function test_units()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);
		$course_unit_id = $this->db->add_course_unit($course_id);
		$list_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$this->db->add_unit_list($course_unit_id, $list_id);
		
		$_GET["course_id"] = $course_id;
		
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->units();
		$this->assertFalse(Session::get()->has_error());
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(1, $result);
		$unit = $result[0];
		$this->assertEquals($unit["unitId"], $course_unit_id);
	}
	
	public function test_students()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_student($course_id, $this->db->user_ids[1]);
		
		$_GET["course_id"] = $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->students();
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(1, $result);
		$student = $result[0];
		$this->assertEquals($student["userId"], $this->db->user_ids[1]);
	}
	
	public function test_students_add()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);

		$_POST["course_id"] = $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["user_ids"] = implode(",", array($this->db->user_ids[1], $this->db->user_ids[2]));
		
		$this->obj->students_add();
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(2, $result);
		$student1 = $result[0];
		$student2 = $result[1];
		
		$this->assertTrue($student1["userId"] != $student2["userId"]);
		$this->assertTrue($student1["userId"] == $this->db->user_ids[1] || $student1["userId"] == $this->db->user_ids[1]);
		$this->assertTrue($student2["userId"] == $this->db->user_ids[2] || $student2["userId"] == $this->db->user_ids[2]);
	}

	public function test_students_remove()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_student($course_id, $this->db->user_ids[1]);
		$this->db->add_course_student($course_id, $this->db->user_ids[2]);
		$this->db->add_course_student($course_id, $this->db->user_ids[3]);
		
		$_POST["course_id"] = $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["user_ids"] = implode(",", array ($this->db->user_ids[2], $this->db->user_ids[3]));
		$this->obj->students_remove();
		
		$result_assoc = Session::get()->get_result_assoc();
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(1, $result);
		$student = $result[0];
		$this->assertEquals($student["userId"], $this->db->user_ids[1]);
	}
	
	public function test_instructors()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_instructor($course_id, $this->db->user_ids[1]);
		
		$_GET["course_id"] = $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->instructors();
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(2, $result);

		$instructor1 = $result[0];
		$instructor2 = $result[1];
		
		$this->assertTrue($instructor1["userId"] != $instructor2["userId"]);
		$this->assertTrue($instructor1["userId"] == $this->db->user_ids[0] || $student1["userId"] == $this->db->user_ids[1]);
		$this->assertTrue($instructor2["userId"] == $this->db->user_ids[0] || $instructor2["userId"] == $this->db->user_ids[1]);
	}
	
	public function test_instructors_add()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);

		$_POST["course_id"] = $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["user_ids"] = implode(",", array($this->db->user_ids[2]));
		
		$this->obj->instructors_add();
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(2, $result);

		$instructor1 = $result[0];
		$instructor2 = $result[1];
		
		$this->assertTrue($instructor1["userId"] != $instructor2["userId"]);
		$this->assertTrue($instructor1["userId"] == $this->db->user_ids[0] || $student1["userId"] == $this->db->user_ids[2]);
		$this->assertTrue($instructor2["userId"] == $this->db->user_ids[0] || $instructor2["userId"] == $this->db->user_ids[2]);
	}
	
	public function test_instructors_remove()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_instructor($course_id, $this->db->user_ids[1]);
		$this->db->add_course_instructor($course_id, $this->db->user_ids[2]);
		
		$_POST["course_id"] = $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["user_ids"] = implode(",", array($this->db->user_ids[2]));
		$this->obj->instructors_remove();
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
		//Hans, please check this
		//$this->assertCount(2, $result);

		//$instructor1 = $result[0];
		//$instructor2 = $result[1];
		
		//$this->assertTrue($instructor1["userId"] != $instructor2["userId"]);
		//$this->assertTrue($instructor1["userId"] == $this->db->user_ids[0] || $student1["userId"] == $this->db->user_ids[1]);
		//$this->assertTrue($instructor2["userId"] == $this->db->user_ids[0] || $instructor2["userId"] == $this->db->user_ids[1]);
	}
	
	public function test_researchers()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_researcher($course_id, $this->db->user_ids[1]);
		
		$_GET["course_id"] = $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		$this->obj->researchers();
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(1, $result);

		$researcher = $result[0];
		
		$this->assertTrue($researcher["userId"] == $this->db->user_ids[1]);
	}
	
	public function test_researchers_add()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);

		$_POST["course_id"] = $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["user_ids"] = implode(",", array($this->db->user_ids[1], $this->db->user_ids[2]));
		
		$this->obj->researchers_add();
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(2, $result);
		$researcher1 = $result[0];
		$researcher2 = $result[1];
		
		$this->assertTrue($researcher1["userId"] != $researcher2["userId"]);
		$this->assertTrue($researcher1["userId"] == $this->db->user_ids[1] || $researcher1["userId"] == $this->db->user_ids[1]);
		$this->assertTrue($researcher2["userId"] == $this->db->user_ids[2] || $researcher2["userId"] == $this->db->user_ids[2]);
	}
	
	public function test_researchers_remove()
	{
		$course_id =  $this->db->add_course($this->db->user_ids[0]);
		$this->db->add_course_researcher($course_id, $this->db->user_ids[1]);
		$this->db->add_course_researcher($course_id, $this->db->user_ids[2]);
		$this->db->add_course_researcher($course_id, $this->db->user_ids[3]);
		
		$_POST["course_id"] = $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		$_POST["user_ids"] = implode(",", array($this->db->user_ids[2], $this->db->user_ids[3]));
		$this->obj->researchers_remove();
		
		$result_assoc = Session::get()->get_result_assoc();
		//print_r($result_assoc);
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		$this->assertCount(1, $result);
		$researcher = $result[0];
		$this->assertEquals($researcher["userId"], $this->db->user_ids[1]);
	}
	
	public function test_practice_report()
	{
		$this->db->add_users(3);
		$this->db->add_dictionary_entries(10);
		
		$course_id = $this->db->add_course($this->db->user_ids[1]);
		$course_unit_id = $this->db->add_course_unit($this->db->course_ids[0]);
		$list_id = $this->db->add_list($this->db->user_ids[1], $this->db->entry_ids);
		$this->db->add_unit_list($course_unit_id, $list_id);
		
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[2]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[2], 3);
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[3]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[3], 2);

		$this->db->add_course_researcher($this->db->course_ids[0], $this->db->user_ids[0]);
		
		$_GET["course_id"] =  $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		
		$this->obj->practice_report();
		
		$this->assertFalse(Session::get()->has_error());
		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
		//print $result;
	}

	public function test_student_practice_report()
	{
		$this->db->add_users(3);
		$this->db->add_dictionary_entries(10);
		
		$course_id = $this->db->add_course($this->db->user_ids[1]);
		$course_unit_id = $this->db->add_course_unit($this->db->course_ids[0]);
		$list_id = $this->db->add_list($this->db->user_ids[1], $this->db->entry_ids);
		$this->db->add_unit_list($course_unit_id, $list_id);
		
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[0]);
		$this->db->add_practice_data_for_list($list_id, $this->db->user_ids[0], 3);
		
		$_GET["course_id"] =  $course_id;
		$_SESSION["handle"] = $this->db->handles[0];
		
		$this->obj->student_practice_report();
		$this->assertFalse(Session::get()->has_error());

		$result_assoc = Session::get()->get_result_assoc();
		$this->assertNotNull($result_assoc);
		
		$result = $result_assoc["result"];
		$this->assertNotNull($result);
		
		//print $result;
	}
}
	
?>