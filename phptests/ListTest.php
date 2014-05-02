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
		$name = "test_list";
		$list = EntryList::insert($name);
		$this->assertNull($list);
		
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		$list = EntryList::insert("test_list");
		$this->assertNotNull($list);
		$this->assertEquals($list->get_list_name(), $name);
	}
	
	public function test_select()
	{
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$this->assertNotNull($list);
		$this->assertEquals($list->get_list_id(), $this->db->list_ids[0]);
		$this->assertFalse($list->get_public());
	}
	
	public function test_find_by_entry_ids()
	{
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		
		$entry_ids1 = $this->db->add_dictionary_entries(7);
		$entry_ids2 = $this->db->add_dictionary_entries(7);
		$entry_ids3 = $this->db->add_dictionary_entries(7);
		
		$list1_id = $this->db->add_list($this->db->user_ids[0], $entry_ids1);
		$list2_id = $this->db->add_list($this->db->user_ids[0], $entry_ids2);
		$list3_id = $this->db->add_list($this->db->user_ids[0], $entry_ids3);
				
		$entry_ids_to_find = array();
		array_push($entry_ids_to_find, $entry_ids1[0]);
		array_push($entry_ids_to_find, $entry_ids1[3]);		
		array_push($entry_ids_to_find, $entry_ids3[2]);
		
		$lists = EntryList::find_by_entry_ids($entry_ids_to_find);
		$this->assertNotNull($lists);
		
		$this->assertCount(2, $lists);
		
		$list1 = $lists[$list1_id];
		$list2 = $lists[$list3_id];

		$this->assertNotNull($list1);
		$this->assertNotNull($list2);
	}
	
	public function test_find_by_user_ids()
	{
		$this->db->add_list($this->db->user_ids[1], $this->db->entry_ids);
		$this->db->add_list($this->db->user_ids[2], $this->db->entry_ids);
		$this->db->add_list($this->db->user_ids[3], $this->db->entry_ids);
		
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$user_ids = array();
		array_push($user_ids, $this->db->user_ids[0]);
		array_push($user_ids, $this->db->user_ids[3]);
		
		
		$lists = EntryList::find_by_user_ids($user_ids);
		$this->assertNotNull($lists);
		
		$this->assertCount(1, $lists);
		$list1 = $lists[$this->db->list_ids[0]];
		$this->assertNotNull($list1);
	}
	
	public function test_find_by_user_query()
	{
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
		
		$entries = $list->entries();
		$this->assertCount(12, $entries);
	}
	
	public function test_entries_remove()
	{
		//No session user set
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$entries = $list->entries();
		$ret = $list->entries_remove($entries[0]);
		$this->assertNull($ret);

		//Session user set
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$entries = $list->entries();
		$this->assertCount(7, $entries);

		$ret = $list->entries_remove($entries[0]);
		$this->assertNotNull($ret);
		$ret = $list->entries_remove($entries[2]);
		$this->assertNotNull($ret);
		$ret = $list->entries_remove($entries[4]);
		$this->assertNotNull($ret);

		$entries = $list->entries();
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
	
	public function test_entries()
	{
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$this->assertNotNull($list);
		$entries = $list->entries();
		$this->assertCount(7, $entries);
	}
	
	public function test_copy_for_session_user()
	{
		$user0 = User::select_by_id($this->db->user_ids[0]);
		$user1 = User::select_by_id($this->db->user_ids[1]);

		//Try to copy a private list of user0 for user1; should fail
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$this->assertEquals($user0, $list->get_owner());
		$copied_list = $list->copy_for_user($user1);
		$this->assertNull($copied_list);
		
		//Create a course, add user1 as student
		$course_id = $this->db->add_course($user0->get_user_id());
		$course_unit_id = $this->db->add_course_unit($course_id);
		$list_id = $this->db->add_list($user0->get_user_id(), $this->db->entry_ids);
		$this->db->add_unit_list($course_unit_id, $list_id);
		$this->assertTrue(in_array(EntryList::select_by_id($list_id), Unit::select_by_id($course_unit_id)->lists()));
		
		$course = Course::select_by_id($course_id);
		Session::get()->set_user($course->get_owner());
		$course->students_add($user1);
		$courses = $user1->courses_studied();
		$this->assertCount(1, $courses);
		
		//Copy course's list
		$lists = $course->lists();
		$this->assertCount(1, $lists);
		$course_list = $lists[0];
		
		//  Arunabha, the session user can't copy a list for another user
		//      We have to set the session user to the user
		//      who is performing the copy and will receive the copied list
		Session::get()->set_user($user1);
		$this->assertTrue($course->session_user_is_student());
		$this->assertTrue($course_list->session_user_can_read());
		$entries = $course_list->entries();
		$this->assertCount(7, $entries);

		//  Copies the list by virtue of its readability in the course
		$copied_list = $course_list->copy_for_session_user();
		$this->assertNotNull($copied_list);
		$this->assertEquals($user1, $copied_list->get_owner());
		$entries = $copied_list->entries();
		$this->assertCount(7, $entries);
	}
}









?>
