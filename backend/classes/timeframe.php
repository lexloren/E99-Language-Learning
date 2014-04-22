<?php

class Timeframe
{
	const TIME_MIN = 946684800;  //  2000/01/01 00:00:00
	
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
		$this->open = ($open = intval($open, 10)) > self::TIME_MIN ? $open : null;
		$this->close = ($close = intval($close, 10)) > self::TIME_MIN ? $close : null;
	}
	
	public function is_current()
	{
		return (!$this->get_open() || ($time = time()) > $this->get_open())
			&& (!$this->get_close() || $time < $this->get_close());
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
	
	public function json_assoc($privacy = null)
	{
		return array (
			"open" => $this->get_open(),
			"close" => $this->get_close(),
			"isCurrent" => $this->is_current()
		);
	}
}

?>
