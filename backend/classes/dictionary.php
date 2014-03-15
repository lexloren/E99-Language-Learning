<?php

require_once "./backend/connect.php";
require_once "./backend/support.php";

class Dictionary
{
	public static $page_size = null;
	public static $page_num = null;
	
	public static $look_up_last_count = null;

	public static function look_up($word, $lang_codes, $pagination = null)
	{
		global $mysqli;
		
		//  Now perform the query
		//      First, decode the query and make sure it's safe
		$query = $mysqli->escape_string($word);
		
		//  Make sure the language codes are safe
		foreach ($lang_codes as &$lang_code)
		{
			$lang_code = $mysqli->escape_string($lang_code);
		}
		
		//  To make the SQL easier to read, I construct it piece by piece
		$join_dict_lang_0 = "(dictionary LEFT JOIN languages AS language_0 ON dictionary.lang_id_0 = language_0.lang_id)";
		$join_dict_langs = "$join_dict_lang_0 LEFT JOIN languages AS language_1 ON dictionary.lang_id_1 = language_1.lang_id";

		//  If the query contains three characters or fewer,
		//      then should we require exact matches to limit the results?
		$exact_matches_only = false; //strlen(urldecode($_GET["query"])) > 3;
		$wildcard = $exact_matches_only ? "" : "%%";
		
		//      Second, take all the pieces created above and run the SQL query
		$query = sprintf("SELECT dictionary.*, language_0.lang_code AS lang_code_0, language_1.lang_code AS lang_code_1, CHAR_LENGTH(word_0) AS lang_0_length, CHAR_LENGTH(word_1) AS lang_1_length, (word_0 != '$query' AND word_1 != '$query') AS inexact FROM $join_dict_langs WHERE (word_0 LIKE '$wildcard%s$wildcard' OR word_1 LIKE '$wildcard%s$wildcard') AND (language_0.lang_code IN ('%s') AND language_1.lang_code IN ('%s')) ORDER BY inexact, lang_1_length, lang_0_length LIMIT 500",
			//implode(", ", $columnsSelected),
			$query,
			$query,
			implode("','", $lang_codes),
			implode("','", $lang_codes)
		);
		
		$result = $mysqli->query($query);
		if (!$result)
		{
			exit_with_error("Database Error", $mysqli->error);
		}
		
		self::$look_up_last_count = $result->num_rows;
		self::$page_size = self::$look_up_last_count;
		self::$page_num = 1;
		
		if (isset ($pagination["size"]) && isset ($pagination["num"]))
		{
			self::$page_size = intval($pagination["size"]);
			self::$page_num = intval($pagination["num"]);
		}
		
		$entry_min = (self::$page_num - 1) * self::$page_size;
		$entry_max = $entry_min + self::$page_size - 1;
		
		$entries_returnable = array ();
		$entry_num = 0;
		while (($result_assoc = $result->fetch_assoc()))
		{
			if (!self::$page_size || !self::$page_num || ($entry_num >= $entry_min && $entry_num <= $entry_max))
			{
				array_push($entries_returnable, Entry::from_mysql_result_assoc($result_assoc));
			}
			$entry_num ++;
		}
		
		return $entries_returnable;
	}
}

?>