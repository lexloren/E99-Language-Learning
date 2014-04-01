<?php

//Tests class User
require_once './backend/classes/session.php';
require_once './backend/classes/practice.php';
require_once './phptests/TestDB.php';

class PracticeTest extends PHPUnit_Framework_TestCase
{
	private $db;

        public function setup()
        {
                Session::set(null);
                $this->db = TestDB::create();
                $this->assertNotNull($this->db, "failed to create test database");

		$this->db->add_users(1);
		$this->db->add_practice_data($this->db->user_ids[0], 2, 10);
        }
        
        public function tearDown()
        {
                //if (isset($this->db))
                //      $this->db->close();
        }

	public function testGenerate()
	{

	}

	public function testUpdatePracticeResponse()
	{

	}
	
}
?>
