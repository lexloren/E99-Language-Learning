<?php

require_once "./backend/connect.php";
require_once "./backend/support.php";
require_once "./backend/classes.php";

//  Get all the languages available in the application
$result = $mysqli->query("SELECT * FROM languages");
if (!$result)
{
	exit_with_error("Database Error", $mysqli->error);
}
$languages_available = mysqli_fetch_all_assocs($result);
$result->close();

//  Create a dictionary mapping language codes to language identifiers
$lang_codes_dictionary = array();
foreach ($languages_available as $language_available)
{
	$lang_codes_dictionary[$language_available["lang_code"]] = $language_available["lang_id"];
}

//  Perform the dictionary search, if requested
if (isset ($_GET["query"]))
{
	//  Limit the search to certain languages, if requested
	if (isset ($_GET["langs"]))
	{
		$lang_codes_requested = array();
		
		$lang_codes_getted = explode(",", $_GET["langs"]);
		foreach ($lang_codes_getted as &$lang_code_getted)
		{
			//  Verify that this language code requested by user is in the system;
			//      if not, then ignore this language code requested by user
			if (in_array($lang_code_getted, array_keys($lang_codes_dictionary)))
			{
				array_push($lang_codes_requested, $lang_code_getted);
			}
		}
	}
	//  If no limitations on languages requested, then search all languages available
	else
	{
		$lang_codes_requested = array_keys($lang_codes_dictionary);
	}
	
	//  Now perform the query
	//      First, decode the query and make sure it's safe
	$query = $mysqli->escape_string(urldecode($_GET["query"]));
	
	//  To make the SQL easier to read, I construct it piece by piece
	$join_dict_lang_0 = "(dictionary LEFT JOIN languages AS language_0 ON dictionary.lang_id_0 = language_0.lang_id)";
	$join_dict_langs = "$join_dict_lang_0 LEFT JOIN languages AS language_1 ON dictionary.lang_id_1 = language_1.lang_id";

	//  We will convert the database columns to the proper format for sending JSON to the front end
	/*
	$columns = array(
		"dictionary.entry_id" => "entryId",
		"dictionary.user_id" => "userId",
		"language_0.lang_code" => "langCodeKnown",
		"language_1.lang_code" => "langCodeUnknown",
		"dictionary.word_0" => "languageKnown",
		"dictionary.word_1" => "languageUnknown",
		"dictionary.pronunciation" => "languageUnknownPronunciation",
		"CHAR_LENGTH(word_0)" => "languageKnownLength",
		"CHAR_LENGTH(word_1)" => "languageUnknownLength",
		"(word_0 != '$query' AND word_1 != '$query')" => "isNotExact"
	);
	
	$columnsSelected = array();
	foreach ($columns as $old => $new)
	{
		array_push($columnsSelected, "$old AS $new");
	}
	*/

	//  If the query contains three characters or fewer,
	//      then should we require exact matches to limit the results?
	$exact_matches_only = false; //strlen(urldecode($_GET["query"])) > 3;
	$wildcard = $exact_matches_only ? "" : "%%";
	
	//      Second, take all the pieces created above and run the SQL query
	$query = sprintf("SELECT dictionary.*, language_0.lang_code AS lang_code_0, language_1.lang_code AS lang_code_1, CHAR_LENGTH(word_0) AS lang_0_length, CHAR_LENGTH(word_1) AS lang_1_length, (word_0 != '$query' AND word_1 != '$query') AS inexact FROM $join_dict_langs WHERE (word_0 LIKE '$wildcard%s$wildcard' OR word_1 LIKE '$wildcard%s$wildcard') AND (language_0.lang_code IN ('%s') AND language_1.lang_code IN ('%s')) ORDER BY inexact, lang_1_length, lang_0_length",
		//implode(", ", $columnsSelected),
		$query,
		$query,
		implode("','", $lang_codes_requested),
		implode("','", $lang_codes_requested)
	);
	
	$result = $mysqli->query($query);
	if (!$result)
	{
		exit_with_error("Database Error", $mysqli->error);
	}
	$mysqli->close();
	
	$entries_count = $result->num_rows;
	
	$page_size = $entries_count;
	$page_num = 1;
	
	if (isset ($_GET["page_size"]) && isset ($_GET["page_num"]))
	{
		$page_size = intval($_GET["page_size"]);
		$page_num = intval($_GET["page_num"]);
	}
	
	$entry_min = ($page_num - 1) * $page_size;
	$entry_max = $entry_min + $page_size - 1;
	
	$entries_returnable = array ();
	$entry_num = 0;
	while (($result_assoc = $result->fetch_assoc()))
	{
		if ($entry_num >= $entry_min && $entry_num <= $entry_max)
		{
			$entry = Entry::from_mysql_result_assoc($result_assoc);
			array_push($entries_returnable, $entry->assoc_for_json());
		}
		$entry_num ++;
	}
	
	//      Finally, format the query results and send them to the front end
	exit_with_result($entries_returnable, array (
		"entriesCount" => $entries_count,
		"pageSize" => $page_size,
		"pageNum" => $page_num
	));
}

exit_with_result(NULL);

?>