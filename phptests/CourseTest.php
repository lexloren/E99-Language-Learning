<?php

//Tests class list
require_once './backend/classes/course.php';
require_once './phptests/TestDB.php';

class CourseTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
	}
	
	public function test_insert()
	{
		Session::get()->set_user(null);
		$course = Course::insert(TestDB::$lang_code_1, TestDB::$lang_code_0, 'New Course1');
		$this->assertNull($course);
		
		$user_obj = User::select_by_id(TestDB::$user_id);
		Session::get()->set_user($user_obj);
		
		$course = Course::insert(TestDB::$lang_code_1, TestDB::$lang_code_0, 'New Course1');
		$this->assertNotNull($course);
		$this->assertEquals($course->get_user_id(), TestDB::$user_id);
		$this->assertEquals($course->get_course_name(), 'New Course1');
		$this->assertEquals($course->get_lang_id_0(), TestDB::$lang_id_1);
		$this->assertEquals($course->get_lang_id_1(), TestDB::$lang_id_0);
		
		$instructors = $course->get_instructors();
		$this->assertCount(1, $instructors);
		$this->assertTrue($course->session_user_is_instructor());
		$this->assertFalse($course->session_user_is_student());
	}
	
	public function test_select()
	{
		$course = Course::select_by_id(0);
		$this->assertNull($course);

		$course = Course::select_by_id(TestDB::$course_id);
		$this->assertNotNull($course);
		$this->assertEquals($course->get_user_id(), TestDB::$user_id);
		$this->assertEquals($course->get_course_name(), TestDB::$course_name);
		$this->assertEquals($course->get_lang_id_0(), TestDB::$lang_id_0);
		$this->assertEquals($course->get_lang_id_1(), TestDB::$lang_id_1);
	}
	
	public function test_delete()
	{
		//TODO: make it better
		$course = Course::select_by_id(TestDB::$course_id);
		$this->assertNotNull($course);
		//$course->delete();
	}
	
	
	public function test_instructors_add()
	{
	}
	
	public function test_students_add()
	{
	}
	
	public function test_instructors_remove()
	{
	}
	
	public function test_students_remove()
	{
	}
}









?>