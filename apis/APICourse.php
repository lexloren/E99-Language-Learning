<?php

require_once "./APIBase.php";
require_once "./backend/support.php";

class APICourse  extends APIBase
{
	public function __construct()
	{	
		parent::__construct();
	}
	
	public function describe()
	{
		exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}	
}

?>