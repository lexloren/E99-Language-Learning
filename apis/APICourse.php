<?php

require_once "./APIBase.php";
require_once "./backend/classes.php";

class APICourse  extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}
	
	public function describe()
	{
		Session::exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}	
}

?>