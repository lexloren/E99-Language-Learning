<?php

class database
{
	public static function recreate_database($mysql_database)
	{
		//code from http://stackoverflow.com/questions/19751354/how-to-import-sql-file-in-mysql-database-using-php
		//http://stackoverflow.com/questions/9068767/php-mysql-create-database-if-not-exists
		
		$filename = './tools/recreatedb.sql';
		// MySQL host
		$mysql_host = '127.0.0.1';
		// MySQL username
		$mysql_username = 'root';
		// MySQL password
		$mysql_password = '';

		// Connect to MySQL server
		$link = mysql_connect($mysql_host, $mysql_username, $mysql_password) or die('Error connecting to MySQL server: ' . mysql_error());

		// Select database
		$db_selected = mysql_select_db($mysql_database);

		if (!$db_selected) 
		{
		  // If we couldn't, then it either doesn't exist, or we can't see it.
		  $sql = 'CREATE DATABASE '.$mysql_database;

		  if (mysql_query($sql, $link)) 
		  {
			  echo "Database ".$mysql_database." created successfully\n";
			  mysql_select_db($mysql_database);
		  }
		  else 
		  {
			  die ('Error creating database: '. mysql_error() . "\n");
		  }
		}

		mysql_query('SET FOREIGN_KEY_CHECKS=0');

		// Temporary variable, used to store current query
		$templine = '';
		// Read in entire file
		$lines = file($filename);
		// Loop through each line
		foreach ($lines as $line)
		{
			// Skip it if it's a comment
			if (substr($line, 0, 2) == '--' || $line == '')
				continue;

			// Add this line to the current segment
			$templine .= $line;
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';')
			{
				// Perform the query
				mysql_query($templine) or print('Error performing query \'<strong>' . $templine . '\': ' . mysql_error() . '<br /><br />');
				// Reset temp variable to empty
				$templine = '';
			}
		}
		
		mysql_close($link);
		
		$link = new mysqli($mysql_host, $mysql_username, $mysql_password, $mysql_database);

		return $link;
	}
}
?>




