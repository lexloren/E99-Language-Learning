<?php

class Header
{
	private static $header_string = array(
		"json" =>  array(
			"Content-Type: application/json; charset=utf-8"
		),
		"csv"  => array(
			"Content-type: text/csv",
			"Content-Disposition: attachment; filename=researcher-data.csv",
			"Pragma: no-cache",
			"Expires: 0"
		)
	);
	private static $default_type = "json";

	public static function is_type_supported($header_type)
	{
		return array_key_exists($header_type, self::$header_string);
	}

	public static function get_header_strings($header_type)
	{
		if (array_key_exists($header_type, self::$header_string))
			$type = $header_type;
		else
			$type = self::$default_type;
		return self::$header_string[$type];
	}
}
?>
