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
	
	public function __construct($user, $mysqli) 
	{
		$this->user = $user;
		$this->mysqli = $mysqli;
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
			Session::get()->set_error_assoc("$class_name Selection", $class_name::unset_error_description());
		}
		
		return $object;
	}
	
	protected static function return_updates_as_json($class_name, $error_description, $result_assoc)
	{
		$error_title = "$class_name Modification";
		if ($result_assoc)
		{
			if ($error_description)
			{
				Session::get()->set_mixed_assoc($error_title, $error_description, $result_assoc);
			}
			else
			{
				Session::get()->set_result_assoc($result_assoc);
			}
		}
		else
		{
			if (!$error_description) $error_description = "No updates requested.";
			
			Session::get()->set_error_assoc($error_title, $error_description);
		}
	}
	
	protected static function return_array_as_json($array, $privacy = null)
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
				array_push($returnable, $item->assoc_for_json($privacy));
			}
			
			Session::get()->set_result_assoc($returnable);
		}
	}
}

?>