<?php

class Connection
{
	private static $mysqli = null;
	
	public static function get_shared_instance()
	{
		if (!!self::$mysqli) return self::$mysqli;

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
	
	private static $transaction_level = 0;
	private static function transaction_push()
	{
		if (self::$transaction_level === 0)
		{
			self::get_shared_instance()->query("START TRANSACTION");
			if (self::get_shared_instance()->error) return null;
		}
		return ++ self::$transaction_level;
	}
	
	private static function transaction_abort()
	{
		if (self::$transaction_level > 0)
		{
			self::get_shared_instance()->query("ROLLBACK");
			self::$transaction_level = 0;
		}
		
		return null;
	}
	
	private static function transaction_pop($return)
	{
		if ($return === null) return self::transaction_abort();
		
		if (!(self::$transaction_level > 0))
		{
			exit("SQL-transaction nesting error: Cannot pop when transaction_level == " . self::$transaction_level . ".\n");
		}
		
		if (self::$transaction_level === 1)
		{
			self::get_shared_instance()->query("COMMIT");
			
			if (self::get_shared_instance()->error)
			{
				return self::transaction_abort();
			}
		}
		
		self::$transaction_level --;
		
		return $return;
	}
	
	public static function transact($closure)
	{
		if (self::transaction_push() !== null)
		{
			return self::transaction_pop($closure());
		}
		
		return null;
	}
	
	//Used for testing with a local db
	public static function set_shared_instance($mysqli_new)
	{
		if (!!self::$mysqli) self::$mysqli->close();
		self::$mysqli = $mysqli_new;
	}
}

?>