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
	
	protected function return_array_as_assoc_for_json($array)
	{
		if (!is_array($array))
		{
			Session::get()->set_error_assoc("Unknown Error", "Back end expected associative array of DatabaseRow objects but received $array.");
		}
		else
		{
			$returnable = array ();
			foreach ($array as $item)
			{
				if (!is_subclass_of($item, DatabaseRow))
				{
					Session::get()->set_error_assoc("Unknown Error", "Back end expected associative array of DatabaseRow objects, but one such object in $array was $item.");
					return;
				}
				array_push($returnable, $item->assoc_for_json());
			}
			
			Session::get()->set_result_assoc($returnable);
		}
	}
}

?>