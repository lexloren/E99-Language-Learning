<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Entry extends DatabaseRow
{
	/***    CLASS/STATIC    ***/
	protected static $instances_by_id = array ();
	protected static $errors = null;
	
	public static function select_by_id($entry_id, $promote_automatically = true)
	{
		$entry_id = intval($entry_id, 10);
		
		if ($promote_automatically
			&& Session::get() && Session::get()->get_user())
		{
			if (($user_entry =
					UserEntry::select_by_user_id_entry_id(
						Session::get()->get_user()->get_user_id(),
						$entry_id,
						false)
					)) return $user_entry;
		}
		
		if (isset(self::$instances_by_id[$entry_id])) return self::$instances_by_id[$entry_id];
		
		return Dictionary::select_entry_by_id($entry_id);
	}
	
	/***    INSTANCE    ***/

	protected $entry_id = null;
	public function get_entry_id()
	{
		return $this->entry_id;
	}
	public function get_user()
	{
		return null;
	}
	public function get_owner()
	{
		return null;
	}
	public function get_user_id()
	{
		return null;
	}
	
	protected $word_0;
	public function get_word_0()
	{
		return $this->word_0;
	}
	
	protected $word_1;
	public function get_word_1()
	{
		return $this->word_1;
	}
	
	protected $lang_code_0;
	public function get_lang_code_0()
	{
		return $this->lang_code_0;
	}
	
	protected $lang_code_1;
	public function get_lang_code_1()
	{
		return $this->lang_code_1;
	}
	
	public function languages()
	{
		return array (
			$this->get_lang_code_0(),
			$this->get_lang_code_1()
		);
	}
	public function words($i = null)
	{
		return $i !== null
			? ($i === 0
				? $this->get_word_0()
				: ($i === 1 ? $this->get_word_1() : null))
			: array (
					$this->get_lang_code_0() => $this->get_word_0(),
					$this->get_lang_code_1() => $this->get_word_1()
				);
	}

	protected $pronunciations = null;
	public function pronunciations()
	{
		return $this->pronunciations;
	}
	
	public function annotations()
	{
		if (!!Session::get()
			&& !!($session_user = Session::get()->get_user())
			)
		{
			$errors = static::$errors;
			if (($user_entry = UserEntry::select_by_user_id_entry_id($session_user->get_user_id(), $this->get_entry_id(), false)))
			{
				return $user_entry->annotations();
			}
			static::errors_unset();
			static::$errors = $errors;
		}
		
		return array ();
	}

	private function __construct($entry_id, $lang_code_0, $lang_code_1,
		$word_0, $word_1, $pronunciation = null)
	{
		$this->entry_id = intval($entry_id, 10);
		$this->word_0 = $word_0;
		$this->word_1 = $word_1;
		$this->lang_code_0 = $lang_code_0;
		$this->lang_code_1 = $lang_code_1;
		$this->pronunciations = array (
			$lang_code_1 => $pronunciation
		);
	}
	
	public function revert()
	{
		if (!!Session::get()
			&& !!($session_user = Session::get()->get_user())
			)
		{
			$errors = static::$errors;
			if (($user_entry = UserEntry::select_by_user_id_entry_id($session_user->get_user_id(), $this->get_entry_id(), false)))
			{
				return $user_entry->revert();
			}
			static::errors_unset();
			static::$errors = $errors;
		}
		
		return $this;
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
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["entry_id"],
				$result_assoc["lang_code_0"],
				$result_assoc["lang_code_1"],
				$result_assoc["word_0"],
				$result_assoc["word_1"],
				!!$result_assoc["word_1_pronun"] && strlen($result_assoc["word_1_pronun"]) > 0 ? $result_assoc["word_1_pronun"] : null
			)
			: null;
	}
	
	//  Sets a new user-specific value for this Entry's word_0
	public function set_word_0($word_0)
	{
		return ($user_entry = $this->copy_for_session_user())
			? $user_entry->set_word_0($word_0)
			: null;
	}
	
	//  Sets a new user-specific value for this Entry's word_1
	public function set_word_1($word_1)
	{
		return ($user_entry = $this->copy_for_session_user())
			? $user_entry->set_word_1($word_1)
			: null;
	}
	
	//  Sets a new user-specific value for this Entry's word_1_pronun
	public function set_word_1_pronunciation($word_1_pronun)
	{
		return ($user_entry = $this->copy_for_session_user())
			? $user_entry->set_word_1_pronunciation($word_1_pronun)
			: null;
	}
	
	public function in($array)
	{
		foreach ($array as $key => $item)
		{
			if ($this->equals($item))
			{
				return $key;
			}
		}
		return null;
	}
	
	public function equals($entry)
	{
		return !!$entry && $this->get_entry_id() === $entry->get_entry_id();
	}
	
	//  Returns a copy of $this owned and editable by the Session User
	public function copy_for_session_user()
	{
		if (!Session::get() || !($session_user = Session::get()->get_user()))
		{
			return static::errors_push("Session user has not reauthenticated.");
		}
		
		return $this->copy_for_user($session_user);
	}
	
	public function copy_for_user($user)
	{
		if (!$user) return static::errors_push("Failed to copy entry for null user.");
		
		return UserEntry::select_by_user_id_entry_id($user->get_user_id(), $this->get_entry_id());
	}
	
	public function annotations_count()
	{
		return 0;
	}

	public function user_can_read($user)
	{
		if (Session::get()
			&& ($session_user = Session::get()->get_user())
			&& $session_user->sittings_live()) return false;
		
		return true;
	}
	
	public function json_assoc($privacy = null, $hint = null)
	{
		if ($privacy === null) $privacy = $this->privacy();
		
		$entry = !!$privacy ? self::select_by_id($this->get_entry_id(), false) : $this;
		
		$assoc = array (
			"entryId" => $entry->get_entry_id(),
			"languages" => $entry->languages(),
			"words" => $entry->words(),
			"pronuncations" => $entry->pronunciations(),
			"annotationsCount" => $this->annotations_count()
		);
		
		return $this->prune($assoc, array (0 => "entryId"), $privacy);
	}
	
	public function json_assoc_detailed($privacy = null, $hint = null)
	{
		if (!!Session::get() && !!($session_user = Session::get()->get_user()))
		{
			if (($user_entry = UserEntry::select_by_user_id_entry_id($session_user->get_user_id(), $this->get_entry_id(), false)))
			{
				return $user_entry->json_assoc_detailed($privacy, $hint);
			}
		}
		
		return parent::json_assoc_detailed($privacy);
	}
}

