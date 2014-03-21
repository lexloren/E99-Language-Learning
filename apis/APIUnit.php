<?php

require_once "./APIBase.php";
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
	
	public function add_instructor()
	{
	
	}
	
	public function add_student()
	{
	
	}
	
	public function remove_instructor()
	{
	
	}
	
	public function remove_student()
	{
	
	}
	
	public function add_list()
	{
	
	}
	
	public function remove_list()
	{
	
	}
	
	public function describe()
	{
		Session::set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}	
}

?>