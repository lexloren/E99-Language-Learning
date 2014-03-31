<?php

//Tests class list
require_once './backend/classes/entry.php';
require_once './phptests/TestDB.php';

class EntryTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		Session::set(null);
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
		$this->db->add_dictionary_entries(10);
	}
	
	public function test_select()
	{
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$this->assertNotNull($entry);
		
		$this->assertNull($entry->get_owner());
		
		$words = $entry->get_words();
		$this->assertNotNull($words);
		$this->assertCount(2, $words);
		
		$this->assertEquals($entry->get_entry_id(), $this->db->entry_ids[0]);
	}

	public function test_get_annotation()
	{
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$this->assertNotNull($entry);
		
		Session::get()->set_user(null);
		$annotations = $entry->get_annotations();
		$this->assertNull($annotations);
		
		$this->db->add_users(1);
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);	
		
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$annotations = $entry->get_annotations();
		$this->assertNotNull($annotations);
		/*$this->assertCount(1, $annotations);
		
		$annotation = $annotations[0];
		$this->assertEquals($annotation->get_annotation_id(), $this->db->entry_annotation_id[0]);
		$this->assertEquals($annotation->get_entry_id(), $this->db->entry_ids[0]);
		
		$ret = $entry->annotations_remove($annotation);
		$this->assertNotNull($ret);
		$annotations = $entry->get_annotations();
		$this->assertNotNull($annotations);
		$this->assertCount(0, $annotations);*/
	}
	
	public function test_annotations_add()
	{
		$this->db->add_users(1);
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$this->assertNotNull($entry);
		
		Session::get()->set_user(null);
		$new_anno = "a new annotation";
		$ret = $entry->annotations_add($new_anno);
		$this->assertNull($ret);
	
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);
		$ret = $entry->annotations_add($new_anno);
		//Assert below is failing; Entry also looks like UserEntry
		$this->assertNotNull($ret);
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