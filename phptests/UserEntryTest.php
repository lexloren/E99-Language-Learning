<?php

//Tests class list
require_once './backend/classes/entry.php';
require_once './phptests/TestDB.php';

class UserEntryTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		Session::set(null);
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
		$this->db->add_dictionary_entries(10);
		$this->db->add_users(1);
		$this->db->add_list($this->db->user_ids[0], $this->db->entry_ids);
	}
	
	public function test_select_by_user_entry_id()
    {
		$user_entry = UserEntry::select_by_user_entry_id($this->db->user_entry_ids[0]);
		$this->assertNotNull($user_entry);
		$this->assertEquals($this->db->user_entry_ids[0], $user_entry->get_user_entry_id());
		$this->assertEquals($this->db->user_ids[0], $user_entry->get_user_id());

		$prons = $user_entry->pronunciations();
		$this->assertNotNull($prons);
		$this->assertCount(1, $prons);
		$this->assertEquals($this->db->word_1_pronuns[0], $prons[TestDB::$lang_code_1]);

		$annotations = $user_entry->annotations();
		$this->assertNotNull($annotations);
		$this->assertCount(1, $annotations);		


		$langs = $user_entry->languages();
		$this->assertCount(2, $langs);
		$this->assertEquals(TestDB::$lang_code_0, $langs[0]);
		$this->assertEquals(TestDB::$lang_code_1, $langs[1]);		
	}
	
	public function test_set_words()
	{
		$user_entry = UserEntry::select_by_user_entry_id($this->db->user_entry_ids[0]);
		$this->assertNotNull($user_entry);
		$word_0 = $user_entry->get_word_0();
		$word_1 = $user_entry->get_word_1();
		$this->assertNull($user_entry->set_word_0("new word 0"));
		$this->assertNull($user_entry->set_word_1("new word 1"));

		$this->assertEquals($word_0, $user_entry->get_word_0());
		$this->assertEquals($word_1, $user_entry->get_word_1());
		
		Session::get()->set_user(User::select_by_id($this->db->user_ids[0]));			
		
		$ret = $user_entry->set_word_0("new word 0");
		$this->assertNotNull($ret);
		$ret = $user_entry->set_word_1("new word 1");
		$this->assertNotNull($ret);

		$this->assertEquals("new word 0", $user_entry->get_word_0());
		$this->assertEquals("new word 1", $user_entry->get_word_1());
	}
	
	public function test_set_pronunciations()
	{
		
	}
}









?>
