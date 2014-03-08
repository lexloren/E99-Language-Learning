<?php

require "backend.php";

$dictionary_file_names = array("jp–en"); //, "jp.txt");

$index_delimiter = null;

foreach ($dictionary_file_names as $dictionary_file_name)
{
	if (file_exists($dictionary_file_name))
	{
		$language_codes = explode("–", $dictionary_file_name);
		$language_known = mysql_fetch_assoc(mysql_get_result_from_query(sprintf("SELECT * FROM languages WHERE lang_code = '%s'",
			mysql_real_escape_string($language_codes[1])
		)));
		$language_unknw = mysql_fetch_assoc(mysql_get_result_from_query(sprintf("SELECT * FROM languages WHERE lang_code = '%s'",
			mysql_real_escape_string($language_codes[0])
		)));
		
		$entries = file($dictionary_file_name);
		$values = array();
		foreach ($entries as $entry)
		{
			//  Until we perform array_shift() later,
			//      this array will start with the index word,
			//      not just the definitions.
			$definitions = explode("/", $entry);
			array_pop($definitions);
			$indices = explode(" [", array_shift($definitions));
			$pronunciation = (count($indices) > 1) ? trim(array_pop($indices), " \t\n\r\0\x0B\[\]") : "";
			
			if (!is_null($index_delimiter))
			{
				$indices = explode($index_delimiter, $indices[0]);
			}
			
			foreach ($indices as $index)
			{
				foreach ($definitions as $definition)
				{
					/*array_push($values,
						sprintf("(%d, %d, '%s', '%s', '%s')",
							intval($language_known["lang_code"], 10),
							intval($language_unknw["lang_code"], 10),
							mysql_real_escape_string(trim($definition)),
							mysql_real_escape_string(trim($index)),
							mysql_real_escape_string($pronunciation)
						)
					);*/
					
					$query = sprintf("INSERT INTO dictionary (lang_id_known, lang_id_unknw, lang_known, lang_unknw, pronunciation) VALUES %s",
							sprintf("(%d, %d, '%s', '%s', '%s')",
								intval($language_known["lang_id"], 10),
								intval($language_unknw["lang_id"], 10),
								mysql_real_escape_string(trim($definition)),
								mysql_real_escape_string(trim($index)),
								mysql_real_escape_string($pronunciation)
							)
						);
					
					//print($query . "\n");
					mysql_query($query);
				}
			}
			echo ".";
		}
		/*mysql_perform_query(sprintf("INSERT INTO entries (lang_id_known, lang_id_unknw, lang_known, lang_unknw, pronunciation) VALUES %s", implode(",",$values)));*/
	}
}

//session_adjourn();

?>