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
		if (count(array_diff(array ("open","close"), array_keys($arg))) === 0)
		{
			$open = $arg["open"];
			$close = $arg["close"];
		}
		else if (count($arg) === 2)
		{
			$open = $arg[0];
			$close = $arg[1];
		}
		
		$this->open = strtotime($open);
		$this->close = strtotime($close);
	}
	
	public function is_current()
	{
		return ($time = time()) > $open && $time < $close;
	}
	
	public function assoc_for_json()
	{
		return array (
			"open" => $open,
			"close" => $close,
			"isCurrent" => $this->is_current()
		);
	}
}

?>