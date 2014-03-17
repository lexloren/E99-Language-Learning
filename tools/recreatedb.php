<?php

	require_once './tools/database.php';
	
		
	$link = database::recreate_database('cscie99');
	if (isset($link))
	{
		$link->close();
		echo "database created successfully";
	}
	else
		echo "Failed to create database";
?>




