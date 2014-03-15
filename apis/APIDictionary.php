<?php

require_once "./apis/APIBase.php";
require_once "./backend/support.php";
require_once "./backend/classes/session.php";
require_once "./backend/classes/user.php";

class APIDictionary extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}
}
?>

