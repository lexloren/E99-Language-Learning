<?php

require "backend/connect.php";
require "backend/headers.php";
require "backend/support.php";

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
		exit_with_error("Invalid Session", "The user session is not valid. Please authenticate.");
	}
	
	$mysqli->query(sprintf("UPDATE users SET last_activity = CURRENT_TIMESTAMP WHERE session = '%s' AND handle = '%s'",
		$mysqli->escape_string(session_id()),
		$mysqli->escape_string($_SESSION["handle"])
	));
}
else
{
	exit_with_error("No Session", "The user is not logged in. Please authenticate.");
}

?>