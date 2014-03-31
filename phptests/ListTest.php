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

		$this->db->add_dictionary_entries(1);
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
		
		/*
		$ret = $list->set_list_name("list_new_name");
		$this->assertNotNull($ret);
		$this->assertEquals($list->get_list_name(), "list_new_name");
		*/
	}
	
	public function test_entries_add()
	{
		
	}
	
	public function test_entries_remove()
	{
		
	}
	
	public function test_delete()
	{
		$list = EntryList::select_by_id($this->db->list_ids[0]);
		$this->assertNotNull($list);
		$ret = $list->delete();
		$this->assertNull($ret);
		
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));
		$ret = $list->delete();

		//$this->assertNotNull($ret);
		//$this->assertNull(EntryList::select_by_id($this->db->list_id[0]));
		//EntryList::unregister_all();
		//$this->assertNull(EntryList::select_by_id($this->db->list_ids[0]));
	}
	
	public function test_get_entries()
	{
		
	}
}









?>
