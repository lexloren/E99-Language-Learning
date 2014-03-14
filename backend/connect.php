<?php

if (isset ($mysqli)) exit;

//  Global variable for getting access to the database.
$mysqli = new mysqli("68.178.216.146", "cscie99", "Ina28@Waffle", "cscie99");

/* check connection */
if ($mysqli->connect_errno)
{
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit;
}

/* change character set to utf8 */
if (!$mysqli->set_charset("utf8"))
{
    printf("Error loading character set utf8: %s\n", $mysqli->error);
}

?>