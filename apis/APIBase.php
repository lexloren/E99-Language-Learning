<?php

//  The purpose of this statement is to enforce that we don't try to include/require more than once (by accident).
//  If we're getting an error, it means somehow we're trying to require this script more than once,
//      and that means we have an error elsewhere in the application.

//Do we need this for a class? Getting some error, so commented

require_once "./backend/classes.php";

class APIBase
{
	protected $mysqli = null;
	protected $user = null;
	
	protected static function errors_collect($classes)
	{
		array ();
		$errors = array ();
		
		foreach ($classes as $class)
		{
			if (($more = $class::errors_unset())) array_push($errors, $more);
		}
		
		return implode(" ", $errors);
	}
	
	protected static function validate_request($array, $keys)
	{
		if (is_string($keys)) $keys = array ($keys, $keys);
		
		$keys_missing = array_diff($keys, array_keys($array));
		if (count($keys_missing) > 0)
		{
			Session::get()->set_error_assoc("Request Invalid", "Request must include " . implode(", ", $keys) . " (missing " . implode(", ", $keys_missing) . ").");
			return false;
		}
		return true;
	}
	
	protected static function validate_selection_id($array, $id_key, $class_name)
	{
		$object = null;
		if (!in_array($id_key, array_keys($array)))
		{
			Session::get()->set_error_assoc("Request Invalid", "Request must include $id_key.");
		}
		else if (!($object = $class_name::select_by_id(($id = intval($array[$id_key], 10)))))
		{
			Session::get()->set_error_assoc("$class_name Selection", $class_name::errors_unset());
		}
		
		return $object;
	}
	
	protected static function return_array_as_json($array, $privacy = null, $result_information = null)
	{
		if (!is_array($array))
		{
			Session::get()->set_error_assoc("Unknown Error", "Back end expected associative array of DatabaseRow objects but received '$array'.");
		}
		else
		{
			$returnable = array ();
			foreach ($array as $item)
			{
				if (!is_subclass_of($item, "DatabaseRow"))
				{
					Session::get()->set_error_assoc("Unknown Error", "Back end expected associative array of DatabaseRow objects, but one such object was '$item'.");
					return;
				}
				array_push($returnable, $item->json_assoc($privacy));
			}
			
			Session::get()->set_result_assoc($returnable, $result_information);
		}
	}
	
	public static function collect_entries($use_list_ids = true)
	{
		$entries = array ();
		
		if ($use_list_ids && isset($_POST["list_ids"]))
		{
			$list_ids = explode(",", $_POST["list_ids"]);
			
			foreach ($list_ids as $list_id)
			{
				if (($list = EntryList::select_by_id($list_id))
					&& $list->session_user_can_read()
					&& $list->session_user_can_execute())
				{
					foreach ($list->entries() as $entry)
					{
						if ($entry->in($entries) === null)
						{
							array_push($entries, $entry);
						}
					}
				}
			}
		}
		
		if (isset($_POST["entry_ids"]))
		{
			foreach (explode(",", $_POST["entry_ids"]) as $entry_id)
			{
				if (($entry = Entry::select_by_id($entry_id))
					&& ($entry = $entry->copy_for_session_user()))
				{
					if ($entry->in($entries) === null)
					{
						array_push($entries, $entry);
					}
				}
			}
		}
		
		return $entries;
	}

	public function method_output_type($method)
	{
		return "json";
	}
}

?>
