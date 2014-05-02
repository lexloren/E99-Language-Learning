<?php

class Connection
{
	private static $mysqli = null;
	
	protected static function mysqli()
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
			printf("Error loading character set utf8: %s.\n", self::$mysqli->error);
		}

		return self::$mysqli;
	}
	
	public static function mysqli_test()
	{
		return !!static::mysqli();
	}
	
	private static $enforce_query_error_clearing = true;
	public static function enforce_query_error_clearing($enforce_query_error_clearing = null)
	{
		if ($enforce !== null) static::$enforce_query_error_clearing = $enforce_query_error_clearing;
		
		return static::$enforce_query_error_clearing;
	}
	
	private static $error_cleared = true;
	private static $transaction_level = 0;
	private static function transaction_push()
	{
		if (self::$transaction_level === 0)
		{
			self::mysqli()->query("START TRANSACTION");
			if (self::mysqli()->error) return null;
		}
		return ++ self::$transaction_level;
	}
	
	private static function transaction_abort()
	{
		if (self::$transaction_level > 0)
		{
			self::mysqli()->query("ROLLBACK");
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
			self::mysqli()->query("COMMIT");
			
			if (self::mysqli()->error)
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
	
	private static $query = null;
	public static function query($query = null)
	{
		if ($query === null) return static::$query;
		
		if (static::$enforce_query_error_clearing && !static::$error_cleared)
		{
			exit("SQL–error-handling error: Cannot submit query before having cleared for error from prior query.\n\tPrior query: " . static::$query . "\n\tThis query: $query\n");
		}
		
		static::$error_cleared = false;
		
		return static::mysqli()->query((static::$query = $query));
	}
	
	public static function query_result()
	{
		return static::mysqli()->result;
	}
	
	public static function query_error_clear()
	{
		static::$error_cleared = true;
		
		return static::mysqli()->error;
	}
	
	public static function escape($string)
	{
		return static::mysqli()->escape_string($string);
	}
	
	public static function query_insert_id()
	{
		return static::mysqli()->insert_id;
	}
	
	//Used for testing with a local db
	public static function set_mysqli($mysqli)
	{
		if (!!self::$mysqli) self::$mysqli->close();
		self::$mysqli = $mysqli;
	}
}

?>