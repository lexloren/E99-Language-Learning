<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIEntry extends APIBase
{
	public function __construct($user, $mysqli)
	{
		parent::__construct($user, $mysqli);
	}
	
	public function update()
	{
		// word_0
		// word_1
		// word_1_pronun
	}
	
	public function annotations()
	{
	
	}
	
	/*
	//  For consistency, let's move these to
	//      annotation/insert and annotation/remove
	public function annotations_add()
	{
		
	}
	
	public function annotations_remove()
	{
		
	}*/
}
?>
