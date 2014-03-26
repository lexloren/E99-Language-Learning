<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class DatabaseRow
{
	private static $error_description = null;
	protected static function set_error_description($error_description)
	{
		self::$error_description = $error_description;
		return null;
	}
	public static function get_error_description()
	{
		return self::$error_description;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		return null;
	}
	
	//Commented. Getting error because function signature are not matching with derived objects
	/*
	public static function select_by_id($id)
	{
		return null;
	}
	
	public static function insert($id)
	{
		return null;
	}
	
	public function delete($id)
	{
		return $this;
	}*/
	
	public function assoc_for_json()
	{
		return null;
	}
}

?>