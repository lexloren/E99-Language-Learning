<?php

require_once 'APIBase.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/backend/support.php';

class User extends APIBase
{
	public function __construct() {	
		parent::__construct();
	}

	public function authenticate()
	{
		exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
	public function register()
	{
		exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function activate() 
	{
		exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function reset_password() 
	{
		exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
	
	public function deauthenticate() 
	{
		exit_with_error("TODO", __CLASS__."::".__FUNCTION__);
	}
}
?>

