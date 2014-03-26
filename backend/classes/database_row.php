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
	
	public static function assoc_contains_keys($assoc, $keys)
	{
		if (!isset($assoc) || !$assoc || !is_array($assoc))
		{
			return self::set_error_description("Invalid result_assoc: " . $assoc . ".");
		}
		
		$keys_missing = array_diff($keys, array_keys($assoc));
		
		if (count($keys_missing) > 0)
		{
			return self::set_error_description("Missing keys in result_assoc: " . implode(", ", $keys_missing) . ".");
		}
		
		return true;
	}
	
	public function assoc_for_json()
	{
		return null;
	}
}

?>