<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIUser extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
	}

	public function authenticate()
	{
		if (isset ($_POST["handle"]) && isset ($_POST["password"]))
		{
			//  Should exit the script in either success or failure.
			Session::authenticate(strtolower(urldecode($_POST["handle"])), urldecode($_POST["password"]) );
		}
		else
		{
			Session::exit_with_error("Invalid Post", "Authentication post must include handle and password.");
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
			Session::exit_with_result($new_user->assoc_for_json());
		}
		else
		{
			Session::exit_with_error("Invalid Post", "Registration post must include email, handle, and password.");
		}
	}
	
	public function activate() 
	{
		Session::exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function reset_password() 
	{
		Session::exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function deauthenticate() 
	{
		Session::deauthenticate();
		self::exit_with_result("Deauthentication", "The current session has ended.");
	}
}


?>
