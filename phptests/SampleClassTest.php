<?php

require_once 'SampleClass.php';

class SampleClassTest extends PHPUnit_Framework_TestCase
{
	public $test;
	public function setup()
	{
		$this->test = new SampleClass("Xenogloss", 99, True);
	}
	
	public function testGetString()
    {
		$this->assertEquals($this->test->getString(), "Xenogloss");
    }
	
	public function testGetInt()
    {
		$this->assertEquals($this->test->getInt(), 99);
    }
	
	public function testGetBool()
    {
		$this->assertEquals($this->test->getBool(), True);
    }
}
?>