<?php

//  The purpose of this statement is to enforce that we don't try to include/require more than once (by accident).
//  If we're getting an error, it means somehow we're trying to require this script more than once,
//      and that means we have an error elsewhere in the application.

//Do we need this for a class? Getting some error, so commented

require_once "./backend/classes.php";

class APIBase
{
	protected $mysqli = null;
	protected $user = null;
	
	public function __construct($user, $mysqli) 
	{
		$this->user = $user;
		$this->mysqli = $mysqli;
	}
	
	//  DEPRECATED
	//      Call instead Session::reauthenticate()
	protected function exit_if_not_authenticated()
	{
		//Session::reauthenticate();
	}
}

?>