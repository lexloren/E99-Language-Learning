<?php

//Tests class list
require_once './backend/classes/course.php';
require_once './phptests/TestDB.php';

class CourseTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		Session::set(null);
		$this->db = TestDB::create();
		$this->db->add_users(1);
		$this->db->add_course($this->db->user_ids[0]);
		$this->assertNotNull($this->db, "failed to create test database");
	}
	
	public function test_insert()
	{
		Session::get()->set_user(null);
		$course = Course::insert(TestDB::$lang_code_1, TestDB::$lang_code_0, 'New Course1');
		$this->assertNull($course);
		
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		
		$course = Course::insert(TestDB::$lang_code_1, TestDB::$lang_code_0, 'New Course1');
		$this->assertNotNull($course);
		$this->assertEquals($course->get_user_id(), $this->db->user_ids[0]);
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
	
		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertNotNull($course);
		$this->assertEquals($course->get_user_id(), $this->db->user_ids[0]);
		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
		$this->assertEquals($course->get_lang_id_0(), TestDB::$lang_id_0);
		$this->assertEquals($course->get_lang_id_1(), TestDB::$lang_id_1);
	}
	
	public function test_delete()
	{
		Session::get()->set_user(null);
		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertNotNull($course);
		$ret = $course->delete();
		$this->assertNull($ret);
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		$ret = $course->delete();
		$this->assertNotNull($ret);

		//$this->assertNull(Course::select_by_id($this->db->course_ids[0]));		
		Course::unregister_all();
		$this->assertNull(Course::select_by_id($this->db->course_ids[0]));
	}
	
	public function test_get_lists()
	{
		$course = Course::select_by_id($this->db->course_ids[0]);
		$lists = $course->get_lists();
		$this->assertNotNull($lists);
		$this->assertCount(1, $lists);
		$this->assertEquals($lists[0]->get_list_id(), $this->db->list_ids[0]);
	}
	
	public function test_set_course_name()
	{
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);

		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertNotNull($course);

		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
		$course->set_course_name("new name of old course");
		/*
		$this->assertEquals($course->get_course_name(), "new name of old course");
		
		Course::unregister_all();
		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertEquals($course->get_course_name(), "new name of old course");
		$course->set_course_name($this->db->course_names[0]);
		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
		*/
		Course::unregister_all();
	}
	
	public function test_set_course_name_no_user()
	{
		Session::get()->set_user(null);

		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertNotNull($course);

		//session user not set, it should fail
		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
		$ret = $course->set_course_name("new name of old course");

		$this->assertNull($ret);
		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
		
		Course::unregister_all();
		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
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