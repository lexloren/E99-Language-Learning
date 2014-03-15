<?php

class Connect
{
	private static $mysqli = null;
	public static function get()
	{
		if (isset (self::$mysqli))
			return self::$mysqli;

		//  Global variable for getting access to the database.
		self::$mysqli = new mysqli("68.178.216.146", "cscie99", "Ina28@Waffle", "cscie99");

		/* check connection */
		if (self::$mysqli->connect_errno)
		{
			printf("Connect failed: %s\n", $mysqli->connect_error);
			exit;
		}

		/* change character set to utf8 */
		if (!self::$mysqli->set_charset("utf8"))
		{
			printf("Error loading character set utf8: %s\n", self::$mysqli->error);
		}

		return self::$mysqli;
	}
}
?>