<?php

//Tests class list
require_once './backend/classes/entry.php';
require_once './phptests/TestDB.php';

class EntryTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
	}
	
	public static function test_select()
	{
	}
	
	public function test_add_annotation()
	{
	}
	
	public function test_remove_annotation()
	{
	}
	
	public function test_copy_for_session_user()
	{
	}
	
	public function test_update_repetition_details()
	{
	}
	
	public function test_get_annotations()
	{
	}
	
	public function test_session_user_can_write()
	{
	}
}









?>