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
		if (isset($_POST["email"]) && isset($_POST["handle"]) && isset($_POST["password"]))
		{
			//  Validate the posted data
			$email = strtolower($_POST["email"]);
			$handle = strtolower($_POST["handle"]);
			$password = $_POST["password"];
			
			//  Finally, send the user information to the front end
			if (!!($new_user = User::insert($email, $handle, $password)))
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
		if (isset($_POST["handle"]) && isset($_POST["password"]))
		{
			//  Should exit the script in either success or failure.
			Session::authenticate(strtolower($_POST["handle"]), $_POST["password"]);
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
		if (!Session::reauthenticate()) return;
		$this->return_array_as_assoc_for_json(Session::get_user()->get_lists());
	}
	
	public function update()
	{
		//  email
		//  handle
		//  name_given
		//  name_family
	}
	
	//  needs back-end implementation
	public function languages()
	{
	
	}
	
	//  needs back-end implementation
	public function languages_add()
	{
	
	}
	
	//  needs back-end implementation
	public function languages_remove()
	{
	
	}
	
	//  Courses owned by the user
	public function courses()
	{
		if (!Session::reauthenticate()) return;
		$this->return_array_as_assoc_for_json(Session::get_user()->get_courses());
	}
	
	public function student_courses()
	{
		if (!Session::reauthenticate()) return;
		$this->return_array_as_assoc_for_json(Session::get_user()->get_student_courses());
	}
	
	public function instructor_courses()
	{
		if (!Session::reauthenticate()) return;
		$this->return_array_as_assoc_for_json(Session::get_user()->get_instructor_courses());
	}
}


?>