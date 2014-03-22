<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Entry extends DatabaseRow
{
	/***    CLASS/STATIC    ***/

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
	
	public static function select($entry_id)
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
	private $pronunciations = null;
	private $annotations = null;
	public function get_annotations()
	{
		//  Annotations are user-specific, so if we have no Session User, we can't have annotations
		if (!Session::get()->get_user())
		{
			return Entry::set_error_description("Session user has not reauthenticated.");
		}
		
		if (!$this->annotations)
		{
			$this->annotations = array ();
			
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query(sprintf("SELECT * FROM user_entry_annotations WHERE user_id = %d AND entry_id = %d",
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
	
	private $user_id = null;
	public function get_user_id()
	{
		return $this->user_id;
	}
	
	public function get_owner()
	{
		return !!$this->user_id ? User::select($this->user_id) : null;
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
		$word_0, $word_1, $pronunciation = null, $interval = null,
		$efactor = null, $user_id = null)
	{
		$this->entry_id = intval($entry_id, 10);
		$this->words = array (
			$lang_code_0 => $word_0,
			$lang_code_1 => $word_1
		);
		$this->pronunciations = array (
			$lang_code_1 => $pronunciation
		);
		$this->interval = intval($interval, 10);
		$this->efactor = floatval($efactor);
		$this->user_id = $user_id;
		
		//  Register $this in the appropriate member of Entry::$user_entries_by_id
		if (!!$this->user_id)
		{
			$entries_by_id_for_user_id = Entry::entries_by_id_for_user_id($this->user_id);
			$entries_by_id_for_user_id[$this->entry_id] = $this;
		}
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		if (!$result_assoc)
		{
			return Entry::set_error_description("Invalid result_assoc.");
		}
		
		return new Entry(
			$result_assoc["entry_id"],
			$result_assoc["lang_code_0"],
			$result_assoc["lang_code_1"],
			$result_assoc["word_0"],
			$result_assoc["word_1"],
			!!$result_assoc["word_1_pronun"] && strlen($result_assoc["word_1_pronun"]) > 0 ? $result_assoc["word_1_pronun"] : null,
			$result_assoc["interval"],
			$result_assoc["efactor"],
			$result_assoc["user_id"]
		);
	}
	
	public function session_user_can_write()
	{
		return !!Session::get()->get_user() && (Session::get()->get_user()->get_user_id() === $this->user_id);
	}
	
	//  Sets both some object property and the corresponding spot in the database
	private function set(&$variable, $column, $value)
	{
		if (!$this->session_user_can_write())
		{
			return Entry::set_error_description("Session user cannot edit entry.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("UPDATE user_entries SET $column = '%s' WHERE user_id = %d AND entry_id = %d",
			$mysqli->escape_string($value),
			$this->user_id,
			$this->entry_id
		));
		
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
	
	public function add_annotation($annotation_contents)
	{
		if (!$this->session_user_can_write())
		{
			return Entry::set_error_description("Session user cannot edit entry.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO user_entry_annotations (user_id, entry_id, contents) VALUES (%d, %d, '%s'",
			$this->user_id,
			$this->entry_id,
			$mysqli->escape_string($annotation_contents)
		));
		
		$result = $mysqli->query("SELECT * FROM user_entry_annotations WHERE annotation_id = %d", $mysqli->insert_id);
		
		if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
		{
			array_push($this->get_annotations(), Annotation::from_mysql_result_assoc($result_assoc));
		}
		
		return $this;
	}
	
	public function remove_annotation($annotation)
	{
		if (!in_array($annotation, $this->get_annotations())
			|| $annotation->get_entry_id() !== $this->get_entry_id())
		{
			return Entry::set_error_description("Cannot delete annotation.");
		}
		
		$annotation->delete();
		
		unset ($this->annotations);
		
		return $this;
	}
	
	//  Returns a copy of $this owned and editable by the Session User
	public function copy_for_session_user()
	{
		if (!Session::get()->get_user())
		{
			return Entry::set_error_description("Session user has not reauthenticated.");
		}
		
		if ($this->user_id === Session::get()->get_user()->get_user_id())
		{
			$entries_by_id_for_user_id = Entry::entries_by_id_for_user_id(Session::get()->get_user());
			//  Just make sure that this Entry object has been appropriately registered
			return ($entries_by_id_for_user_id[$this->entry_id] = $this);
		}
		else
		{
			$mysqli = Connection::get_shared_instance();
			
			//  Insert into user_entries the dictionary row corresponding to this Entry object
			//      If such a row already exists in user_entries, ignore the insertion error
			$mysqli->query(sprintf("INSERT IGNORE INTO user_entries (user_id, entry_id, word_0, word_1, word_1_pronun) SELECT %d, entry_id, word_0, word_1, word_1_pronun FROM dictionary WHERE entry_id = %d",
				Session::get()->get_user()->get_user_id(),
				$this->entry_id
			));
			
			$query = sprintf("SELECT * FROM (SELECT entry_id, languages_0.lang_code AS lang_code_0, languages_1.lang_code AS lang_code_1 FROM %s WHERE entry_id = %d) AS reference LEFT JOIN user_entries USING (entry_id) WHERE user_id = %d",
				Dictionary::join(),
				$this->entry_id,
				Session::get()->get_user()->get_user_id()
			);
			
			$result = $mysqli->query($query);
			
			if (!$result || !($result_assoc = $result->fetch_assoc()))
			{
				return Entry::set_error_description("Entry failed to copy for session user: " .
					(!!$mysqli->error ? $mysqli->error : $query)
				);
			}
			
			return Entry::from_mysql_result_assoc($result_assoc);
		}
	}
	
	public function update_repetition_details($point)
	{
		if (!$this->session_user_can_write())
		{
			return Entry::set_error_description("Session user cannot edit entry.");
		}
		$_efactor = $this->efactor + (0.1 - (4 - $point) * (0.08 + (4 - $point) * 0.02));
		$new_efactor = min(max($_efactor, 1.3), 2.5);
		$new_interval = $this->interval * $new_efactor;
		$is_updated = $mysqli->query(sprintf(
			"UPDATE user_entries SET interval = %d, efactor = %f ".
			"WHERE user_id = %d AND entry_id = %d",
			$new_interval, $new_efactor,
			$this->user_id, $this->entry_id
		));
		
		if (!$is_updated)
		{
			return Entry::set_error_description("Failed to update interval details: " . $mysqli->error);
		}
		
		$this->interval = $new_interval;
		$this->efactor = $new_efactor;
	}

	public function assoc_for_json()
	{
		$privacy = !!$this->get_owner() && !$this->get_owner()->equals(Session::get()->get_user());
		
		$entry = !$privacy ? $this : Dictionary::select_entry($this->entry_id);
		
		return array (
			"entryId" => $entry->entry_id,
			"words" => $entry->words,
			"pronuncations" => $entry->pronunciations
		);
	}
}

?>