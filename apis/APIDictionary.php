<?php

require_once 'APIBase.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/backend/support.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/backend/classes/session.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/backend/classes/user.php';

class APIDictionary extends APIBase
{
	public function __construct() {	
		parent::__construct();
	}
}
?>

