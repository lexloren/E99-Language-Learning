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
		$this->open = intval($open, 10);
		$this->close = intval($close, 10);
	}
	
	public function is_current()
	{
		return ($time = time()) > $this->get_open() && $time < $this->get_close();
	}
	
	public function get_duration()
	{
		return !!$this->get_close() && !!$this->get_open()
			? $this->get_close() - $this->get_open()
			: null;
	}
	
	public function mysql_assignments()
	{
		return array (
			"open" => !!$this->get_open() ? $this->get_open() : "NULL",
			"close" => !!$this->get_close() ? $this->get_close() : "NULL"
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
