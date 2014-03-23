<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

$mysqli = Connection::get_shared_instance();
//  Get all the languages available in the application
$result = $mysqli->query("SELECT * FROM languages");
if (!$result)
{
	Session::get()->exit_with_error("Database Error", $mysqli->error);
}
$languages_available = mysqli_fetch_all_assocs($result);

//  Create a dictionary mapping language codes to language identifiers
$lang_codes_dictionary = array();
foreach ($languages_available as $language_available)
{
	$lang_codes_dictionary[$language_available["lang_code"]] = $language_available["lang_id"];
}

//  Perform the dictionary search, if requested
if (isset($_GET["query"]))
{
	//  Limit the search to certain languages, if requested
	if (isset($_GET["langs"]))
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
	
	//  Use pagination only if GET includes both page size and page number
	$pagination = null;
	if (isset($_GET["page_size"]) && isset($_GET["page_num"]))
	{
		$pagination = array(
			"size" => intval($_GET["page_size"]),
			"num" => intval($_GET["page_num"])
		);
	}
	
	//  Perform the database query
	$entries_matching = Dictionary::look_up(
		$_GET["query"],
		$lang_codes_requested,
		$pagination
	);
	
	//  Convert the objects to associative arrays for returning JSON to the front end
	$entries_returnable = array();
	while (($entry = array_shift($entries_matching)))
	{
		array_push($entries_returnable, $entry->assoc_for_json());
	}
	
	//      Finally, format the query results and send them to the front end
	Session::get()->exit_with_result($entries_returnable, array (
		"entriesCount" => Dictionary::$look_up_last_count,
		"pageSize" => Dictionary::$page_size,
		"pageNum" => Dictionary::$page_num
	));
}

Session::get()->exit_with_error("Missing Query", "The call specified no query.");

?>