<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIUnit  extends APIBase
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
	
	public function lists()
	{
	
	}
	
	public function lists_add()
	{
	
	}
	
	public function lists_remove()
	{
	
	}
	
	public function tests()
	{
	
	}
	
	/*
	//  Move these to test/insert and test/delete
	public function tests_add()
	{
	
	}
	
	public function tests_remove()
	{
	
	}
	*/
	
	/*
	//  DEPRECATED (?)
	//      I'm not sure whether we actually need this method.
	public function select()
	{
		Session::get()->set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}
	*/
}

?>