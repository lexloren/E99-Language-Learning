<?php

require_once "./backend/connect.php";
require_once "./backend/support.php";

class Entry
{
	private $entry_id = null;
	private $words = null; //Associative array from language codes to word values
	private $pronunciations = null;
	private $user_id = null;
	
	private function __construct($entry_id, $lang_code_0, $lang_code_1,
		$word_0, $word_1, $pronunciation = null, $user_id = null)
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
	
	public static function select($entry_id)
	{
		return Dictionary::select_entry($entry_id);
	}
	
	public static function from_mysql_result_assoc($result)
	{
		if (!$result) return null;
		
		return new Entry($result["entry_id"],
			$result["lang_code_0"],
			$result["lang_code_1"],
			$result["word_0"],
			$result["word_1"],
			!!$result["word_1_pronun"] && strlen($result["word_1_pronun"]) > 0 ? $result["word_1_pronun"] : null,
			$result["user_id"]
		);
	}
	
	public function copy_for_session_user()
	{
		if (!Session::$user) return null;
		
		if ($this->user_id === Session::$user->user_id) return $this;
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO user_entries (user_id, entry_id) VALUES (%d, %d)"
			Session::$user->user_id,
			$this->entry_id
		));
		
		//  JUST A STUB RIGHT NOW!
		//      We should implement user-customization of entries, but later;
		//      for now, this method just makes sure that the user has a copy of the entry.
		return $this;
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