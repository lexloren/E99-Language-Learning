<?php

//Tests class list
require_once './backend/classes/list.php';
require_once './phptests/TestDB.php';

class ListTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
	}
	
	public function test_insert()
	{
		
	}
	
	public function test_select()
	{
		
	}
	
	public function test_entries_add()
	{
		
	}
	
	public function test_entries_remove()
	{
		
	}
	
	public function test_delete()
	{
		
	}
	
	public function test_get_entries()
	{
		
	}
}









?>