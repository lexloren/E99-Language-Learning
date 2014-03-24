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
				Session::get()->set_result_assoc($new_user->assoc_for_json());
			}
		}
		else
		{
			Session::get()->set_error_assoc("Invalid Post", "Registration post must include email, handle, and password.");
		}
	}
	
	public function authenticate()
	{
		if (isset($_POST["handle"]) && isset($_POST["password"]))
		{
			//  Should exit the script in either success or failure.
			Session::get()->authenticate(strtolower($_POST["handle"]), $_POST["password"]);
		}
		else
		{
			Session::get()->set_error_assoc("Invalid Post", "Authentication post must include handle and password.");
		}
	}
	
	public function activate()
	{
		Session::get()->set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function password_reset()
	{
		Session::get()->set_error_assoc("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function deauthenticate() 
	{
		Session::get()->deauthenticate();
		Session::get()->set_result_assoc("Deauthentication", "The current session has ended.");
	}
	
	public function lists()
	{
		if (!Session::get()->reauthenticate()) return;
		$this->return_array_as_assoc_for_json(Session::get()->get_user()->get_lists());
	}
	
	
	public function update()
	{
		//  email
		//  handle
		//  name_given
		//  name_family
	}
	
	public function practice()
	{
		if (!Session::reauthenticate()) return;

		if (!isset($_GET["list_ids"]))
		{
			Session::set_error_assoc("Invalid Get", "User-practice get must include list_ids.");
		}
		//  TO FIX: SHOULDN'T PASS A COMMA-DELIMITED LIST AS AN ARGUMENT
		return UserPractice::get_practice_entries($_GET["list_ids"], $_GET["entries_count"]);
	}

	public function practice_response()
	{
		if (!Session::reauthenticate()) return;

		if (!isset($_GET["entry_id"]) || !isset($_GET["grade_id"]))
		{
			Session::set_error_assoc("Invalid Post", "User-practice-response must include entry_id and grade_id.");
		}
		//  TO FIX: SHOULDN'T PASS A COMMA-DELIMITED LIST AS AN ARGUMENT
		UserPractice::update_practice_response($_GET["entry_id"], $_GET["grade_id"]);
		//  WHAT ARE WE RETURNING?
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
		if (!Session::get()->reauthenticate()) return;
		$this->return_array_as_assoc_for_json(Session::get()->get_user()->get_courses());
	}
	
	public function student_courses()
	{
		if (!Session::get()->reauthenticate()) return;
		$this->return_array_as_assoc_for_json(Session::get()->get_user()->get_student_courses());
	}
	
	public function instructor_courses()
	{
		if (!Session::get()->reauthenticate()) return;
		$this->return_array_as_assoc_for_json(Session::get()->get_user()->get_instructor_courses());
	}
}


?>