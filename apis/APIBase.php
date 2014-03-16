<?php

//  The purpose of this statement is to enforce that we don't try to include/require more than once (by accident).
//  If we're getting an error, it means somehow we're trying to require this script more than once,
//      and that means we have an error elsewhere in the application.

//Do we need this for a class? Getting some error, so commented
//if (isset ($apibase)) exit;
//$apibase = true;

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
	
	protected function exit_if_not_authenticated()
	{
		if (!isset($this->user))
			Session::exit_with_error("Authentication Needed", "User must authenticate for this resource");
	}
}

?>


