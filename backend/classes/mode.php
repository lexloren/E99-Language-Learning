<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Mode extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	public static function insert($direction_from, $direction_to)
	{
		return static::errors_push("Failed to insert mode.");
	}

	public static function select_by_id($mode_id)
	{
		return parent::select("modes", "mode_id", $mode_id);
	}

        public static function select_by_direction($direction_from, $direction_to)
        {
                $result = Connection::query("SELECT * FROM modes WHERE LOWER(`from`) = '$direction_from' ".
                                "AND LOWER(`to`) = '$direction_to'");
                if (!!($error = Connection::query_error_clear())) return static::errors_push("Failed to select from mode: " . $error . ".");
                if (!$result || $result->num_rows === 0 || !($result_assoc = $result->fetch_assoc()))
                        return static::errors_push("Failed to select any mode for given direction $direction_from => $direction_to");

                return self::from_mysql_result_assoc($result_assoc);
        }

        public static function select_all()
        {
                $result = Connection::query("SELECT * FROM modes");
                if (!!($error = Connection::query_error_clear())) return static::errors_push("Failed to select from mode: " . $error . ".");
                $modes = array();
                while (($result_assoc = $result->fetch_assoc()))
                {
                        array_push($modes, self::from_mysql_result_assoc($result_assoc));
                }
                return $modes;
        }

	/***    INSTANCE    ***/

	private $mode_id = null;
	public function get_mode_id()
	{
		return $this->mode_id;
	}

	private $from = null;
	public function get_direction_from()
	{
		return $this->from;
	}

	private $to = null;
	public function get_direction_to()
	{
		return $this->to;
	}

	private function __construct($mode_id, $from, $to)
	{
		$this->mode_id = intval($mode_id, 10);
		$this->from = $from;
		$this->to = $to;

		self::register($this->mode_id, $this);
	}

	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array ("mode_id", "from", "to");
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["mode_id"],
				$result_assoc["from"],
				$result_assoc["to"]
			)
			: null;
	}

	public function delete()
	{
		return static::errors_push("Failed to delete mode.");
	}

	public function json_assoc($privacy = null)
	{
		$assoc = array (
			"modeId" => $this->get_mode_id(),
			"directionFrom" => $this->get_direction_from(),
			"directionTo" => $this->get_direction_to(),
		);
		
		return $this->privacy_mask($assoc, array_keys($assoc), false);
	}
}

?>
