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
		Session::get()->set_error_assoc("API Deprecation", "Please use entry_find.php?query=*[&langs=__,__...].");
	}
}
?>