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
		if (self::validate_request($_POST, array ("email", "handle", "password")))
		{
			//  Validate the posted data
			$email = strtolower($_POST["email"]);
			$handle = strtolower($_POST["handle"]);
			$password = $_POST["password"];
			
			//  Finally, send the user information to the front end
			if (!($user = User::insert($email, $handle, $password)))
			{
				Session::get()->set_error_assoc("User Insertion", User::get_error_description());
			}
			else
			{
				Session::get()->set_result_assoc($user->assoc_for_json(false));
			}
		}
	}
	
	public function authenticate()
	{
		if (self::validate_request($_POST, array ("handle", "password")))
		{
			//  Should exit the script in either success or failure.
			Session::get()->authenticate($_POST["handle"], $_POST["password"]);
		}
	}
	
	public function find()
	{
		if (self::validate_request($_GET, "query"))
		{
			self::return_array_as_json(User::find($_GET["query"]));
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
		self::return_array_as_json(Session::get()->get_user()->get_lists());
	}
	
	
	public function update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		$user = Session::get()->get_user();
		
		$updates = 0;
			
		if (isset($_POST["email"]))
		{
			$updates += !!$user->set_email($_POST["email"]);
		}
		
		if (isset($_POST["name_given"]))
		{
			$updates += !!$user->set_name_given($_POST["name_given"]);
		}
		
		if (isset($_POST["name_family"]))
		{
			$updates += !!$user->set_name_family($_POST["name_family"]);
		}
		
		self::return_updates_as_json("User", User::get_error_description(), $updates ? $user->assoc_for_json() : null);
	}
	
	public function practice()
	{
		if (!Session::get()->reauthenticate()) return;

		//  Nirmal, do you check for session_user_can_read() on the lists requested for practice?
		$list_ids = array();
		if (isset($_GET["list_ids"]))
		{
			foreach (explode(",", $_GET["list_ids"]) as $list_id)
			{
				if (!($list = EntryList::select_by_id(intval($list_id, 10))))
		                {
                		        Session::get()->set_error_assoc("Unknown List", "Back end failed to select list with list_id = $list_id.");
					return;
	        	        }
				array_push($list_ids, $list->get_list_id());
			}
		}

		if (empty($list_ids))
		{
			Session::get()->set_error_assoc("Request Invalid", "User-practice get must include list_ids.");
		} else {
			$entries_count = isset($_GET["entries_count"]) ? $_GET["entries_count"] : 0;
			$practice = Practice::generate($list_ids, $entries_count);
			self::return_array_as_json($practice->get_entries());
		}
	}

	public function practice_response()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (!isset($_GET["entry_id"]) || !($entry = Entry::select_by_id(intval($_GET["entry_id"], 10))))
		{
			Session::get()->set_error_assoc("Unknown Entry", "Back end failed to select entry with entry_id = ".
							(isset($_GET["entry_id"]) ? $_GET["entry_id"] : ''));
		}
		else if (!isset($_GET["grade_id"]) || !($grade = Grade::select_by_id(intval($_GET["grade_id"], 10))))
		{
			Session::get()->set_error_assoc("Unknown Grade", "Back end failed to select grade with grade_id = ".
                                                        (isset($_GET["grade_id"]) ? $_GET["grade_id"] : ''));
		}
		else
		{
			$result = Practice::update_practice_response($_GET["entry_id"], $_GET["grade_id"]);
			if (!Session::get()->has_error())
			{
				Session::get()->set_result_assoc($result->assoc_for_json());
			}
		}
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
		self::return_array_as_json(Session::get()->get_user()->get_courses());
	}
	
	public function student_courses()
	{
		if (!Session::get()->reauthenticate()) return;
		self::return_array_as_json(Session::get()->get_user()->get_student_courses());
	}
	
	public function instructor_courses()
	{
		if (!Session::get()->reauthenticate()) return;
		self::return_array_as_json(Session::get()->get_user()->get_instructor_courses());
	}
}


?>
