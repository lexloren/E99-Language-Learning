<?php

class Status extends DatabaseRow
{
	/***    CLASS/STATIC    ***/
	protected static $instances_by_id = array ();
	protected static $errors = null;
	
	public static function select_by_id($status_id)
	{
		return parent::select("user_statuses", "status_id", $status_id);
	}
	
	public static function select_by_description($status_desc)
	{
		return parent::select("user_statuses", "desc", $status_desc);
	}
	
	public static function select_all()
	{
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query("SELECT * FROM user_statuses");
		
		$user_statuses = array ();
		
		while (($result_assoc = $result->fetch_assoc()))
		{
			array_push($user_statuses, self::from_mysql_result_assoc($result_assoc));
		}
		
		return $user_statuses;
	}
	
	/***    INSTANCE    ***/

	protected $status_id = null;
	public function get_status_id()
	{
		return $this->status_id;
	}
	
	protected $description = null;
	public function get_description()
	{
		return $this->description;
	}

	private function __construct($status_id, $description)
	{
		$this->status_id = intval($status_id, 10);
		$this->description = $description;
		
		static::$instances_by_id[$this->status_id] = $this;
		static::$instances_by_id[$this->description] = $this;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"status_id",
			"desc"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["status_id"],
				$result_assoc["desc"]
			)
			: null;
	}

	public function json_assoc($privacy = null)
	{
		return array (
			"statusId" => $this->get_status_id(),
			"description" => $this->get_description()
		);
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		return parent::json_assoc_detailed($privacy);
	}
}

?>