<?php

require_once './backend/connection.php';
require_once './tools/database.php';

class TestDB
{
	public static $user_id;
	public static $email = 'email@domain.com';
	public static $handle = 'username';
	public static $password = 'P@ssword1';
	public static $name_family = 'SomeFamily';
	public static $name_given = 'SomeGiven';
	public static $session = 'somesessionid';
	public static $list_name = 'somelist';
	public static $list_id;
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
	
		self::add_user($link);
		self::add_list($link);
		
		return $testdb;
	}
	
	private static function add_user($link)
	{
		$link->query(sprintf("INSERT INTO users (handle, email, pswd_hash, name_given, name_family, session) VALUES ('%s', '%s', PASSWORD('%s'), '%s', '%s', '%s')",
			$link->escape_string(self::$handle),
			$link->escape_string(self::$email),
			$link->escape_string(self::$password),
			$link->escape_string(self::$name_given),
			$link->escape_string(self::$name_family),
			$link->escape_string(self::$session)
		));

		self::$user_id = $link->insert_id;
	}

	private static function add_list($link)
	{
		$link->query(sprintf("INSERT INTO lists (user_id, list_name) VALUES (%d, '%s')",
			self::$user_id,
			$link->escape_string(self::$list_name)
		));

		self::$list_id = $link->insert_id;
	}

}
	
	
	
	
	

?>