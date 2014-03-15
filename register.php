<?php

/*
//  SESSION MANAGEMENT MOVED INTO "./BACKEND/CLASSES/SESSION.PHP"

require "backend/connect.php";
require "backend/support.php";

require "deauthenticate.php";
*/

/*
//  FUNCTIONALITY MOVED INTO "./BACKEND/CLASSES/USER.PHP"

if (isset ($_POST["email"]) && isset ($_POST["handle"]) && isset ($_POST["password"]))
{
	//  Validate the posted data
	$email = strtolower(urldecode($_POST["email"]));
	$handle = strtolower(urldecode($_POST["handle"]));
	$password = urldecode($_POST["password"]);
	//$name_given =
	//$name_family =
	
	if (!validate_email($email))
	{
		exit_with_error("Invalid Email", "Email must conform to the standard pattern.");
	}
	
	if (!validate_password($password))
	{
		exit_with_error("Invalid Password", "Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character.");
	}
	
	if (!validate_handle($handle))
	{
		exit_with_error("Invalid Handle", "Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.");
	}
	
	//  Check whether requested handle conflicts with any existing handle
	$result = $mysqli->query(sprintf("SELECT * FROM users WHERE handle = '%s'",
		$mysqli->escape_string($handle)
	));
	
	if ($existing_user = $result->fetch_assoc())
	{
		exit_with_error("Handle Conflict", "The requested handle is already taken.");
	}
	$result->close();
	
	
	//  Good to go, so insert the new user
	$mysqli->query(sprintf("INSERT INTO users (handle, email, pswd_hash) VALUES ('%s', '%s', PASSWORD('%s'))",
		$mysqli->escape_string($handle),
		$mysqli->escape_string($email),
		$mysqli->escape_string($password)
	));
	
	$result = $mysqli->query(sprintf("SELECT user_id AS id, handle, email, name_given AS nameGiven, name_family AS nameFamily FROM users WHERE handle = '%s'",
		$mysqli->escape_string($handle)
	));
	
	//  Just make sure we actually created the user
	if (!$result)
	{
		exit_with_error("Unknown Error", "The back end unexpectedly failed to create the user.");
	}
	$user_assoc = $result->fetch_assoc();
	$result->close();
	
	//  Finally, send the user information to the front end
	exit_with_result($user_assoc);
}

exit_with_error("Invalid Post", "Registration post must include email, handle, and password.");
*/

require_once "./backend/connection.php";
require_once "./backend/support.php";
require_once "./backend/classes.php";

if (isset ($_POST["email"]) && isset ($_POST["handle"]) && isset ($_POST["password"]))
{
	//  Validate the posted data
	$email = strtolower(urldecode($_POST["email"]));
	$handle = strtolower(urldecode($_POST["handle"]));
	$password = urldecode($_POST["password"]);
	
	$new_user = User::insert($email, $handle, $password);
	
	//  Finally, send the user information to the front end
	exit_with_result($new_user->assoc_for_json());
}

exit_with_error("Invalid Post", "Registration post must include email, handle, and password.");

?>