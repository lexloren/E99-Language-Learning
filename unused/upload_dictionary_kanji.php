<?php

require "backend.php";

if (file_exists("kanji.txt"))
{
	$language_codes = array("jp", "en");
	$language_known = mysql_fetch_assoc(mysql_get_result_from_query(sprintf("SELECT * FROM languages WHERE lang_code = '%s'",
		mysql_real_escape_string($language_codes[1])
	)));
	$language_unknw = mysql_fetch_assoc(mysql_get_result_from_query(sprintf("SELECT * FROM languages WHERE lang_code = '%s'",
		mysql_real_escape_string($language_codes[0])
	)));
	
	$entries = file("kanji.txt");
	$values = array();
	foreach ($entries as $entry)
	{
		//  Until we perform array_shift() later,
		//      this array will start with the index word,
		//      not just the definitions.
		$parts = explode(" ", trim($entry));
		$index = array_shift($parts);
		array_shift($parts); // drop the part that gives the Unicode
		
		//  Go through all the annotations to find the Chinese pronunciations
		$pronunciations = array();
		for ($pinyin; !!preg_match("/^[0-9A-Z]/", $parts[0]); array_shift($parts))
		{
			if (strcmp(substr($parts[0], 0, 1), "Y") === 0)
			{
				array_push($pronunciations, substr($parts[0], 1));
			}
		}
		
		//  Go through the Japanese pronunciations
		while (count($parts) > 0
			&& !preg_match("/^\{/", $parts[0])
			&& strcmp(($pronunciation = array_shift($parts)), "T1") != 0)
		{
			array_push($pronunciations, $pronunciation);
		}
		
		$pronunciation_string = implode(" / ", $pronunciations);
		
		$definitions = array();
		while (count($parts) > 0 && !!preg_match("/\}$/", $parts[count($parts)-1]))
		{
			$definition = "";
			do
			{
				$definition = array_pop($parts) . ((strlen($definition) > 0) ? " " : "") . $definition;
			} while (strcmp(substr($definition, 0, 1), "{") !== 0);
			
			array_unshift($definitions, substr($definition, 1, strlen($definition) - 2));
		}
		
		foreach ($definitions as $definition)
		{
			$query = sprintf("INSERT INTO dictionary (lang_id_known, lang_id_unknw, lang_known, lang_unknw, pronunciation) VALUES %s",
					sprintf("(%d, %d, '%s', '%s', '%s')",
						intval($language_known["lang_id"], 10),
						intval($language_unknw["lang_id"], 10),
						mysql_real_escape_string(trim($definition)),
						mysql_real_escape_string(trim($index)),
						mysql_real_escape_string(trim($pronunciation_string))
					)
				);
			
			//print($query . "\n");
			mysql_query($query);
		}
		echo ".";
	}
}

?>