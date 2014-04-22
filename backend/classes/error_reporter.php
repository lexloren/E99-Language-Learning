<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class ErrorReporter
{
	protected static function set_error_description($error_description)
	{
		static::$error_description = (!!static::$error_description ? static::$error_description . "\n\n" : "") . $error_description;
		return null;
	}
	public static function unset_error_description()
	{
		$error_description = static::$error_description;
		static::$error_description = null;
		return $error_description;
	}
	public static function get_error_description()
	{
		return static::$error_description;
	}
	
	public static function reset()
	{
		return static::unset_error_description();
	}
}

?>