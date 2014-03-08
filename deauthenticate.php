<?php

require "backend/connect.php";
require "backend/headers.php";

if (!!session_id() && strlen(session_id()) > 0)
{
	$mysqli->query(sprintf("UPDATE users SET session = '' WHERE session = '%s'",
		$mysqli->escape_string(session_id()),
	));

	session_destroy();
	session_unset();
}

?>