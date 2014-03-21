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
		if(!Session::reauthenticate())
			return;
	}
	
	public function delete()
	{
		if(!Session::reauthenticate())
			return;
	}
	
	public function lists()
	{
	
	}
	
	public function units()
	{
	
	}
	
	//  Maybe just call APIUnit.insert from here (?)
	public function units_add()
	{
	
	}
	
	//  Maybe just call APIUnit.delete from here (?)
	public function units_remove()
	{
	
	}
	
	//  DEPRECATED (?)
	//      I'm not sure whether we actually need this method.
	public function describe()
	{
		if(!Session::reauthenticate())
			return;
		Session::set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}	
}

?>