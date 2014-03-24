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
	
	public function testInsert()
	{
		
	}
	
	public function testSelect()
	{
		
	}
	
	public function testadd_entry()
	{
	}
	
	public function testremove_entry()
	{
	}
	
	public function testdelete()
	{
	}
	
	public function testget_entries()
	{
	}
}









?>