<?php

require_once 'APIBase.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/backend/support.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/backend/classes/session.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/backend/classes/user.php';

class APIUser extends APIBase
{
	public function __construct() {	
		parent::__construct();
	}

	public function authenticate()
	{
		if (isset ($_POST["handle"]) && isset ($_POST["password"]))
		{
			//  Should exit the script in either success or failure.
			Session::authenticate(
				strtolower(urldecode($_POST["handle"])),
				urldecode($_POST["password"])
			);
		}
	}
	public function register()
	{
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
		else
			exit_with_error("Invalid Post", "Registration post must include email, handle, and password.");
	}
	
	public function activate() 
	{
		exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function reset_password() 
	{
		exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function deauthenticate() 
	{
		exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
}
?>

