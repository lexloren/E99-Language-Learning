<?php

require "backend/connect.php";
require "backend/support.php";

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
	if (isset ($_GET["lang_codes"]))
	{
		$lang_codes_requested = array();
		
		$lang_codes_getted = explode(",", $_GET["lang_codes"]);
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
	$join1 = "(dictionary LEFT JOIN languages AS language_known ON dictionary.lang_id_known = language_known.lang_id)";
	$join2 = "$join1 LEFT JOIN languages AS language_unknw ON dictionary.lang_id_unknw = language_unknw.lang_id";

	//  We will convert the database columns to the proper format for sending JSON to the front end
	$columns = array(
		"dictionary.entry_id" => "entryId",
		"dictionary.user_id" => "userId",
		"language_known.lang_code" => "langCodeKnown",
		"language_unknw.lang_code" => "langCodeUnknown",
		"dictionary.lang_known" => "languageKnown",
		"dictionary.lang_unknw" => "languageUnknown",
		"dictionary.pronunciation" => "languageUnknownPronunciation",
		"CHAR_LENGTH(lang_unknw)" => "languageUnknownLength",
		"CHAR_LENGTH(lang_known)" => "languageKnownLength",
		"(lang_unknw != '$query' AND lang_known != '$query')" => "isNotExact"
	);
	
	$columnsSelected = array();
	foreach ($columns as $old => $new)
	{
		array_push($columnsSelected, "$old AS $new");
	}

	//  If the query contains three characters or fewer,
	//      then should we require exact matches to limit the results?
	$exact_matches_only = false; //strlen(urldecode($_GET["query"])) > 3;
	$wildcard = $exact_matches_only ? "" : "%%";
	
	//      Second, take all the pieces created above and run the SQL query
	$query = sprintf("SELECT %s FROM $join2 WHERE (lang_known LIKE '$wildcard%s$wildcard' OR lang_unknw LIKE '$wildcard%s$wildcard') AND (language_known.lang_code IN ('%s') AND language_unknw.lang_code IN ('%s')) ORDER BY isNotExact, languageUnknownLength, languageKnownLength",
		implode(", ", $columnsSelected),
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
	
	//      Finally, format the query results and send them to the front end
	exit_with_result(mysqli_fetch_all_assocs($result),
		array( "exactMatchesOnly" => $exact_matches_only )
	);
}

exit_with_result(NULL);

?>