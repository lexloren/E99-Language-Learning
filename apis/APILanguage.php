<?php

require_once "./apis/APIBase.php";
require_once "./backend/classes.php";

class APILanguage extends APIBase
{
	public function enumerate()
	{
		self::return_array_as_json(Language::select_all());
	}
}
?>