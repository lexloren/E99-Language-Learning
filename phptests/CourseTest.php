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
		$this->assertNotNull($this->db, "failed to create test database");

		$this->db->add_users(5);
		$this->db->add_course($this->db->user_ids[0]);
		$list_id = $this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$course_unit_id = $this->db->add_course_unit($this->db->course_ids[0]);
		$this->db->add_unit_list($course_unit_id, $list_id);
	}
	
	public function test_course_insert()
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
		$this->assertEquals($course->get_lang_code_0(), TestDB::$lang_code_0);
		$this->assertEquals($course->get_lang_code_1(), TestDB::$lang_code_1);
		$this->assertEquals($course->get_message(), $this->db->course_messages[0]);
		$this->assertFalse($course->get_public());
	}
	
	public function test_course_find()
        {
                $user_obj = User::select_by_id($this->db->user_ids[0]);
                Session::get()->set_user($user_obj);

		$this->assertNotNull(Course::insert(TestDB::$lang_code_1, TestDB::$lang_code_0, 'New Course1'));
		$this->assertNotNull(Course::insert(TestDB::$lang_code_1, TestDB::$lang_code_0, 'New Course2'));
		$this->assertNotNull(Course::insert(TestDB::$lang_code_1, TestDB::$lang_code_0, 'NoMatch Course1'));

		//  To update later... (---Hans)
		/*
		$courses = Course::find('New', array ());
		$this->assertEquals(count($courses), 2);
		$courses = Course::find('New', array(TestDB::$lang_code_1));
		$this->assertEquals(count($courses), 2);
		$courses = Course::find('New', array(TestDB::$lang_code_1,TestDB::$lang_code_0));
		$this->assertEquals(count($courses), 2);
		$courses = Course::find('', array(TestDB::$lang_code_1));
		$this->assertEquals(count($courses), 4);
		$courses = Course::find('New', array('xx'));
		$this->assertEquals(count($courses), 0);

		$user_obj = User::select_by_id($this->db->user_ids[1]);
                Session::get()->set_user($user_obj);
		$courses = Course::find('', array(TestDB::$lang_code_1));
                $this->assertEquals(count($courses), 0);
		*/
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

		$this->assertNull(Course::select_by_id($this->db->course_ids[0]));		
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
		
		$this->assertEquals($course->get_course_name(), "new name of old course");
		
		Course::reset();

		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertEquals($course->get_course_name(), "new name of old course");
		$course->set_course_name($this->db->course_names[0]);
		$this->assertEquals($course->get_course_name(), $this->db->course_names[0]);
		
		Course::reset();
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
		
		$this->assertCount(0, $course->get_students());
		$ret = $course->students_add($student);
		$this->assertNotNull($ret);
		
		$students = $course->get_students();
		$this->assertCount(1, $students);
		$this->assertEquals($student, $students[0]);
	}
	
	public function test_students_remove()
	{
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[1]);
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[2]);
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[3]);
		$this->db->add_course_student($this->db->course_ids[0], $this->db->user_ids[4]);
		$course = Course::select_by_id($this->db->course_ids[0]);
		$students = $course->get_students();
		$this->assertCount(4, $students);
		
		//Session user is not set
		$course->students_remove($students[0]);
		$students = $course->get_students();
		$this->assertCount(4, $students);

		//Session user set
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$students = $course->get_students();
		$student1 = $students[1];
		$student2 = $students[2];
		$course->students_remove($students[0]);
		$course->students_remove($students[3]);
		$students = $course->get_students();
		$this->assertCount(2, $students);
		$this->assertTrue(in_array($student1, $students));
		$this->assertTrue(in_array($student2, $students));
	}
	
	public function test_instructors_add()
	{
		//Wrong session user, should fail
		$user = User::select_by_id($this->db->user_ids[1]);
		Session::get()->set_user($user);
		$course = Course::select_by_id($this->db->course_ids[0]);
		
		$instructor = User::select_by_id($this->db->user_ids[2]);
		$ret = $course->instructors_add($instructor);
		$this->assertNull($ret);
		
		//Correct session user, should work
		$user = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user);
		$course = Course::select_by_id($this->db->course_ids[0]);
		
		$this->assertCount(0, $course->get_instructors());
		$ret = $course->instructors_add($instructor);
		$this->assertNotNull($ret);
		
		$instructors = $course->get_instructors();
		$this->assertCount(1, $instructors);
		$this->assertEquals($instructor, $instructors[0]);
	}
	
	public function test_instructors_remove()
	{
		$this->db->add_course_instructor($this->db->course_ids[0], $this->db->user_ids[1]);
		$this->db->add_course_instructor($this->db->course_ids[0], $this->db->user_ids[2]);
		$this->db->add_course_instructor($this->db->course_ids[0], $this->db->user_ids[3]);
		$this->db->add_course_instructor($this->db->course_ids[0], $this->db->user_ids[4]);
		$course = Course::select_by_id($this->db->course_ids[0]);
		$instructors = $course->get_instructors();
		$this->assertCount(4, $instructors);
		
		//Session user is not set
		$course->instructors_remove($instructors[0]);
		$instructors = $course->get_instructors();
		$this->assertCount(4, $instructors);

		//Session user set
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$instructors = $course->get_instructors();
		$instructor1 = $instructors[1];
		$instructor2 = $instructors[2];
		$course->instructors_remove($instructors[0]);
		$course->instructors_remove($instructors[3]);
		$instructors = $course->get_instructors();
		$this->assertCount(2, $instructors);
		$this->assertTrue(in_array($instructor1, $instructors));
		$this->assertTrue(in_array($instructor2, $instructors));
	}
	
	public function test_get_public()
	{
		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertFalse($course->get_public());

		//Session user not set
		$course->set_public(true);
		$this->assertFalse($course->get_public());

		//Session user set
		$user = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user);
		$course->set_public(true);
		$this->assertTrue($course->get_public());
		$course->set_public(false);
		$this->assertFalse($course->get_public());
	}
	
	public function test_time_frame()
	{
		$course = Course::select_by_id($this->db->course_ids[0]);
		$time_frame = $course->get_timeframe();
		$this->assertNull($time_frame);
		$week = (7 * 24 * 60 * 60);
		$time_now = time();
		$prevWeek = $time_now - $week;
		$nextWeek = $time_now + $week;
		$time_frame_to_set = new Timeframe($prevWeek, $nextWeek);
		
		//Session user not set
		$course->set_timeframe($time_frame_to_set);
		$time_frame = $course->get_timeframe();
		$this->assertNull($time_frame);

		//Session user set
		$user = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user);
		$course->set_timeframe($time_frame_to_set);
		$time_frame = $course->get_timeframe();
		$this->assertNotNull($time_frame);
		$this->assertEquals($time_now - $week, $time_frame->get_open());
		$this->assertEquals($time_now + $week, $time_frame->get_close());
		$course->set_open($time_now);
		$time_frame = $course->get_timeframe();
		$this->assertEquals($time_now, $time_frame->get_open());
		$this->assertEquals($time_now + $week, $time_frame->get_close());
		$course->set_close($time_now + $week * 2);
		$time_frame = $course->get_timeframe();
		$this->assertEquals($time_now, $time_frame->get_open());
		$this->assertEquals($time_now + $week * 2, $time_frame->get_close());
	}
	
	public function	test_session_user_can_read()
	{
		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertFalse($course->session_user_can_read());
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$this->assertTrue($course->session_user_can_read());
		Session::get()->set_user(User::select_by_id($this->db->user_ids[1]));
		$this->assertFalse($course->session_user_can_read());
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$ret = $course->students_add(User::select_by_id($this->db->user_ids[1]));
		Session::get()->set_user(User::select_by_id($this->db->user_ids[1]));
		$this->assertTrue($course->session_user_can_read());
	}

	public function test_course_test()
	{
		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertCount(0, $course->get_tests());
	}
	
	public function test_set_message()
	{
		$course = Course::select_by_id($this->db->course_ids[0]);
		$this->assertNotNull($course->get_message());

		$message = "new course message";
		
		//Session user not set, should fail
		$ret = $course->set_message($message);
		$this->assertNull($ret);
		$this->assertEquals($this->db->course_messages[0], $course->get_message());

		//Session user set, should work
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$ret = $course->set_message($message);
		$this->assertNotNull($ret);
		$this->assertEquals($message, $course->get_message());
	}
}









?>
