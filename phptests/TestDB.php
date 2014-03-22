<?php

require_once './backend/connection.php';
require_once './tools/database.php';

class TestDB
{
	public static $email = 'email@domain.com';
	public static $handle = 'username';
	public static $password = 'P@ssword1';
	public static $name_family = 'SomeFamily';
	public static $name_given = 'SomeGiven';
	public $link = null;

	private function __construct()
	{
	}
	
	public static function create()
	{
		$testdb = new TestDB();
		
		$link = database::recreate_database('cscie99test');
		$testdb->link = $link;
		

		Connection::set_shared_instance($testdb->link);
		
		$link->query(sprintf("INSERT INTO users (handle, email, pswd_hash, name_given, name_family) VALUES ('%s', '%s', PASSWORD('%s'), '%s', '%s')",
			$link->escape_string(self::$handle),
			$link->escape_string(self::$email),
			$link->escape_string(self::$password),
			$link->escape_string(self::$name_given),
			$link->escape_string(self::$name_family)
		));
		
		return $testdb;
	}
	
}
?>