<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Dictionary extends ErrorReporter
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;

	private static $entries_by_id = array ();

	public static $page_size = null;
	public static $page_num = null;
	public static $pages_count = null;
	
	public static $find_last_count = null;
	
	public static function reset()
	{
		self::$entries_by_id = array ();
		parent::reset();
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
	
	public static function query($query, $lang_codes, $pagination = null, $exact_matches_only = false, $user = null)
	{
		//  Make sure the language codes are safe
		foreach ($lang_codes as &$lang_code)
		{
			$lang_code = Connection::escape($lang_code);
		}
		
		if ($exact_matches_only)
		{
			$wc = "";
			$op = "=";
		}
		else
		{
			$wc = "%%";
			$op = "LIKE";
		}
		
		//  Now perform the query
		//      First, decode the query and make sure it's safe
		//      For BOTH the PHP sprintf() and the SQL query
		$query = str_replace("%", "%%", Connection::escape($query));
		
		if ($user)
		{
			Connection::query(sprintf("INSERT INTO dictionary_queries (user_id, timestamp, contents) VALUES (%d, %d, '%s')",
				$user->get_user_id(),
				time(),
				$query
			));
			
			if (!Connection::query_error_clear() && ($query_id = Connection::query_insert_id()))
			{
				foreach ($lang_codes as &$lang_code)
				{
					Connection::query("INSERT INTO dictionary_query_languages (query_id, lang_code) VALUES ($query_id, '$lang_code')");
					if (!!Connection::query_error_clear()) unset($lang_code);
				}
			}
		}
		
		//      Second, take all the pieces created above and run the SQL query
		$result = Connection::query(sprintf("SELECT %s, CHAR_LENGTH(word_0) AS lang_0_length, CHAR_LENGTH(word_1) AS lang_1_length, (word_0 != '$query' AND word_1 != '$query') AS inexact FROM %s WHERE (word_0 $op '$wc%s$wc' OR word_1 $op '$wc%s$wc') AND (languages_0.lang_code IN ('%s') AND languages_1.lang_code IN ('%s')) ORDER BY inexact, lang_1_length, lang_0_length LIMIT 500",
			self::default_columns(),
			self::join(),
			$query,
			$query,
			implode("','", $lang_codes),
			implode("','", $lang_codes)
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to find entry: $error.");
		}
		
		//  Save information about query results in static properties
		self::$find_last_count = $result->num_rows;
		self::$page_size = min(self::$find_last_count, 10);
		self::$page_num = 1;
		
		//  Use pagination only if we have both page size and page number
		if (isset($pagination["size"]) && isset($pagination["num"]))
		{
			self::$page_size = intval($pagination["size"], 10);
			self::$page_num = intval($pagination["num"], 10);
		}
		
		self::$pages_count = intval(self::$find_last_count / self::$page_size, 10) + (self::$find_last_count % self::$page_size != 0);
		
		//  Compute the minimum and maximum entries to return
		$entry_min = (self::$page_num - 1) * self::$page_size;
		$entry_max = $entry_min + self::$page_size - 1;
		
		//  Convert the mysql_result associative arrays to Entry objects
		$entries_returnable = array ();
		$entry_num = 0;
		while (($result_assoc = $result->fetch_assoc()))
		{
			if (!self::$page_size || !self::$page_num || ($entry_num >= $entry_min && $entry_num <= $entry_max))
			{
				$entry = Entry::from_mysql_result_assoc($result_assoc);
				self::$entries_by_id[$entry->get_entry_id()] = $entry;
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
			$result = Connection::query(sprintf("SELECT %s FROM %s WHERE entry_id = $entry_id",
				self::default_columns(),
				self::join()
			));
			
			if (!!($error = Connection::query_error_clear()) || !$result || !($result_assoc = $result->fetch_assoc()))
			{
				return static::errors_push("Failed to select dictionary entry where entry_id = $entry_id" . (!!$error ? ": $error." : "."));
			}
			
			self::$entries_by_id[$entry_id] = Entry::from_mysql_result_assoc($result_assoc);
		}
		
		return self::$entries_by_id[$entry_id];
	}
	
	public static function get_lang_code($lang_id)
	{
		$lang_id = intval($lang_id, 10);
		
		$result = Connection::query("SELECT * FROM languages WHERE lang_id = $lang_id");
		
		$failure_message = "Failed to select language where language_id = $lang_id";
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("$failure_message: $error.");
		}
		
		if (!!$result && $result->num_rows == 1 && !!($result_assoc = $result->fetch_assoc()))
		{
			return $result_assoc["lang_code"];
		}
		
		return static::errors_push("$failure_message.");
	}
	
	public static function get_lang_id($lang_code)
	{
		$result = Connection::query(sprintf("SELECT * FROM languages WHERE lang_code = '%s'",
			Connection::escape($lang_code)
		));
		
		$failure_message = "Failed to select language where language_code = '$lang_code'";
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("$failure_message: $error.");
		}
		
		if (!!$result && $result->num_rows == 1 && !!($result_assoc = $result->fetch_assoc()))
		{
			return intval($result_assoc["lang_id"], 10);
		}
		
		return static::errors_push("$failure_message.");
	}
}

?>