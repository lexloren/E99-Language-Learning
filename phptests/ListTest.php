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
	}

	public function test_insert()
	{
		Session::get()->set_user(null);
		$list_name = "test_list";
		$list = EntryList::insert($list_name);
		$this->assertNull($list);
		
		$user_obj = User::select_by_id(TestDB::$user_id);
		Session::get()->set_user($user_obj);
		$list = EntryList::insert("test_list");
		$this->assertNotNull($list);
		$this->assertEquals($list->get_list_name(), $list_name);
	}
	
	public function test_select()
	{
		$list = EntryList::select_by_id(TestDB::$list_id);
		$this->assertNotNull($list);
		$this->assertEquals($list->get_list_id(), TestDB::$list_id);
		$this->assertFalse($list->is_public());
	}
	
	public function test_list_name()
	{
	
		$list = EntryList::select_by_id(TestDB::$list_id);
		$this->assertNotNull($list);
		$this->assertEquals($list->get_list_name(), TestDB::$list_name);
		$ret = $list->set_list_name("list_new_name");
		$this->assertNull($ret);
		$this->assertEquals($list->get_list_name(), TestDB::$list_name);
		
		$user_obj = User::select_by_id(TestDB::$user_id);
		Session::get()->set_user($user_obj);
		$list = EntryList::select_by_id(TestDB::$list_id);
		
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
		$list = EntryList::select_by_id(TestDB::$list_id);
		$this->assertNotNull($list);
		$ret = $list->delete();
		$this->assertNull($ret);
		
		Session::get()->set_user(User::select_by_id(TestDB::$user_id));
		$ret = $list->delete();

		//$this->assertNotNull($ret);
		//$this->assertNull(EntryList::select_by_id(TestDB::$list_id));
		//EntryList::unregister_all();
		//$this->assertNull(EntryList::select_by_id(TestDB::$list_id));
	}
	
	public function test_get_entries()
	{
		
	}
}









?>