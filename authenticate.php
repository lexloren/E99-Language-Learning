<?php

require "backend/connect.php";
require "backend/headers.php";
require "backend/support.php";

require "deauthenticate.php";

if (isset ($_POST["handle"]) && isset ($_POST["password"]))
{
	//  Validate the posted data
	$handle = strtolower(urldecode($_POST["handle"]));
	$password = urldecode($_POST["password"]);
	
	if (!validate_password($password))
	{
		exit_with_error("Invalid Password", "Password must consist of between 6 and 31 (inclusive) characters containing at least one non-alphanumeric character.");
	}
	
	if (!validate_handle($handle))
	{
		exit_with_error("Invalid Handle", "Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
	}
	
	//  See whether we can authenticate with the handle and password posted
	$result = $mysqli->query(sprintf("SELECT user_id AS id, handle, email, name_given AS nameGiven, name_family AS nameFamily FROM users WHERE handle = '%s' AND pswd_hash = '%s'",
		$mysqli->escape_string($handle),
		$mysqli->escape_string(crypt($password, $password))
	));
	
	if ($user_assoc = $result->fetch_assoc())
	{
		$result->close();
		
		session_start();
		$session = session_id();
		
		$user_assoc["id"] = intval($user_assoc["id"]);
		
		//  Start a PHP session (using cookies), and update the database accordingly
		$mysqli->query(sprintf("UPDATE users SET session = '%s' WHERE user_id = %d",
			$mysqli->escape_string($session),
			$user_assoc["id"]
		));
		
		//  Tell the front end that we succeeded in authenticating
		exit_with_result($user_assoc);
	}
	else
	{
		exit_with_error("Invalid Credentials", "The handle and password entered match no users in the database.");
	}
}

exit_with_error("Invalid Post", "Authentication post must include handle and password.");

?>