<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APIUnit extends APIBase
{
	public function insert()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($course = self::validate_selection_id($_POST, "course_id", "Course")))
		{
			if (($unit = Connection::transact(
				function () use ($course)
				{
					$name = isset($_POST["name"]) && strlen($_POST["name"]) > 0 ? $_POST["name"] : null;
					$timeframe = isset($_POST["open"]) && isset($_POST["close"]) ? new Timeframe($_POST["open"], $_POST["close"]) : null;
					$message = isset($_POST["message"]) && strlen($_POST["message"]) > 0 ? $_POST["message"] : null;
					
					if (!($unit = Unit::insert($course->get_course_id(), $name, $timeframe, $message))) return null;
					
					if (isset($_POST["list_ids"]))
					{
						$list_ids = explode(",", $_POST["list_ids"]);
						
						foreach ($list_ids as $list_id)
						{
							if (($list = EntryList::select_by_id($list_id))
								&& $list->session_user_can_read())
							{
								if (!$unit->lists_add($list)) return null;
							}
							else return null;
						}
					}
					
					$unit->get_course()->students_notify("Unit Inserted", "An instructor has inserted a unit into this course.");
					
					return $unit;
				}
			))) Session::get()->set_result_assoc($unit->json_assoc());
			else Session::get()->set_error_assoc("Unit Insertion", Unit::errors_unset());
		}
	}
	
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_GET, "unit_id", "Unit")))
		{
			Session::get()->set_result_assoc($unit->json_assoc_detailed());
		}
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (!$unit->delete())
			{
				Session::get()->set_error_assoc("Unit Deletion", Unit::errors_unset());
			}
			else
			{
				$unit->get_course()->students_notify("Unit Deleted", "An instructor has deleted a unit from this course.");
				Session::get()->set_result_assoc($unit->json_assoc());
			}
		}
	}
	
	public function update()
	{
		//  name
		//  num
		
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (Connection::transact(
				function () use ($unit)
				{
					$errors = 0;
					
					if (isset($_POST["name"]))
					{
						$errors += !$unit->set_name($_POST["name"]);
					}
					
					if (isset($_POST["num"]))
					{
						$errors += !$unit->set_number($_POST["num"]);
					}
					
					if (isset($_POST["message"]))
					{
						$errors += !$unit->set_message($_POST["message"]);
					}
					
					$should_notify = false;
					if (isset($_POST["open"]) && isset($_POST["close"]))
					{
						$errors += !$unit->set_timeframe(!!$_POST["open"] || !!$_POST["close"] ? new Timeframe($_POST["open"], $_POST["close"]) : null);
						$should_notify = true;
					}
					else
					{
						if (isset($_POST["open"]))
						{
							$errors += !$unit->set_open($_POST["open"]);
							$should_notify = true;
						}
						if (isset($_POST["close"]))
						{
							$errors += !$unit->set_close($_POST["close"]);
							$should_notify = true;
						}
					}
					
					if ($errors) return null;
					
					if ($should_notify) $unit->get_course()->students_notify("Unit Updated", "An instructor has updated important details about a unit associated with this course.");
					
					return $unit;
				}
			)) Session::get()->set_result_assoc($unit->json_assoc());
			else Session::get()->set_error_assoc("Unit Modification", Unit::errors_unset());
		}
	}
	
	public function lists()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_GET, "unit_id", "Unit")))
		{
			if ($unit->session_user_can_execute())
			{
				self::return_array_as_json($unit->lists());
			}
			else
			{
				Session::get()->set_error_assoc("Unit-Lists Selection", "Session user cannot execute unit.");
			}
		}
	}
	
	public function lists_add()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (self::validate_request($_POST, "list_ids"))
			{
				if (Connection::transact(
					function () use ($unit)
					{
						foreach (explode(",", $_POST["list_ids"]) as $list_id)
						{
							if (!$unit->lists_add(EntryList::select_by_id($list_id)))
							{
								return null;
							}
						}
						return $unit;
					}
				)) self::return_array_as_json($unit->lists());
				else Session::get()->set_error_assoc("Unit-Lists Addition", Unit::errors_unset());
			}
		}
	}
	
	public function lists_remove()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_POST, "unit_id", "Unit")))
		{
			if (self::validate_request($_POST, "list_ids"))
			{
				if (Connection::transact(
					function () use ($unit)
					{
						foreach (explode(",", $_POST["list_ids"]) as $list_id)
						{
							if (!$unit->lists_remove(EntryList::select_by_id($list_id)))
							{
								return null;
							}
						}
						return $unit;
					}
				)) self::return_array_as_json($unit->lists());
				else Session::get()->set_error_assoc("Unit-Lists Removal", Unit::errors_unset());
			}
		}
	}
	
	public function tests()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_GET, "unit_id", "Unit")))
		{
			if ($unit->session_user_can_execute())
			{
				self::return_array_as_json($unit->tests());
			}
			else
			{
				Session::get()->set_error_assoc("Unit-Tests Selection", "Session user cannot execute unit.");
			}
		}
	}
	
	public function sittings()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($unit = self::validate_selection_id($_GET, "unit_id", "Unit")))
		{
			if ($unit->session_user_can_administer())
			{
				self::return_array_as_json($unit->sittings());
			}
			else
			{
				Session::get()->set_error_assoc("Unit-Sittings Selection", "Session user cannot administer course.");
			}
		}
	}
}

?>