<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class UsersDirectory
{
	public static function look_up($query)
	{
		$mysqli = Connection::get_shared_instance();
		
		$query = $mysqli->escape_string($query);
		
		$result = $mysqli->query("SELECT * FROM users WHERE email LIKE '$query' OR handle LIKE '$query'");
		
		$users = array ();
		while (($result_assoc = $result->fetch_assoc()))
		{
			array_push($users, User::from_mysql_result_assoc($result_assoc));
		}
		return $users;
	}
}

?>