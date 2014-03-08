<?php

require "backend/connect.php";
require "backend/headers.php";
require "backend/support.php";

session_destroy();
session_unset();

if (isset ($_POST["handle"]) && isset ($_POST["password"]))
{
	$handle = urldecode($_POST["handle"]);
	$password = urldecode($_POST["password"]);
	
	if (!validate_handle($handle) || !validate_password($password))
	{
		echo jsonencode(NULL);
		exit;
	}
	
	$result = $mysqli->query(sprintf("SELECT user_id AS id, handle, email, name_given AS nameGiven, name_family AS nameFamily FROM users WHERE handle = '%s' AND pswd_hash = '%s'",
		$mysqli->escape_string($handle),
		$mysqli->escape_string(crypt($password))
	);
	
	if ($user_assoc = $result->fetch_assoc())
	{
		$result->close();
		
		session_start();
		$session = session_id();
		
		$user_assoc["id"] = intval($user_assoc["id"]);
		
		$mysqli->query(sprintf("UPDATE users SET session = '%s' WHERE user_id = %d",
			$mysqli->escape_string($session),
			intval($user_assoc["id"]);
		));
		
		echo jsonencode($user_assoc);
	}
	else
	{
		echo jsonencode(NULL);
	}
}

?>