<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIDictionary extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}
	
	public function query()
	{
		if (self::validate_request($_GET, array ("word", "langs")))
		{
			$langs = $_GET["langs"];		
			$pagination = null;
			if (isset($_GET["page_size"]) && isset($_GET["page_num"]))
			{
				$pagination = Array(
				'size' => $_GET["page_size"],
				'num' => $_GET["page_num"]
				);
			}
			
			$entries = Dictionary::look_up($_GET["word"], $langs, $pagination);

			if (isset($entries))
			{
				$entries_returnable = array ();
				foreach ($entries as $entry)
				{
					array_push($entries_returnable, $entry->assoc_for_json());
				}
				Session::get()->set_result_assoc($entries_returnable);
			}
		}
	}
}
?>