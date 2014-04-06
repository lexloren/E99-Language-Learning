<?php

class Timeframe
{
	private $open;
	public function get_open()
	{
		return $this->open;
	}
	
	private $close;
	public function get_close()
	{
		return $this->close;
	}
	
	public function __construct($open, $close)
	{
		/*if (in_array("open", array_keys($arg)) && in_array("close", array_keys($arg)))
		{
			$open = $arg["open"];
			$close = $arg["close"];
		}
		else if (count($arg) === 2)
		{
			$open = $arg[0];
			$close = $arg[1];
		}*/
		
		$this->open = intval($open, 10);
		$this->close = intval($close, 10);
	}
	
	public function is_current()
	{
		return ($time = time()) > $this->open && $time < $this->close;
	}
	
	public function mysql_assignments()
	{
		return array (
			"open" => !!$this->get_open() ? "FROM_UNIXTIME(" . $this->get_open() . ")" : "NULL",
			"close" => $this->get_close() ? "FROM_UNIXTIME(" . $this->get_close() . ")" : "NULL"
		);
	}
	
	public function assoc_for_json($privacy = null)
	{
		return array (
			"open" => $this->get_open(),
			"close" => $this->get_close(),
			"isCurrent" => $this->is_current()
		);
	}
}

?>
