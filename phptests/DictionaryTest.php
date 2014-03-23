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
		//print_r($result);
	}
	
	/*
	public function test_select_entry()
	{
	}
	
	public function test_get_lang_code()
	{
	}
	
	public function test_get_lang_id()
	{
	}*/
}









?>