class UserEntry extends Entry
{
	/***    CLASS/STATIC    ***/
	protected static $instances_by_id = array ();
	protected static $instances_by_entry_id_by_user_id = array ();
	
	public static function reset()
	{
		parent::reset();
		self::$instances_by_entry_id_by_user_id = array ();
	}
	
	public static function select_by_user_entry_id($user_entry_id)
	{
		$table = "user_entries";
		$column = "user_entry_id";
		$id = intval($user_entry_id, 10);
		
		$result = Connection::query("SELECT * FROM $table WHERE $column = $id");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to select from $table: $error.");
		}
		
		if (!$result || $result->num_rows === 0 || !($result_assoc = $result->fetch_assoc()))
		{
			return static::errors_push("Failed to select any rows from $table where $column = $id.");
		}
		
		return self::select_by_user_id_entry_id($result_assoc["user_id"], $result_assoc["entry_id"]);
	}
	
	protected static function entries_by_id_for_user_id($user_id)
	{
		$user_id = intval($user_id, 10);
		
		if (!in_array($user_id, array_keys(self::$instances_by_entry_id_by_user_id)))
		{
			$instances_by_entry_id_by_user_id[$user_id] = array ();
		}
		
		return $instances_by_entry_id_by_user_id[$user_id];
	}
	
	public static function select_by_user_id_entry_id($user_id, $entry_id, $insert_if_necessary = true)
	{
		$user_id = intval($user_id, 10);
		$entry_id = intval($entry_id, 10);
		
		$entries_by_id_for_user_id = self::entries_by_id_for_user_id($user_id);
		
		if (in_array($entry_id, array_keys($entries_by_id_for_user_id)))
		{
			return $entries_by_id_for_user_id[$entry_id];
		}
		
		if ($insert_if_necessary)
		{
			//  Insert into user_entries the dictionary row corresponding to this Entry object
			//      If such a row already exists in user_entries, ignore the insertion error
			Connection::query("INSERT INTO user_entries (user_id, entry_id, word_0, word_1, word_1_pronun) " .
					"SELECT $user_id, entry_id, word_0, word_1, word_1_pronun FROM dictionary WHERE entry_id = $entry_id ON DUPLICATE KEY UPDATE user_entry_id = user_entry_id");
			
			if (!!($error = Connection::query_error_clear()))
			{
				return static::errors_push("Failed to insert user entry: $error.", ErrorReporter::ERRCODE_DATABASE);
			}
		}
		
		$query = sprintf("SELECT * FROM (SELECT entry_id, %s FROM %s WHERE entry_id = %d) AS reference LEFT JOIN user_entries USING (entry_id) WHERE user_id = %d",
			Dictionary::language_code_columns(),
			Dictionary::join(),
			$entry_id,
			$user_id
		);
		
		$result = Connection::query($query);
		
		if (!!($error = Connection::query_error_clear()) || !$result || !($result_assoc = $result->fetch_assoc()))
		{
			return $insert_if_necessary
				? static::errors_push("Failed to select user entry where user_id = $user_id and entry_id = $entry_id: " . (!!$error ? $error : $query))
				: null;
		}
		
		return self::from_mysql_result_assoc($result_assoc);
	}
	
	private $user_entry_id;
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
		return !!$this->get_user_id() ? User::select_by_id($this->get_user_id()) : null;
	}
	
	public function get_entry()
	{
		return Entry::select_by_id($this->get_entry_id(), false);
	}
	
	public function get_lang_code_0()
	{
		return !!$this->get_entry() ? $this->get_entry()->get_lang_code_0() : null;
	}
	
	public function get_lang_code_1()
	{
		return !!$this->get_entry() ? $this->get_entry()->get_lang_code_1() : null;
	}

	public function pronunciations()
	{
		return !!$this->get_entry() ? $this->get_entry()->pronunciations() : null;
	}
	
	public function uncache_annotations()
	{
		if (isset($this->annotations)) unset($this->annotations);
	}
	public function uncache_lists()
	{
		if (isset($this->lists)) unset($this->lists);
	}
	public function uncache_all()
	{
		$this->uncache_annotations();
		$this->uncache_lists();
	}

	private $annotations;
	public function annotations()
	{
		$table = "user_entry_annotations LEFT JOIN user_entries USING (user_entry_id)";
		return self::cache($this->annotations, "Annotation", $table, "user_entry_id", $this->get_user_entry_id());
	}
	
	private $lists;
	public function lists()
	{
		return self::cache($this->lists, "EntryList", "lists CROSS JOIN list_entries USING (list_id)", "user_entry_id", $this->get_user_entry_id());
	}

	private function __construct($user_entry_id, $user_id, $entry_id,
		$lang_code_0, $lang_code_1,
		$word_0, $word_1, $pronunciation = null)
	{
		$this->user_entry_id = intval($user_entry_id, 10);
		$this->entry_id = $entry_id !== null ? intval($entry_id, 10) : null;
		$this->user_id = intval($user_id, 10);
		$this->word_0 = $word_0;
		$this->word_1 = $word_1;
		$this->lang_code_0 = $lang_code_0;
		$this->lang_code_1 = $lang_code_1;
		$this->pronunciations = array (
			$lang_code_1 => $pronunciation
		);
		
		$entries_by_id_for_user_id = self::entries_by_id_for_user_id($this->user_id);
		$entries_by_id_for_user_id[$this->entry_id] = $this;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"user_entry_id",
			"user_id",
			"entry_id",
			"lang_code_0",
			"lang_code_1",
			"word_0",
			"word_1",
			"word_1_pronun"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["user_entry_id"],
				$result_assoc["user_id"],
				$result_assoc["entry_id"],
				$result_assoc["lang_code_0"],
				$result_assoc["lang_code_1"],
				$result_assoc["word_0"],
				$result_assoc["word_1"],
				!!$result_assoc["word_1_pronun"] && strlen($result_assoc["word_1_pronun"]) > 0 ? $result_assoc["word_1_pronun"] : null
			)
			: null;
	}
	
	public function revert()
	{
		$failure_message = "Failed to revert user entry to dictionary entry";
		
		if (!$this->get_entry())
		{
			return static::errors_push("$failure_message: No dictionary entry associated with user entry where user_entry_id = " . $this->get_user_entry_id());
		}
		
		$user_entry = $this;
		
		return Connection::transact(
			function () use ($user_entry)
			{
				if (!$user_entry->set_word_0($user_entry->get_entry()->get_word_0())) return null;
				if (!$user_entry->set_word_1($user_entry->get_entry()->get_word_1())) return null;
				
				$pronunciations = $user_entry->get_entry()->pronunciations();
				if (!$user_entry->set_word_1_pronunciation($pronunciations[$user_entry->get_entry()->get_lang_code_1()])) return null;
				
				return $user_entry;
			}
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
	
	//  providing a $hint, either a list or a test, reduces the amount of work
	//      to verify that $user has permission to read this UserEntry for copying
	public function copy_for_user($user, $hint = null)
	{
		if (!$user)
		{
			return static::errors_push("Failed to copy entry for null user.");
		}
		
		if ($this->user_is_owner($user)) return $this;
		
		if (!$this->user_can_read($user, $hint))
		{
			return $this->get_entry()->copy_for_user($user);
		}
		
		//  Insert into user_entries the dictionary row corresponding to this Entry object
		//      If such a row already exists in user_entries, ignore the insertion error
		Connection::query(sprintf("INSERT INTO user_entries (user_id, entry_id, word_0, word_1, word_1_pronun) SELECT %d, entry_id, word_0, word_1, word_1_pronun FROM user_entries AS source WHERE source.user_entry_id = %d ON DUPLICATE KEY UPDATE user_entries.user_entry_id = user_entries.user_entry_id",
			$user->get_user_id(),
			$this->get_user_entry_id()
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Entry failed to copy for user: $error.");
		}
		
		return self::select_by_user_id_entry_id($user->get_user_id(), $this->get_entry_id(), false);
	}
	
	public function annotations_count()
	{
		return self::count("user_entry_annotations", "user_entry_id", $this->get_user_entry_id());
	}
	
	/*** PERMISSIONS ***/
	public function user_can_read($user, $hint = null)
	{
		return $hint === true
			|| (parent::user_can_read($user)
				&& ($this->user_can_write($user)
					|| $this->user_can_read_via($user, $hint)
					|| $this->user_can_read_via_some_list($user)));
	}
	
	public function user_can_write($user, $hint = null)
	{
		return $hint === true
			|| parent::user_can_write($user)
			|| $this->user_can_write_via($user, $hint)
			|| $this->user_can_write_via_some_list($user);
	}
	
	private function user_can_read_via($user, $hint)
	{
		if (!$user || !$hint) return false;
		
		return $this->hint_relevant($hint)
			&& ($hint->user_can_read($user) || $hint->user_can_execute($user));
	}
	
	private function user_can_write_via($user, $hint)
	{
		if (!$user || !$hint) return false;
		
		return $this->hint_relevant($hint) && $hint->user_can_write($user);
	}
	
	private function user_can_read_via_some_list($user)
	{
		foreach ($this->lists() as $list)
		{
			if ($list->user_can_read($user)) return true;
		}
		return false;
	}
	
	private function user_can_write_via_some_list($user)
	{
		foreach ($this->lists() as $list)
		{
			if ($list->user_can_write($user)) return true;
		}
		return false;
	}
	
	public function hint_relevant($hint)
	{
		if (!$hint) return false;
		
		//  Quick check
		if (in_array($this, ($entries = $hint->entries()))) return true;
		
		//  Robustness, in case of unexpected duplicate UserEntry
		foreach ($entries as $entry)
		{
			if ($entry->get_user_entry_id() === $this->get_user_entry_id())
			{
				//  Unexpected error
				EntryList::errors_push("List whose list_id = " . $list->get_list_id() . " appears to contain a duplicate UserEntry whose user_entry_id = " . $this->get_user_entry_id());
				return true;
			}
		}
		
		return false;
	}
	
	/*** OUTPUT ***/
	
	public function json_assoc($privacy = null, $hint = null)
	{
		if ($privacy === null && $hint !== null)
		{
			if (Session::get() && ($session_user = Session::get()->get_user()))
			{
				$privacy = !$this->user_can_read($session_user, $hint);
			}
		}
		
		$assoc = parent::json_assoc($privacy);
		$public_keys = array_keys($assoc);
		
		$lists = $this->lists();
		foreach ($lists as $l => $list)
		{
			if (!$list->session_user_can_read())
			{
				unset($lists[$l]);
			}
		}
		$assoc["lists"] = self::json_array($lists);
		$assoc["annotations"] = self::json_array($this->annotations());
		
		return $this->prune($assoc, $public_keys, $privacy);
	}
	
	public function json_assoc_detailed($privacy = null, $hint = null)
	{
		return $this->json_assoc($privacy, $hint);
	}
}

?>
