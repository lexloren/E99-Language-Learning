<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Entry extends DatabaseRow
{
	/***    CLASS/STATIC    ***/
	protected static $error_description = null;

	//  Associative array, keyed by user_id,
	//      of associative arrays of Entry objects keyed by entry_id
	private static $user_entries_by_id = array ();
	
	//  Gets the associative array of Entry objects, keyed by entry_id, for some user_id
	private static function entries_by_id_for_user_id($user_id)
	{
		$user_id = intval($user_id, 10);
		
		if (!in_array($user_id, array_keys(self::$user_entries_by_id)))
		{
			self::$user_entries_by_id[$user_id] = array ();
		}
		
		return self::$user_entries_by_id[$user_id];
	}
	
	public static function select_by_id($entry_id)
	{
		return Dictionary::select_entry($entry_id);
	}
	
	/***    INSTANCE    ***/

	private $entry_id = null;
	public function get_entry_id()
	{
		return $this->entry_id;
	}
	
	private $words = null; //Associative array from language codes to word values
	public function get_words()
	{
		return $this->words;
	}	

	private $pronunciations = null;
	public function get_pronunciations()
	{
		return $this->pronunciations;
	}

	private $annotations;
	public function get_annotations()
	{
		//  Annotations are user-specific, so if we have no Session User, we can't have annotations
		if (!Session::get()->get_user())
		{
			return self::set_error_description("Session user has not reauthenticated.");
		}
		
		if (!isset($this->annotations))
		{
			$this->annotations = array ();
			
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT * FROM user_entry_annotations LEFT JOIN user_entries USING (user_entry_id) WHERE user_id = %d AND entry_id = %d",
				Session::get()->get_user()->get_user_id(),
				$this->get_entry_id()
			));
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				array_push($this->annotations, Annotation::from_mysql_result_assoc($result_assoc));
			}
		}
		
		return $this->annotations;
	}
	
	private $user_entry_id = null;
	public function get_user_entry_id()
	{
		return $this->user_entry_id;
	}
	
	private $user_id = null;
	public function get_user_id()
	{
		return $this->user_id;
	}
	public function get_owner()
	{
		return !!$this->user_id ? User::select_by_id($this->user_id) : null;
	}
	
	private $interval = null;
	public function get_interval()
	{
			return $this->interval;
	}

	private $efactor = null;
	public function get_efactor()
	{
			return $this->efactor;
	}

	private function __construct($entry_id, $lang_code_0, $lang_code_1,
		$word_0, $word_1, $pronunciation = null,
		$user_entry_id = null, $user_id = null,
		$interval = null, $efactor = null)
	{
		$this->entry_id = intval($entry_id, 10);
		$this->words = array (
			$lang_code_0 => $word_0,
			$lang_code_1 => $word_1
		);
		$this->pronunciations = array (
			$lang_code_1 => $pronunciation
		);
		
		if ($user_entry_id !== null && $user_id !== null && $interval !== null && $efactor !== null)
		{
			$this->user_entry_id = intval($user_entry_id, 10);
			$this->user_id = intval($user_id, 10);
			
			$this->interval = intval($interval, 10);
			$this->efactor = floatval($efactor);

			$entries_by_id_for_user_id = self::entries_by_id_for_user_id($this->user_id);
			$entries_by_id_for_user_id[$this->entry_id] = $this;
		}
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"entry_id",
			"lang_code_0",
			"lang_code_1",
			"word_0",
			"word_1",
			"word_1_pronun"
		);
		
		if (!self::assoc_contains_keys($result_assoc, $mysql_columns)) return null;
		
		return new Entry(
			$result_assoc["entry_id"],
			$result_assoc["lang_code_0"],
			$result_assoc["lang_code_1"],
			$result_assoc["word_0"],
			$result_assoc["word_1"],
			!!$result_assoc["word_1_pronun"] && strlen($result_assoc["word_1_pronun"]) > 0 ? $result_assoc["word_1_pronun"] : null,
			array_key_exists("user_entry_id", $result_assoc) ? $result_assoc["user_entry_id"] : null,
			array_key_exists("user_id", $result_assoc) ? $result_assoc["user_id"] : null,
			array_key_exists("interval", $result_assoc) ? $result_assoc["interval"] : null, 
			array_key_exists("efactor", $result_assoc) ? $result_assoc["efactor"] : null
		);
	}
	
	//  Sets both some object property and the corresponding spot in the database
	private function set(&$variable, $column, $value)
	{
		if (!self::update_this(
			$this,
			"user_entries",
			array ($column => $value),
			"user_entry_id",
			$this->get_user_entry_id()
		)) return null;
		
		$variable = $value;
		
		return $this;
	}
	
	//  Sets a new user-specific value for this Entry's word_0
	public function set_word_0($word_0)
	{
		return $this->set($this->word_0, "word_0", $word_0);
	}
	
	//  Sets a new user-specific value for this Entry's word_1
	public function set_word_1($word_1)
	{
		return $this->set($this->word_1, "word_1", $word_1);
	}
	
	//  Sets a new user-specific value for this Entry's word_1_pronun
	public function set_word_1_pronunciation($word_1_pronun)
	{
		return $this->set($this->word_1_pronun, "word_1_pronun", $word_1_pronun);
	}
	
	public function annotations_add($annotation_contents)
	{
		if (!Session::get()->get_user())
		{
			return self::set_error_description("Session user has not reauthenticated.");
		}
		$entry = $this->copy_for_session_user();
		
		$annotation = Annotation::insert($entry->get_entry_id(), $annotation_contents);
		
		if (!!$annotation)
		{
			array_push($entry->get_annotations(), $annotation);
			return $entry;
		}
		
		return self::set_error_description(Annotation::get_error_description());
	}
	
	public function annotations_remove($annotation)
	{
		if ($annotation->get_entry_id() !== $this->get_entry_id())
		{
			return self::set_error_description("Cannot delete annotation.");
		}
		
		$annotation->delete();
		
		unset($this->annotations);
		
		return $this;
	}
	
	//  Returns a copy of $this owned and editable by the Session User
	public function copy_for_session_user()
	{
		if (!Session::get()->get_user())
		{
			return self::set_error_description("Session user has not reauthenticated.");
		}
		
		$entries_by_id_for_user_id = self::entries_by_id_for_user_id(Session::get()->get_user()->get_user_id());
		
		if (isset($entries_by_id_for_user_id[$this->entry_id])) return $entries_by_id_for_user_id[$this->entry_id];
	
		$mysqli = Connection::get_shared_instance();
		
		//  Insert into user_entries the dictionary row corresponding to this Entry object
		//      If such a row already exists in user_entries, ignore the insertion error
		$mysqli->query(sprintf("INSERT IGNORE INTO user_entries (user_id, entry_id, word_0, word_1, word_1_pronun) SELECT %d, entry_id, word_0, word_1, word_1_pronun FROM dictionary WHERE entry_id = %d",
			Session::get()->get_user()->get_user_id(),
			$this->entry_id
		));
		
		$query = sprintf("SELECT * FROM (SELECT entry_id, %s FROM %s WHERE entry_id = %d) AS reference LEFT JOIN user_entries USING (entry_id) WHERE user_id = %d",
			Dictionary::language_code_columns(),
			Dictionary::join(),
			$this->entry_id,
			Session::get()->get_user()->get_user_id()
		);
		
		$result = $mysqli->query($query);
		
		if (!$result || !($result_assoc = $result->fetch_assoc()))
		{
			return self::set_error_description("Entry failed to copy for session user: " .
				(!!$mysqli->error ? $mysqli->error : $query)
			);
		}
		
		return self::from_mysql_result_assoc($result_assoc);
	}
	
	public function update_repetition_details($point)
	{
		if (!$this->session_user_can_write())
		{
			Session::get()->set_error_assoc("Session user cannot edit entry.");
			return;
		}
		$mysqli = Connection::get_shared_instance();

		$_efactor = $this->efactor + (0.1 - (4 - $point) * (0.08 + (4 - $point) * 0.02));
		$new_efactor = min(max($_efactor, 1.3), 2.5);
		$iteration_result = $mysqli->query(sprintf(
			"SELECT COUNT(*) AS row_count FROM user_entry_results WHERE user_id = %d ".
			"AND entry_id = %d",
			$this->user_id, $this->entry_id)
		);
		$iteration_assoc = $iteration_result->fetch_assoc();
		$iteration_count = intval($iteration_assoc["row_count"], 10);
		if ($iteration_count == 1)
			$new_interval = 1;
		else if ($iteration_count == 2)
			$new_interval = 6;
		else
			$new_interval = round($this->interval * $new_efactor);

		if(!$mysqli->query(sprintf(
			"UPDATE user_entries SET `interval` = %d, efactor = %f ".
			"WHERE user_id = %d AND entry_id = %d",
			$new_interval, $new_efactor,
			$this->user_id, $this->entry_id)))
		{
			Session::get()->set_error_assoc("Failed to update interval details: " . $mysqli->error);
			return;
		}
		
		$this->interval = $new_interval;
		$this->efactor = $new_efactor;
		return true;
	}

	public function assoc_for_json()
	{
		$privacy = !!$this->get_owner() && !$this->session_user_is_owner();
		
		$entry = !$privacy ? $this : Dictionary::select_entry($this->entry_id);
		
		return array (
			"entryId" => $entry->entry_id,
			"owner" => !!$this->get_owner() ? $this->get_owner()->assoc_for_json() : null,
			"words" => $entry->words,
			"pronuncations" => $entry->pronunciations
		);
	}
}

class UserEntry extends Entry
{
	
}

?>
