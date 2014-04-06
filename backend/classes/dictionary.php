<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Dictionary
{
	protected static $error_description = null;
	protected static function set_error_description($error_description)
	{
		static::$error_description = $error_description;
		return null;
	}
	public static function get_error_description()
	{
		return static::$error_description;
	}
	public static function unset_error_description()
	{
		$error_description = static::$error_description;
		static::$error_description = null;
		return $error_description;
	}

	private static $entries_by_id = array ();

	public static $page_size = null;
	public static $page_num = null;
	
	public static $find_last_count = null;
	
	public static function reset()
	{
		self::$entries_by_id = array ();
		self::$error_description = null;
	}
	//  Returns the join of the dictionary on the languages table
	//      so that we can include language codes (which exist only in the languages table)
	public static function join()
	{
		$join_dict_lang_0 = "dictionary LEFT JOIN languages AS languages_0 ON dictionary.lang_id_0 = languages_0.lang_id";
		return "($join_dict_lang_0) LEFT JOIN languages AS languages_1 ON dictionary.lang_id_1 = languages_1.lang_id";
	}

	public static function language_code_columns()
	{
		return "languages_0.lang_code AS lang_code_0, languages_1.lang_code AS lang_code_1";
	}

	//  Returns the default columns that result from join()
	public static function default_columns()
	{
		return "dictionary.*, " . self::language_code_columns();
	}
	
	public static function query($query, $lang_codes, $pagination = null, $exact_matches_only = false)
	{
		$mysqli = Connection::get_shared_instance();
		
		//  Now perform the query
		//      First, decode the query and make sure it's safe
		$query = $mysqli->escape_string($query);
		
		//  Make sure the language codes are safe
		foreach ($lang_codes as &$lang_code)
		{
			$lang_code = $mysqli->escape_string($lang_code);
		}
		
		$wildcard = $exact_matches_only ? "" : "%%";
		
		//      Second, take all the pieces created above and run the SQL query
		$query = sprintf("SELECT %s, CHAR_LENGTH(word_0) AS lang_0_length, CHAR_LENGTH(word_1) AS lang_1_length, (word_0 != '$query' AND word_1 != '$query') AS inexact FROM %s WHERE (word_0 LIKE '$wildcard%s$wildcard' OR word_1 LIKE '$wildcard%s$wildcard') AND (languages_0.lang_code IN ('%s') AND languages_1.lang_code IN ('%s')) ORDER BY inexact, lang_1_length, lang_0_length LIMIT 500",
			self::default_columns(),
			self::join(),
			$query,
			$query,
			implode("','", $lang_codes),
			implode("','", $lang_codes)
		);
		
		if (!($result = $mysqli->query($query)))
		{
			return static::set_error_description("Failed to find entry: " . $mysqli->error);
		}
		
		//  Save information about query results in static properties
		self::$find_last_count = $result->num_rows;
		self::$page_size = self::$find_last_count;
		self::$page_num = 1;
		
		//  Use pagination only if we have both page size and page number
		if (isset($pagination["size"]) && isset($pagination["num"]))
		{
			self::$page_size = intval($pagination["size"], 10);
			self::$page_num = intval($pagination["num"], 10);
		}
		
		//  Compute the minimum and maximum entries to return
		$entry_min = (self::$page_num - 1) * self::$page_size;
		$entry_max = $entry_min + self::$page_size - 1;
		
		//  Convert the mysql_result associative arrays to Entry objects
		$entries_returnable = array ();
		$entry_num = 0;
		while (($result_assoc = $result->fetch_assoc()))
		{
			$entry = Entry::from_mysql_result_assoc($result_assoc);
			self::$entries_by_id[$entry->get_entry_id()] = $entry;
			
			if (!self::$page_size || !self::$page_num || ($entry_num >= $entry_min && $entry_num <= $entry_max))
			{
				array_push($entries_returnable, $entry);
			}
			$entry_num ++;
		}
		
		return $entries_returnable;
	}
	
	//  Gets an entry from the dictionary by entry_id
	public static function select_entry_by_id($entry_id)
	{
		$entry_id = intval($entry_id, 10);
		
		if (!in_array($entry_id, array_keys(self::$entries_by_id)))
		{
			$mysqli = Connection::get_shared_instance();
			
			$result = $mysqli->query(sprintf("SELECT %s FROM %s WHERE entry_id = $entry_id",
				self::default_columns(),
				self::join()
			));
			
			if (!$result || !($result_assoc = $result->fetch_assoc()))
			{
				return static::set_error_description("Failed to select dictionary entry where entry_id = $entry_id.");
			}
			
			self::$entries_by_id[$entry_id] = Entry::from_mysql_result_assoc($result_assoc);
		}
		
		return self::$entries_by_id[$entry_id];
	}
	
	public static function get_lang_code($lang_id)
	{
		$lang_id = intval($lang_id, 10);
		
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query("SELECT * FROM languages WHERE lang_id = $lang_id");
		
		if (!!$result && $result->num_rows == 1 && !!($result_assoc = $result->fetch_assoc()))
		{
			return $result_assoc["lang_code"];
		}
		
		return static::set_error_description("Failed to select language where language_id = $lang_id.");
	}
	
	public static function get_lang_id($lang_code)
	{
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query(sprintf("SELECT * FROM languages WHERE lang_code LIKE '%s'",
			$mysqli->escape_string($lang_code)
		));
		
		if (!!$result && $result->num_rows == 1 && !!($result_assoc = $result->fetch_assoc()))
		{
			return intval($result_assoc["lang_id"], 10);
		}
		
		return static::set_error_description("Failed to select language where language_code = '$lang_code'.");
	}
}

?>