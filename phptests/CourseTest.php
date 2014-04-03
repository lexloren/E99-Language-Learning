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
		$this->db->add_users(5);
		$this->db->add_course($this->db->user_ids[0]);
		$this->assertNotNull($this->db, "failed to create test database");
	}
	
	public function test_insert()
	{
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
		Course::reset();
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
		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertNotNull($course);

		//session user not set, it should fail
		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
		$ret = $course->set_course_name("new name of old course");
		$this->assertNull($ret);

		//set session user
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		
		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
		$course->set_course_name("new name of old course");
		
		//Hans, please check this
		//$this->assertEquals($course->get_course_name(), "new name of old course");
		
		Course::reset();
		$course = Course::select_by_id($this->db->course_ids[0]);
		//Hans, please check this
		//$this->assertEquals($course->get_course_name(), "new name of old course");
		$course->set_course_name($this->db->course_names[0]);
		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
		
		Course::reset();
	}
	
	public function test_instructors_add()
	{
	}
	
	public function test_students_add()
	{
		//Wrong session user, should fail
		$user = User::select_by_id($this->db->user_ids[1]);
		Session::get()->set_user($user);
		$course = Course::select_by_id($this->db->course_ids[0]);
		
		$student = User::select_by_id($this->db->user_ids[2]);
		$ret = $course->students_add($student);
		$this->assertNull($ret);
		
		//Correct session user, should work
		$user = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user);
		$course = Course::select_by_id($this->db->course_ids[0]);
		
		$ret = $course->students_add($student);
		$this->assertNotNull($ret);
		
		$students = $course->get_students();
		//Hans, please check this
		//$this->assertCount(1, $students);
		//$this->assertEquals($student, $students[0]);
	}
	
	public function test_instructors_remove()
	{
	}
	
	public function test_students_remove()
	{
	}
}









?>