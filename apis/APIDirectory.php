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
		if (!isset($_GET["query"]))
		{
			Session::get()->set_error_assoc("Invalid Request", "Directory get must include query (i.e., email or handle).");
		}
		
		$this->return_array_as_assoc_for_json(Directory::look_up($_GET["query"]));
	}
}
?>