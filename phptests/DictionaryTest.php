<?php

//Tests class list
require_once './backend/classes/dictionary.php';
require_once './phptests/TestDB.php';

class DictionaryTest extends PHPUnit_Framework_TestCase
{
	private $db;

	public function setup()
	{
		$this->db = TestDB::create();
		$this->assertNotNull($this->db, "failed to create test database");
	}
	
	public function test_look_up()
	{
		$codes = Array();
		array_push($codes, TestDB::$lang_code_0);
		array_push($codes, TestDB::$lang_code_1);
		$result = Dictionary::look_up(TestDB::$word_0, $codes);
		$this->assertNotNull($result);
	
		$this->assertCount(1, $result);
		$entry = $result[0];
		$this->assertNotNull($entry);
		
		$this->assertNull($entry->get_owner());
		
		$words = $entry->get_words();
		$this->assertNotNull($words);
		$this->assertCount(2, $words);
		
		$this->assertEquals($words[TestDB::$lang_code_0], TestDB::$word_0);
	}
	
	public function test_select_entry()
	{
		$entry = Dictionary::select_entry(TestDB::$entry_id);
		$this->assertNotNull($entry);
		
		$this->assertNull($entry->get_owner());
		
		$words = $entry->get_words();
		$this->assertNotNull($words);
		$this->assertCount(2, $words);
		
		$this->assertEquals($words[TestDB::$lang_code_0], TestDB::$word_0);
	}
	
	
	public function test_get_lang_code()
	{
		$lang_code_0 = Dictionary::get_lang_code(TestDB::$lang_id_0);
		$this->assertEquals($lang_code_0, TestDB::$lang_code_0);
		
		$lan_code_1 = Dictionary::get_lang_code(TestDB::$lang_id_1);
		$this->assertNotNull($lan_code_1, TestDB::$lang_code_1);
	}
	
	public function test_get_lang_id()
	{
		$lang_id_0 = Dictionary::get_lang_id(TestDB::$lang_code_0);
		$this->assertEquals($lang_id_0, TestDB::$lang_id_0);
		
		$lang_id_1 = Dictionary::get_lang_id(TestDB::$lang_code_1);
		$this->assertNotNull($lang_id_1, TestDB::$lang_id_1);
	}
}









?>