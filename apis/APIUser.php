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
				Session::get()->set_error_assoc("User Insertion", User::unset_error_description());
			}
			else
			{
				Outbox::send($user, $user->get_email(), "Xenogloss: Thanks for registering!", "Dear " . $user->get_handle() . ",\n\nThank you for registering to teach and learn languages with Xenogloss.\n\nYours,\nThe Xenogloss Team");
				Session::get()->set_result_assoc($user->json_assoc(false));
			}
		}
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate() || !($user = Session::get()->get_user())) return;
		
		Session::get()->set_result_assoc($user->detailed_json_assoc(false));
	}
	
	public function authenticate()
	{
		if (self::validate_request($_POST, array ("handle", "password")))
		{
			//  Should exit the script in either success or failure.
			Session::get()->authenticate($_POST["handle"], $_POST["password"]);
		}
		else if (self::validate_request($_POST, array ("email", "password")))
		{
			Session::get()->authenticate($_POST["email"], $_POST["password"]);
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
	
	public function languages()
	{
		if (!Session::get()->reauthenticate()) return;
		self::return_array_as_json(Session::get()->get_user()->get_languages());
	}
	
	//  needs back-end implementation
	public function languages_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (self::validate_request($_POST, array ("langs", "langs")))
		{
			$user = Session::get()->get_user();
			
			$updates = 0;
			
			foreach (explode(",", $_POST["langs"]) as $lang_code)
			{
				if (($language = Language::select_by_code($lang_code)))
				{
					$updates += !!$user->languages_add($language);
				}
			}
		
			self::return_updates_as_json("User", trim(Language::unset_error_description() . "\n\n". User::unset_error_description()), $updates ? $user->json_assoc() : null);
		}
	}
	
	//  needs back-end implementation
	public function languages_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (self::validate_request($_POST, array ("langs", "langs")))
		{
			$user = Session::get()->get_user();
			
			$updates = 0;
			
			foreach (explode(",", $_POST["langs"]) as $lang_code)
			{
				if (($language = Language::select_by_code($lang_code)))
				{
					$updates += !!$user->languages_remove($language);
				}
			}
		
			self::return_updates_as_json("User", trim(Language::unset_error_description() . "\n\n". User::unset_error_description()), $updates ? $user->json_assoc() : null);
		}
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
			
		if (isset($_POST["status_id"]))
		{
			if (($status = Status::select_by_id($_POST["status_id"])))
			{
				$updates += !!$user->set_status($status);
			}
		}
		else if (isset($_POST["status"]))
		{
			if (($status = Status::select_by_description($_POST["status"])))
			{
				$updates += !!$user->set_status($status);
			}
		}
		
		if (isset($_POST["name_given"]))
		{
			$updates += !!$user->set_name_given($_POST["name_given"]);
		}
		
		if (isset($_POST["name_family"]))
		{
			$updates += !!$user->set_name_family($_POST["name_family"]);
		}
		
		if (isset($_POST["langs"]))
		{
			$lang_codes = explode(",", $_POST["langs"]);
			$user_languages = $user->get_languages();
			
			foreach ($user_languages as $language)
			{
				if (!in_array($language->get_lang_code(), $lang_codes))
				{
					$updates += !!$user->languages_remove($language);
				}
			}
			
			foreach ($lang_codes as $lang_code)
			{
				if (($language = Language::select_by_code($lang_code)))
				{
					if (!in_array($language, $user_languages))
					{
						$updates += !!$user->languages_add($language);
					}
				}
			}
		}
		
		if (isset($_POST["password_old"]) && isset($_POST["password_new"]))
		{
			if ($user->check_password($_POST["password_old"]))
			{
				$updates += !!$user->set_password($_POST["password_new"]);
			}
		}
		
		self::return_updates_as_json("User", User::unset_error_description(), $updates ? $user->json_assoc() : null);
	}
	
	public function practice()
	{
		if (!Session::get()->reauthenticate()) return;

		//  Nirmal, do you check for session_user_can_read() on the lists requested for practice?
		$list_ids = array ();
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
		
		if (!isset($_POST["entry_id"]) || !($entry = Entry::select_by_id(intval($_POST["entry_id"], 10))))
		{
			Session::get()->set_error_assoc("Unknown Entry", "Back end failed to select entry with entry_id = ".
							(isset($_POST["entry_id"]) ? $_POST["entry_id"] : ''));
		}
		else if (!isset($_POST["grade_id"]) || !($grade = Grade::select_by_id(intval($_POST["grade_id"], 10))))
		{
			Session::get()->set_error_assoc("Unknown Grade", "Back end failed to select grade with grade_id = ".
                                                        (isset($_POST["grade_id"]) ? $_POST["grade_id"] : ''));
		}
		else
		{
			$result = Practice::update_practice_response($_POST["entry_id"], $_POST["grade_id"]);
			if (!Session::get()->has_error())
			{
				Session::get()->set_result_assoc($result->json_assoc());
			}
		}
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
