<?php

require_once "./APIBase.php";
require_once "./backend/classes.php";

class APICourse  extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}
	
	public function insert()
	{
	
	}
	
	public function delete()
	{
	
	}
	
	public function describe()
	{
		Session::set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}	
}

?>