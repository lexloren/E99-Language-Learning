<?php

require_once './backend/connection.php';
require_once './tools/database.php';
require_once './phptests/TestDB.php';

class ConnectionTest extends PHPUnit_Framework_TestCase
{
        private $db;

        public function setup()
        {
                Session::set(null);
                $this->db = TestDB::create();
                $this->assertNotNull($this->db, "failed to create test database");
        }

        public function test_connection()
        {
                Connection::set_mysqli(null);
                $this->assertEquals(true, Connection::mysqli_test());
				$this->db = TestDB::create();
                $this->assertNotNull($this->db->link);
        }
}

?>
