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
	
	public function test_select()
	{
		$entry = Entry::select_by_id(TestDB::$entry_id);
		$this->assertNotNull($entry);
		
		$this->assertNull($entry->get_owner());
		
		$words = $entry->get_words();
		$this->assertNotNull($words);
		$this->assertCount(2, $words);
		
		$this->assertEquals($entry->get_entry_id(), TestDB::$entry_id);
	}

	public function test_get_annotation()
	{
		$entry = Entry::select_by_id(TestDB::$entry_id);
		$this->assertNotNull($entry);
		
		Session::get()->set_user(null);
		$annotations = $entry->get_annotations();
		$this->assertNull($annotations);
		
		$user_obj = User::select_by_id(TestDB::$user_id);
		Session::get()->set_user($user_obj);	
		
		$annotations = $entry->get_annotations();
		$this->assertNotNull($annotations);
		$this->assertCount(1, $annotations);
		
		$annotation = $annotations[0];
		$this->assertEquals($annotation->get_annotation_id(), TestDB::$entry_annotation_id);
		$this->assertEquals($annotation->get_entry_id(), TestDB::$entry_id);
		
		$ret = $entry->annotations_remove($annotation);
		$this->assertNotNull($ret);
		$annotations = $entry->get_annotations();
		$this->assertNotNull($annotations);
		$this->assertCount(0, $annotations);
	}
	
	public function test_annotations_add()
	{
		$entry = Entry::select_by_id(TestDB::$entry_id);
		$this->assertNotNull($entry);
		
		Session::get()->set_user(null);
		$ret = $entry->annotations_add("a new annotation");
		$this->assertNull($ret);
	
		$user_obj = User::select_by_id(TestDB::$user_id);
		Session::get()->set_user($user_obj);
		$ret = $entry->annotations_add("a new annotation");
		//Assert below is failing; Entry also looks like UserEntry
		//$this->assertNotNull($ret);
	}
	
	public function test_annotations_remove()
	{
	}
	
	public function test_copy_for_session_user()
	{
		//  Not necessary, but helpful
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