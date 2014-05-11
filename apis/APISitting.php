<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APISitting extends APIBase
{
	public function select()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($sitting = self::validate_selection_id($_GET, "sitting_id", "Sitting")))
		{
			Session::get()->set_result_assoc($sitting->json_assoc_detailed());
		}
	}
	
	public function delete()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($sitting = self::validate_selection_id($_POST, "sitting_id", "Sitting")))
		{
			$json_assoc = $sitting->json_assoc();
			
			if (!$sitting->delete())
			{
				Session::get()->set_error_assoc("Sitting Deletion", Sitting::errors_unset());
			}
			else
			{
				Session::get()->set_result_assoc($json_assoc);
			}
		}
	}
	
	public function update()
	{
		if (!Session::get()->reauthenticate()) return;
		
		if (($sitting = self::validate_selection_id($_POST, "sitting_id", "Sitting")))
		{
			if (isset($_POST["message"]))
			{
				if (!$sitting->set_message($_POST["message"]))
				{
					Session::get()->set_error_assoc("Sitting Modification", Sitting::errors_unset());
					return;
				}
			}
			Session::get()->set_result_assoc($sitting->json_assoc());
		}
	}
}

?>