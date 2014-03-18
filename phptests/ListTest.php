<?php

//Tests class list
require_once './backend/classes/list.php';
require_once './backend/connection.php';
require_once './tools/database.php';

class ListTest extends PHPUnit_Framework_TestCase
{
	private $link;

	public function setup()
	{
		$this->link = database::recreate_database('cscie99test');
		
		$this->assertNotNull($this->link, "No database connection");

		Connection::set_shared_instance($this->link);
	}
	
	public function tearDown()
	{
		//if (isset($this->link))
		//	$this->link->close();
	}

	public function testInsert()
	{
		
	}
	
	public function testSelect()
	{
		
	}
	
	public function testadd_entry()
	{
	}
	
	public function testremove_entry()
	{
	}
	
	public function testdelete()
	{
	}
	
	public function testget_entries()
	{
	}
}









?>