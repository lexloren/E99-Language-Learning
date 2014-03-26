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
				Session::get()->set_result_assoc($new_user->assoc_for_json(false));
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
	
	public function find()
	{
		if (!isset($_GET["query"]))
		{
			Session::get()->set_error_assoc("Invalid Request", "Find-user get must include query (which contains email or handle).");
		}
		
		$this->return_array_as_assoc_for_json(UsersDirectory::look_up($_GET["query"]));
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
	
	//  SHOULD THIS GO IN A SEPARATE API? WHY UNDER USER?
	public function practice()
	{
		if (!Session::get()->reauthenticate()) return;

		$list_ids = array();
		foreach (explode(",", $_GET["list_ids"]) as $list_id)
		{
			if (!($list = EntryList::select_by_id(intval($list_id, 10))))
	                {
                	        Session::get()->set_error_assoc("Unknown List", "Back end failed to select list with list_id = $list_id.");
				return;
        	        }
			array_push($list_ids, $list->get_list_id());
		}

		if (empty($list_ids))
		{
			Session::get()->set_error_assoc("Invalid Get", "User-practice get must include list_ids.");
		} else {
			$entry_set = Practice::get_practice_entries($list_ids, $_GET["entries_count"]);
			Session::get()->set_result_assoc($entry_set);
		}
		return;
	}

	public function practice_response()
	{
		if (!Session::get()->reauthenticate()) return;

		if (!isset($_GET["entry_id"]) || !($entry = Entry::select_by_id(intval($_GET["entry_id"], 10))))
                {
                        Session::get()->set_error_assoc("Unknown Entry", "Back end failed to select entry with entry_id = ".$_GET["entry_id"]);
                }
		else if (!isset($_GET["grade_id"]) || !($grade = Grade::select_by_id(intval($_GET["grade_id"], 10))))
                {
                        Session::get()->set_error_assoc("Unknown Grade", "Back end failed to select grade with grade_id = ".$_GET["grade_id"]);
                }
		else
		{
			$result = Practice::update_practice_response($_GET["entry_id"], $_GET["grade_id"]);
			if (!Session::get()->has_error())
			{
				Session::get()->set_result_assoc($result);
			}
		}
		return;
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
