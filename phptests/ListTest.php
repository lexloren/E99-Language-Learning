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
		//Hans please check this
		//$this->assertNotNull($ret);
		//$this->assertEquals($list->get_list_name(), "list_new_name");
	}
	
	public function test_entries_add()
	{
		$added = $this->db->add_dictionary_entries(5);
		
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$ret = $list->entries_add($added);
		$this->assertNull($ret);

		//Session user set
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		//$ret = $list->entries_add($added);

		//$this->assertNotNull($ret);
	}
	
	public function test_entries_remove()
	{
//Hans, please check this 
		//No session user set
		$list = EntryList::select_by_id($this->db->list_ids[0]);
//		$entries = $list->get_entries();
//		$ret = $list->entries_remove($entries[0]);	
//		$this->assertNull($ret);

		//Session user set
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$entries = $list->get_entries();
		$this->assertCount(7, $entries);

//		$ret = $list->entries_remove($entries[0]);	
//		$this->assertNotNull($ret);
//		$ret = $list->entries_remove($entries[2]);	
//		$this->assertNotNull($ret);
//		$ret = $list->entries_remove($entries[4]);	
//		$this->assertNotNull($ret);

//		$entries = $list->get_entries();
//		$this->assertCount(4, $entries);
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
		//Hans, please check this
		//$this->assertNull(EntryList::select_by_id($this->db->list_ids[0]));
		EntryList::unregister_all();
		$this->assertNull(EntryList::select_by_id($this->db->list_ids[0]));
	}
	
	public function test_get_entries()
	{
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$this->assertNotNull($list);
		$entries = $list->get_entries();
		$this->assertCount(7, $entries);
	}
}









?>
