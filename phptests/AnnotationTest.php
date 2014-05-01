<?php

//Tests class list
require_once './backend/classes/course.php';
require_once './phptests/TestDB.php';

class AnnotationTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		Session::set(null);
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");

		$this->db->add_users(3);
		$this->db->add_dictionary_entries(2);
		
		$this->annotation_contents = "annotation contents";
	}
	
	public function test_insert_select()
	{
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$this->assertNotNull($entry);
		
		$user0 = User::select_by_id($this->db->user_ids[0]);
		$this->assertNotNull($user0);
		$user_entry = $entry->copy_for_user($user0);
		
		//  No session user
		$this->assertNull(Annotation::insert($user_entry->get_user_entry_id(), $this->annotation_contents));
		
		//  Session user set
		Session::get()->set_user($user0);
		$annotation = Annotation::insert($user_entry->get_user_entry_id(), $this->annotation_contents);
		$this->assertNotNull($annotation);
		$this->assertEquals($this->annotation_contents, $annotation->get_contents());
		$this->assertEquals($user_entry->get_user_entry_id(), $annotation->get_user_entry_id());
		$this->assertEquals($user_entry, $annotation->get_user_entry());
		$this->assertTrue(in_array($annotation, $user_entry->annotations()));
		$this->assertEquals(Session::get()->get_user(), $annotation->get_owner());
		$this->assertEquals($user_entry->get_owner(), $annotation->get_owner());
		
		$annotation_selected = Annotation::select_by_id($annotation->get_annotation_id());
		$this->assertNotNull($annotation_selected);
		$this->assertEquals($annotation, $annotation_selected);
	}
	
	public function test_delete()
	{
		$entry = Entry::select_by_id($this->db->entry_ids[0]);
		$this->assertNotNull($entry);
		
		$user0 = User::select_by_id($this->db->user_ids[0]);
		$this->assertNotNull($user0);
		$user_entry = $entry->copy_for_user($user0);
		
		Session::get()->set_user($user0);
		$annotation = Annotation::insert($user_entry->get_user_entry_id(), $this->annotation_contents);
		$this->assertNotNull($annotation);
		
		//  Wrong session user
		$user1 = User::select_by_id($this->db->user_ids[1]);
		Session::get()->set_user($user1);
		$this->assertNotEquals(Session::get()->get_user(), $annotation->get_owner());
		$this->assertNull($annotation->delete());
		
		//  Right session user
		Session::get()->set_user($user0);
		$this->assertEquals(1, $user_entry->annotations_count());
		$this->assertCount(1, $user_entry->annotations());
		$this->assertEquals($annotation, $annotation->delete());
		$this->assertEquals(0, $user_entry->annotations_count());
		$this->assertCount(0, $user_entry->annotations());
	}
}

?>