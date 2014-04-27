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
		
		$langs = $entry->get_languages();
		$this->assertCount(2, $langs);
		$this->assertEquals(TestDB::$lang_code_0, $langs[0]);
		$this->assertEquals(TestDB::$lang_code_1, $langs[1]);
	}

	public function test_get_annotations()
	{
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$this->assertNotNull($entry);
		
		Session::get()->set_user(null);
		$annotations = $entry->get_annotations();
		$this->assertCount(0, $annotations);
		
		$this->db->add_users(1);
		$user_obj = User::select_by_id($this->db->user_ids[0]);
		Session::get()->set_user($user_obj);	
		$annotations = $entry->get_annotations();
		$this->assertCount(0, $annotations);

		
		$this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
		$annotations = $entry->get_annotations();
		$this->assertNotNull($annotations);
		$this->assertCount(1, $annotations);
		
		$annotation = $annotations[0];
		$this->assertEquals($annotation->get_annotation_id(), $this->db->annotation_ids[0]);
		$this->assertEquals($annotation->get_user_entry_id(), $this->db->user_entry_ids[0]);
		
		$ret = $entry->annotations_remove($annotation);
		
		$this->assertNotNull($ret);
		$annotations = $entry->get_annotations();
		$this->assertNotNull($annotations);
		$this->assertCount(0, $annotations);
	}
	
	/*
	//  DEPRECATED
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
		$this->assertNotNull($ret);
	}
	
	//  DEPRECATED
	public function test_annotations_remove()
	{
		//  Already tested in test_get_annotations()
	}
	*/
	
	public function test_copy_for_session_user()
	{
		//  Not necessary, but helpful
	}
	
	public function test_update_repetition_details()
	{
		$this->db->add_users(2);
		$this->db->add_practice_data($this->db->user_ids[0], 2, 10);
		$entry = Entry::select_by_id($this->db->practice_entry_ids[0]);
		$this->assertNotNull($entry);

		$user_obj = User::select_by_id($this->db->user_ids[1]);
        Session::get()->set_user($user_obj);
		$result = $entry->update_repetition_details(4);
		
		$this->assertNotNull($result);
		$this->assertEquals($result->get_entry_id(), $entry->get_entry_id());
		$this->assertEquals($result->get_words(), $entry->get_words());
		$this->assertEquals($result->get_interval(), 1);
		$this->assertEquals($result->get_efactor(), 2.50);
	}
	
	public function test_get_pronunciations()
	{
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$prons = $entry->get_pronunciations();
		$this->assertNotNull($prons);
		$this->assertCount(1, $prons);
		$this->assertEquals($this->db->word_1_pronuns[0], $prons[TestDB::$lang_code_1]);		
	}
	
	public function test_copy_for_user()
	{
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$this->AssertNull($entry->get_owner());
		
		$this->db->add_users(5);
		$user = User::select_by_id($this->db->user_ids[3]);
		$user_entry = $entry->copy_for_user($user);
		$this->assertNotNull($user_entry);
		$this->AssertEquals($user, $user_entry->get_owner());
		$this->AssertEquals($this->db->user_ids[3], $user_entry->get_user_id());
    }
	
	public function test_session_user_can_write()
	{
		
	}
}









?>
