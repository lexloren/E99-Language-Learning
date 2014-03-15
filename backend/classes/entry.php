<?php

require_once "./backend/connect.php";
require_once "./backend/support.php";

class Entry
{
	public $entry_id = null;
	public $words = null; //Associative array from language codes to word values
	public $pronunciations = null;
	public $user_id = null;
	
	public function __construct($entry_id, $lang_code_0, $lang_code_1,
		$word_0, $word_1, $pronunciation, $user_id = null)
	{
		$this->entry_id = intval($entry_id, 10);
		$this->words = array (
			$lang_code_0 => $word_0,
			$lang_code_1 => $word_1
		);
		$this->pronunciations = array (
			$lang_code_1 => $pronunciation
		);
		$this->user_id = $user_id;
	}
	
	public static function from_mysql_result_assoc($result)
	{
		if (!$result) return null;
		
		return new Entry($result["entry_id"],
			$result["lang_code_0"],
			$result["lang_code_1"],
			$result["word_0"],
			$result["word_1"],
			$result["word_1_pronun"],
			$result["user_id"]
		);
	}
	
	public function assoc_for_json()
	{
		return array(
			"entryId" => $this->entry_id,
			"words" => $this->words,
			"pronuncations" => $this->pronunciations,
			"userId" => $this->user_id
		);
	}
}

?>