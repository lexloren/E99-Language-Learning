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
	
	public function instructors()
	{
	
	}
	
	public function instructors_add()
	{
	
	}
	
	public function instructors_remove()
	{
	
	}
	
	public function students()
	{
	
	}
	
	public function students_add()
	{
	
	}
	
	public function students_remove()
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
	
	public function tests_add()
	{
	
	}
	
	public function tests_remove()
	{
	
	}
	
	//  DEPRECATED (?)
	//      I'm not sure whether we actually need this method.
	public function describe()
	{
		Session::exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}	
}

?>