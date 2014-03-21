<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIUser extends APIBase
{
	public function __construct($user, $mysqli)
	{	
		parent::__construct($user, $mysqli);
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
			if (isset($new_user))
			{	
				Session::set_result_assoc($new_user->assoc_for_json());
			}
		}
		else
		{
			Session::set_error_assoc("Invalid Post", "Registration post must include email, handle, and password.");
		}
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
			Session::set_error_assoc("Invalid Post", "Authentication post must include handle and password.");
		}
	}
	
	public function activate()
	{
		Session::set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function password_reset()
	{
		Session::set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function deauthenticate() 
	{
		Session::deauthenticate();
		Session::set_result_assoc("Deauthentication", "The current session has ended.");
	}
	
	public function lists()
	{
		Session::reauthenticate();
		
		$lists = Session::get_user()->get_lists();
		
		$lists_returnable = array ();
		foreach ($lists as $list)
		{
			array_push($lists_returnable, $list->assoc_for_json());
		}
		
		Session::set_result_assoc($lists_returnable);
	}
}


?>