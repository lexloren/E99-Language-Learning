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
	
	public function __construct($arg)
	{
		if (in_array("open", array_keys($arg)) && in_array("close", array_keys($arg)))
		{
			$open = $arg["open"];
			$close = $arg["close"];
		}
		else if (count($arg) === 2)
		{
			$open = $arg[0];
			$close = $arg[1];
		}
		
		$this->open = intval($open, 10);
		$this->close = intval($close, 10);
	}
	
	public function is_current()
	{
		return ($time = time()) > $open && $time < $close;
	}
	
	public function assoc_for_json($privacy = null)
	{
		return array (
			"open" => $open,
			"close" => $close,
			"isCurrent" => $this->is_current()
		);
	}
}

?>