<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIUser extends APIBase
{
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
				Session::get()->set_error_assoc("User Insertion", User::errors_unset());
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
		if (!Session::get()->reauthenticate()) return;
		
		if (($user = self::validate_selection_id($_POST, "user_id", "User"))
			|| ($user = Session::get()->get_user()))
		{
			Session::get()->set_result_assoc($user->json_assoc_detailed());
		}
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
		
		self::return_array_as_json(Session::get()->get_user()->lists(isset($_GET["course_ids"]) ? explode(",", $_GET["course_ids"]) : null));
	}
	
	public function student_courses_lists()
	{
		if (!Session::get()->reauthenticate()) return;
		
		$lists = array ();
		foreach (Session::get()->get_user()->courses_studied() as $course)
		{
			foreach ($course->lists() as $list)
			{
				if (!in_array($list, $lists)) array_push($lists, $list);
			}
		}
		
		self::return_array_as_json($lists);
	}
	
	public function sittings()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (!($sittings = Session::get()->get_user()->sittings()))
		{
			Session::get()->set_error_assoc("User-Sittings Selection", User::errors_unset());
		}
		else
		{
			if (isset($_GET["live"]))
			{
				$live = !!intval($_GET["live"], 10);
				foreach ($sittings as $s => $sitting)
				{
					if ($sitting->is_live() != $live) unset($sittings[$s]);
				}
			}
			
			self::return_array_as_json($sittings);
		}
	}
	
	public function languages()
	{
		if (!Session::get()->reauthenticate()) return;
		
		self::return_array_as_json(Session::get()->get_user()->languages());
	}
	
	//  needs back-end implementation
	public function languages_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (self::validate_request($_POST, array (0 => "langs")))
		{
			$user = Session::get()->get_user();
			
			if (Connection::transact(
				function () use ($user)
				{
					$errors = 0;
					
					$lang_codes = explode(",", $_POST["langs"]);
					
					$languages = array ();
					foreach ($lang_codes as $lang_code)
					{
						$lang_parts = explode(" ", $lang_code);
						$lang_years = count($lang_parts) == 2 ? array_pop($lang_parts) : null;
						$lang_code = array_shift($lang_parts);
						if ($lang_years !== null && strlen($lang_years) == 0)
						{
							$lang_years = null;
						}
						else $lang_years = intval($lang_years, 10);
						$languages[$lang_code] = $lang_years;
					}
					
					$user_languages = $user->languages();
					
					foreach ($languages as $lang_code => $lang_years)
					{
						if (($language = Language::select_by_code($lang_code)))
						{
							if (!in_array($language, $user_languages))
							{
								$errors += !$user->languages_add($language, $lang_years);
							}
							else
							{
								$errors += !$user->set_language_years($language, $lang_years);
							}
						}
						else $errors ++;
					}
					
					if ($errors) return null;
					
					return $user;
				}
			)) Session::get()->set_result_assoc($user->json_assoc());
			else Session::get()->set_error_assoc("User-Languages Addition", User::errors_unset());
		}
	}
	
	//  needs back-end implementation
	public function languages_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (self::validate_request($_POST, array (0 => "langs")))
		{
			$user = Session::get()->get_user();
			
			if (Connection::transact(
				function () use ($user)
				{
					$errors = 0;
					
					$lang_codes = explode(",", $_POST["langs"]);
					
					$languages = array ();
					foreach ($lang_codes as $lang_code)
					{
						$lang_parts = explode(" ", $lang_code);
						$lang_years = count($lang_parts) == 2 ? array_pop($lang_parts) : null;
						$lang_code = array_shift($lang_parts);
						if ($lang_years !== null && strlen($lang_years) == 0)
						{
							$lang_years = null;
						}
						else $lang_years = intval($lang_years, 10);
						$languages[$lang_code] = $lang_years;
					}
					
					$user_languages = $user->languages();
					
					foreach ($user_languages as $language)
					{
						if (in_array($language->get_lang_code(), $languages))
						{
							$errors += !$user->languages_remove($language);
						}
						else $errors ++;
					}
					
					if ($errors) return null;
					
					return $user;
				}
			)) Session::get()->set_result_assoc($user->json_assoc());
			else Session::get()->set_error_assoc("User-Languages Addition", User::errors_unset());
		}
	}
	
	public function update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		$user = Session::get()->get_user();
		
		if (Connection::transact(
			function () use ($user)
			{
				$errors = 0;
					
				if (isset($_POST["email"]))
				{
					$errors += !$user->set_email($_POST["email"]);
				}
					
				if (isset($_POST["status_id"]))
				{
					if (($status = Status::select_by_id($_POST["status_id"])))
					{
						$errors += !$user->set_status($status);
					}
					else $errors ++;
				}
				else if (isset($_POST["status"]))
				{
					if (($status = Status::select_by_description($_POST["status"])))
					{
						$errors += !$user->set_status($status);
					}
					else $errors ++;
				}
				
				if (isset($_POST["name_given"]))
				{
					$errors += !$user->set_name_given($_POST["name_given"]);
				}
				
				if (isset($_POST["name_family"]))
				{
					$errors += !$user->set_name_family($_POST["name_family"]);
				}
				
				if (isset($_POST["langs"]))
				{
					$lang_codes = explode(",", $_POST["langs"]);
					
					$languages = array ();
					foreach ($lang_codes as $lang_code)
					{
						$lang_parts = explode(" ", $lang_code);
						$lang_years = count($lang_parts) == 2 ? array_pop($lang_parts) : null;
						$lang_code = array_shift($lang_parts);
						if ($lang_years !== null && strlen($lang_years) == 0)
						{
							$lang_years = null;
						}
						else $lang_years = intval($lang_years, 10);
						$languages[$lang_code] = $lang_years;
					}
					
					$user_languages = $user->languages();
					
					foreach ($user_languages as $language)
					{
						if (!in_array($language->get_lang_code(), $languages))
						{
							$errors += !$user->languages_remove($language);
						}
					}
					
					foreach ($languages as $lang_code => $lang_years)
					{
						if (($language = Language::select_by_code($lang_code)))
						{
							if (!in_array($language, $user_languages))
							{
								$errors += !$user->languages_add($language, $lang_years);
							}
							else
							{
								$errors += !$user->set_language_years($language, $lang_years);
							}
						}
						else $errors ++;
					}
				}
				
				if (isset($_POST["password_old"]) && isset($_POST["password_new"]))
				{
					if ($user->check_password($_POST["password_old"]))
					{
						$errors += !$user->set_password($_POST["password_new"]);
					}
				}
				
				if ($errors) return null;
				
				return $user;
			}
		)) Session::get()->set_result_assoc($user->json_assoc());
		else Session::get()->set_error_assoc("User Modification", User::errors_unset());
	}
	
	public function practice()
	{
		if (!Session::get()->reauthenticate()) return;

		$list_ids = array ();
		if (self::validate_request($_GET, array ("list_ids", "practice_from", "practice_to")))
		{
			foreach (explode(",", $_GET["list_ids"]) as $list_id)
			{
				$list = EntryList::select_by_id(intval($list_id, 10));
				if (!$list)
                		        return Session::get()->set_error_assoc("Unknown List", "Back end failed to select list with list_id = $list_id.");
				else if($list->session_user_can_read())
                                        array_push($list_ids, $list->get_list_id());
			}
			$practice_from = str_replace("_", " ", strtolower($_GET["practice_from"]));
			$practice_to = str_replace("_", " ", strtolower($_GET["practice_to"]));
		}

		if (empty($list_ids) || !isset($practice_from) || !isset($practice_to))
		{
			Session::get()->set_error_assoc("Request Invalid", "User-practice get must include list_ids, practice_from & practce_to.");
		} else {
			$entries_count = isset($_GET["entries_count"]) ? $_GET["entries_count"] : 0;
			if (($practice_set = Practice::generate($list_ids, $practice_from, $practice_to, $entries_count)))
				self::return_array_as_json($practice_set);
                        else
                                Session::get()->set_error_assoc("Practice generate", Practice::errors_unset());
		}
	}

	public function practice_response()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($practice_entry = self::validate_selection_id($_POST, "practice_entry_id", "Practice")))
                {
			if (($grade = self::validate_selection_id($_POST, "grade_id", "Grade")))
			{
				if (!!$practice_entry && ($result = $practice_entry->update_practice_response($grade->get_grade_id())))
					return Session::get()->set_result_assoc($result->json_assoc());

				Session::get()->set_error_assoc("Practice Response", Practice::errors_unset());
			}
			else
				Session::get()->set_error_assoc("Unknown Grade", "Back end failed to select grade id");
		}
		else
			Session::get()->set_error_assoc("Unknown PracticeEntry", "Back end failed to select practice-entry");
	}
	
	//  Courses owned by the user
	public function courses()
	{
		if (!Session::get()->reauthenticate()) return;
		
		self::return_array_as_json(Session::get()->get_user()->courses());
	}
	
	public function student_courses()
	{
		if (!Session::get()->reauthenticate()) return;
		
		self::return_array_as_json(Session::get()->get_user()->courses_studied());
	}
	
	public function instructor_courses()
	{
		if (!Session::get()->reauthenticate()) return;
		
		self::return_array_as_json(Session::get()->get_user()->courses_instructed());
	}

	public function researcher_data_dump()
	{
		if (!Session::get()->reauthenticate()) return;

		$user = Session::get()->get_user();
		if($user->session_user_can_research())
		{
			if(!($data = Report::get_data_dump()))
			{
				Session::get()->set_error_assoc("Data Dump", Report::errors_unset());
			}
			return Session::get()->set_result_assoc($data);
		}
	}

	public function method_output_type($method)
	{
		$csv_output_methods = array(
			"researcher_data_dump"
		);
		if (in_array($method, $csv_output_methods))
		{
			return "csv";
		}
		return "json";
	}
}


?>
