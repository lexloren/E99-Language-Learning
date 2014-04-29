<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class XenoglossError
{
	private $code;
	public function code()
	{
		return $this->code;
	}
	
	private $description;
	public function description()
	{
		return $this->description;
	}
	
	public function __construct($code, $description)
	{
		$this->code = intval($code, 10);
		$this->description = $description;
	}
	
	public function __toString()
	{
		return "Error " . $this->code() . ": " . $this->description();
	}
}

class ErrorReporter
{
	const ERRCODE_UNKNOWN = 0;
	const ERRCODE_AUTHENTICATION = 1;
	const ERRCODE_PERMISSIONS = 2;
	const ERRCODE_DATABASE = 4;
	
	protected static function errors_push($description, $code = self::ERRCODE_UNKNOWN)
	{
		if (!static::$errors) static::$errors = array ();
		array_push(static::$errors, new XenoglossError($code, $description));
		return null;
	}
	public static function errors_pop()
	{
		if (!static::$errors) return null;
		return array_pop(static::$errors);
	}
	protected static function errors_describe($errors)
	{
		return $errors ? implode("\n", $errors) : null;
	}
	public static function errors_unset()
	{
		$errors = static::$errors;
		static::$errors = null;
		return self::errors_describe($errors);
	}
	public static function errors_get()
	{
		return self::errors_describe(static::$errors);
	}
	
	public static function reset()
	{
		static::errors_unset();
	}
}

?>