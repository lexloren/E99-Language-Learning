<?php

require_once './apis/APIList.php';
require_once './phptests/TestDB.php';
require_once './backend/classes/session.php';

class APIListTest extends PHPUnit_Framework_TestCase
{
	private $db;
	private $obj;
	public function setup()
	{
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");

		$this->obj = new APIList(null, $this->db->link);
		$this->assertNotNull($this->obj, "Null APIList");
	}
	
	public function test_insert()
	{
		
	}
	
	public function test_delete()
	{
	}
	
	public function test_entries()
	{
	}
	
	public function test_entries_add()
	{
	}
	
	public function test_entries_remove()
	{
	}
	
}
?>