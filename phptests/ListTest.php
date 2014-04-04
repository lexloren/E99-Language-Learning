<?php

//Tests class list
require_once './backend/classes/list.php';
require_once './backend/classes/user.php';
require_once './phptests/TestDB.php';

class ListTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		Session::set(null);
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");

		$this->db->add_dictionary_entries(7);
		$this->db->add_users(5);
		$this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
	}

	public function test_insert()
	{
		Session::get()->set_user(null);
		$list_name = "test_list";
		$list = EntryList::insert($list_name);
		$this->assertNull($list);
		
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		$list = EntryList::insert("test_list");
		$this->assertNotNull($list);
		$this->assertEquals($list->get_list_name(), $list_name);
	}
	
	public function test_select()
	{
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$this->assertNotNull($list);
		$this->assertEquals($list->get_list_id(), $this->db->list_ids[0]);
		$this->assertFalse($list->is_public());
	}
	
	public function test_list_name()
	{
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$this->assertNotNull($list);
		$this->assertEquals($list->get_list_name(), $this->db->list_names[0]);
		$ret = $list->set_list_name("list_new_name");
		$this->assertNull($ret);
		$this->assertEquals($list->get_list_name(), $this->db->list_names[0]);
		
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		
		$ret = $list->set_list_name("list_new_name");
		$this->assertNotNull($ret);
		$this->assertEquals($list->get_list_name(), "list_new_name");
	}
	
	public function test_entries_add()
	{
		$added = $this->db->add_dictionary_entries(5);
		$list = EntryList::select_by_id($this->db->list_ids[0]);

		$ret = $list->entries_add(null);
		$this->assertNull($ret);
		$entry = Entry::select_by_id($added[0]);
		$ret = $list->entries_add($entry);
		$this->assertNull($ret);

		//Session user set
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
			
		foreach ($added as $entry_id)
		{
			$entry = Entry::select_by_id($entry_id);
			$ret = $list->entries_add($entry);
			$this->assertNotNull($ret);
		}
		
		$entries = $list->get_entries();
		$this->assertCount(12, $entries);
	}
	
	public function test_entries_remove()
	{
		//No session user set
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$entries = $list->get_entries();
		$ret = $list->entries_remove($entries[0]);
		$this->assertNull($ret);

		//Session user set
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$entries = $list->get_entries();
		$this->assertCount(7, $entries);

		$ret = $list->entries_remove($entries[0]);
		$this->assertNotNull($ret);
		$ret = $list->entries_remove($entries[2]);
		$this->assertNotNull($ret);
		$ret = $list->entries_remove($entries[4]);
		$this->assertNotNull($ret);

		$entries = $list->get_entries();
		$this->assertCount(4, $entries);
	}
	
	public function test_delete()
	{
		//No session user set
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$this->assertNotNull($list);
		$ret = $list->delete();
		$this->assertNull($ret);
		
		//Session user set
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$ret = $list->delete();

		$this->assertNotNull($ret);
		$this->assertNull(EntryList::select_by_id($this->db->list_ids[0]));
		EntryList::reset();
		$this->assertNull(EntryList::select_by_id($this->db->list_ids[0]));
	}
	
	public function test_get_entries()
	{
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$this->assertNotNull($list);
		$entries = $list->get_entries();
		$this->assertCount(7, $entries);
	}
	
	public function test_copy_for_user()
	{
		$user0 = User::select_by_id($this->db->user_ids[0]);
		$user1 = User::select_by_id($this->db->user_ids[1]);

		//try to copy a list added by user0 for user1; should fail
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$copied_list = $list->copy_for_user($user1);
		$this->assertNull($copied_list);
		
		//Create a course, add user1 as student
		$course_id = $this->db->add_course($user0->get_user_id());
		$course = Course::select_by_id($course_id);
		Session::get()->set_user($user0);
		$course->students_add($user1);
		$courses = $user1->get_student_courses();
		//Hans please check
		//$this->assertCount(1, $courses);
		$lists = $course->get_lists();

		$copied_list = $lists[0]->copy_for_user($user1);
		$this->assertNull($copied_list);
		
		Session::get()->set_user($user1);

		$copied_list = $lists[0]->copy_for_user($user1);
		
		//Hans please check
		//$this->assertNotNull($copied_list);

		//$entries = $copied_list->get_entries();
		//$this->assertCount(7, $entries);
	}
}









?>
