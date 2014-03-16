<?php

/*
//  SESSION MANAGEMENT MOVED INTO "./BACKEND/CLASSES/SESSION.PHP"

if (!!session_id() && isset ($_SESSION["handle"]))
{
	$result = $mysqli->query(sprintf("SELECT * FROM users WHERE session = '%s' AND handle = '%s'",
		$mysqli->escape_string(session_id()),
		$mysqli->escape_string($_SESSION["handle"])
	));
	
	if (!$result || !($result->fetch_assoc()))
	{
		session_destroy();
		session_unset();
		Session::exit_with_error("Invalid Session", "The user session is not valid. Please authenticate.");
	}
	
	$mysqli->query(sprintf("UPDATE users SET last_activity = CURRENT_TIMESTAMP WHERE session = '%s' AND handle = '%s'",
		$mysqli->escape_string(session_id()),
		$mysqli->escape_string($_SESSION["handle"])
	));
}
else
{
	Session::exit_with_error("No Session", "The user is not logged in. Please authenticate.");
}
*/

//  Should be calling Session::reauthenticate() directly;
//      if we're still calling this script,
//      then something is wrong with the back-end setup.
exit;

?>