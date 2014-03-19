<?php

/*
//  SESSION MANAGEMENT MOVED INTO "./BACKEND/CLASSES/SESSION.PHP"

if (!!session_id() && strlen(session_id()) > 0)
{
	$mysqli->query(sprintf("UPDATE users SET session = '' WHERE session = '%s'",
		$mysqli->escape_string(session_id())
	));

	session_destroy();
	session_unset();
}
*/

require_once "./backend/connection.php";
require_once "./backend/support.php";
require_once "./backend/classes.php";

Session::deauthenticate();
Session::exit_with_result("Deauthentication", "The current session has ended.");

?>