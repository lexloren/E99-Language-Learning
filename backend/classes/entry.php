<?php

require_once "./backend/connection.php";
require_once "./backend/support.php";

class Entry
{
	//  Associative array, keyed by user_id,
	//      of associative arrays of Entry objects keyed by entry_id
	private static $user_entries_by_id = array ();
	
	//  Gets the associative array of Entry objects, keyed by entry_id, for some user_id
	private static function entries_by_id_for_user($user_id)
	{
		if (!in_array(array_keys($user_entries_by_id), $user_id))
		{
			$user_entries_by_id[$user_id] = array ();
		}
		
		return &$user_entries_by_id[$user_id];
	}

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
		
		//  Register $this in the appropriate member of Entry::$user_entries_by_id
		if (!!$this->user_id)
		{
			Entry::entries_by_id_for_user($this->user_id)[$this->entry_id] = $this;
		}
	}
	
	public static function select($entry_id)
	{
		return Dictionary::select_entry($entry_id);
	}
	
	public static function from_mysql_result_assoc($result)
	{
		if (!$result) return null;
		
		return new Entry(
			$result["entry_id"],
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
		if (!Session::get_user()) return null;
		
		if ($this->user_id === Session::get_user()->user_id)
		{
			//  Just make sure that this Entry object has been appropriately registered
			return (Entry::entries_by_id_for_user(Session::get_user())[$this->entry_id] = $this);
		}
		else
		{
			$mysqli = Connection::get_shared_instance();
			
			//  Insert into user_entries the dictionary row corresponding to this Entry object
			//      If such a row already exists in user_entries, ignore the insertion error
			$mysqli->query(sprintf("INSERT IGNORE INTO user_entries (user_id, entry_id, word_0, word_1, word_1_pronun) SELECT %d, entry_id, word_0, word_1, word_1_pronun FROM dictionary WHERE dictionary.entry_id = %d",
				Session::get_user()->user_id,
				$this->entry_id
			));
			
			//  Prepare to convert the columns from user_entries
			$column_conversions = array (
				"user_entries.user_id" => "user_id",
				"user_entries.entry_id" => "entry_id",
				"user_entries.word_0" => "word_0",
				"user_entries.word_1" => "word_1",
				"user_entries.word_1_pronun" = "word_1_pronun"
			);
			$user_columns = array ();
			foreach ($column_conversions as $old => $new)
			{
				array_push($user_columns, "$old AS $new");
			}
			
			//  Construct a joined table with all the columns, including lang_code_0 and lang_code_1
			$dictionary_join = Dictionary::join();
			$join = "user_entries LEFT JOIN ($dictionary_join) USING entry_id";
			
			$result = $mysqli->query(sprintf("SELECT $user_columns, lang_code_0, lang_code_1 FROM $join WHERE entry_id = %d",
				$this->entry_id
			));
			
			if (!$result || !($result_assoc = $result->fetch_assoc())) return null;
			
			return Entry::from_mysql_result_assoc($result_assoc);
		}
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