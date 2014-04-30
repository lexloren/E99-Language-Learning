<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Practice extends DatabaseRow
{
	const PRACTICE_ENTRIES_CNT = 50;

	/***    CLASS/STATIC    ***/
	protected static $errors = null;
        protected static $instances_by_id = array ();
        protected static $error_description = null;

	public static function select_by_id($practice_entry_id)
        {
                return parent::select("user_practice", "practice_entry_id", $practice_entry_id);
        }

	public static function insert($user_entry_id)
	{
		$modes = self::get_all_modes();
		$user_practice_set = array();
		$mysqli = Connection::get_shared_instance();

		foreach($modes as $mode)
		{
			$mysqli->query("INSERT IGNORE INTO user_practice (user_entry_id, mode) values($user_entry_id, $mode)");
			if (!!$mysqli->error)
	                        return static::errors_push("Failed to insert user_practice: " . $mysqli->error . ".");
                
	                if (($user_practice = self::select_by_id($mysqli->insert_id)))
        	        {
				array_push($user_practice_set, $user_practice);
                	}
		}
		return $user_practice_set;
	}

	public static function get_mode_from_direction($practice_from, $practice_to)
	{
		$mysqli = Connection::get_shared_instance();
		$result = $mysqli->query("SELECT mode_id FROM modes WHERE `from` = '$practice_from' ".
				"AND `to` = '$practice_to'");
		if (!!$mysqli->error) return static::errors_push("Failed to select from mode: " . $mysqli->error . ".");
		if (!$result || $result->num_rows === 0 || !($result_assoc = $result->fetch_assoc()))
                        return static::errors_push("Failed to select any mode for given where direction $practice_from => $practice_to");

		return intval($result_assoc["mode_id"], 10);
	}
	
	public static function get_direction_from_mode($mode)
	{
		$mysqli = Connection::get_shared_instance();
		$result = $mysqli->query("SELECT `from`, `to` FROM modes WHERE mode_id = $mode");
		if (!!$mysqli->error) return static::errors_push("Failed to select from direction: " . $mysqli->error . ".");
                if (!$result || $result->num_rows === 0 || !($result_assoc = $result->fetch_assoc()))
                {
                        return static::errors_push("Failed to select any direction for given mode $mode");
                }
		
		return array("practice_from" => $result_assoc["from"], "practice_to" => $result_assoc["to"]);
	}

	public static function get_all_modes()
	{
		$mysqli = Connection::get_shared_instance();
		$result = $mysqli->query("SELECT mode_id FROM modes");
		if (!!$mysqli->error) return static::errors_push("Failed to select from mode: " . $mysqli->error . ".");
		$modes = array();
		while (($result_assoc = $result->fetch_assoc()))
                {
			array_push($modes, intval($result_assoc["mode_id"]));
		}
		return $modes;
	}

        /***    INSTANCE    ***/

	private $practice_entry_id = null;
        public function get_practice_entry_id()
        {
                return $this->practice_entry_id;
        }

	private $user_entry_id = null;
	public function get_user_entry_id()
	{
		return $this->user_entry_id;
	}

	private $mode = null;
	public function get_mode()
	{
		return $this->mode;
	}

	private $interval = null;
	public function get_interval()
	{
		return $this->interval;
	}

	private $efactor = null;
	public function get_efactor()
	{
		return $this->efactor;
	}

	private $entry = null;
	public function get_entry()
	{
		return $this->entry;
	}

	private function __construct($practice_entry_id, $user_entry_id, $mode, $interval, $efactor)
	{
		$this->practice_entry_id = intval($practice_entry_id,10);
		$this->user_entry_id = intval($user_entry_id, 10);
		$this->mode = intval($mode, 10);
		$this->interval = intval($interval, 10);
		$this->efactor = floatval($efactor);
		$this->entry = UserEntry::select_by_user_entry_id(intval($user_entry_id, 10));

		self::register($this->practice_entry_id, $this);
	}

        public static function generate($list_ids, $practice_from, $practice_to, $entries_count)
        {
		$practice_set = array();
		$mode = self::get_mode_from_direction($practice_from, $practice_to);
		if(isset($mode))
		{
	                $count_limit = (isset($entries_count) && intval($entries_count, 10) > 0) ? 
        	                intval($entries_count, 10) : self::PRACTICE_ENTRIES_CNT;
                
                	$mysqli = Connection::get_shared_instance();
	                $list_ids_str = join(', ', $list_ids);

			$new_entries = $mysqli->query(sprintf(
                	        "SELECT entry_id FROM list_entries LEFT JOIN user_entries USING (user_entry_id) WHERE list_id IN (%s) ".
				"AND entry_id NOT IN (SELECT entry_id from user_entries where user_id = %d)",
	                        $list_ids_str,
        	                Session::get()->get_user()->get_user_id()
			));

			if (!!$mysqli->error) return static::errors_push("Failed to select entries: " . $mysqli->error . ".");

			while (($new_entry_assoc = $new_entries->fetch_assoc()))
        	        {
				$entry_id = intval($new_entry_assoc["entry_id"], 10);
				$user_entry = Entry::select_by_id($entry_id)->copy_for_session_user();
				self::insert($user_entry->get_user_entry_id());
			}

                	$result = $mysqli->query(sprintf(
                        	"SELECT practice_entry_id, user_entry_id, `mode`, `interval`, efactor FROM user_practice ".
				"LEFT JOIN user_entries USING (user_entry_id) WHERE mode = $mode AND entry_id IN (".
        	                "SELECT entry_id FROM list_entries LEFT JOIN user_entries USING (user_entry_id) WHERE list_id IN (%s)) ".
                	        "AND user_id = %d ORDER BY `interval`",
                        	$list_ids_str, Session::get()->get_user()->get_user_id()
	                ));
			if (!!$mysqli->error) return static::errors_push("Failed to select Practice entries: " . $mysqli->error . ".");
	                if (!$result || $result->num_rows === 0)
                	        return static::errors_push("Failed to select any practice entries for given direction $practice_from => $practice_to.");

			while (($result_assoc = $result->fetch_assoc()) && !!($count_limit--))
			{
				array_push($practice_set, self::from_mysql_result_assoc($result_assoc));
			}
                }
		return $practice_set;
        }

	public static function from_mysql_result_assoc($result_assoc)
        {
                $mysql_columns = array (
                        "practice_entry_id",
                        "user_entry_id",
                        "mode",
			"interval",
			"efactor"
                );
                
                return self::assoc_contains_keys($result_assoc, $mysql_columns)
                        ? new self(
                                $result_assoc["practice_entry_id"],
                                $result_assoc["user_entry_id"],
                                $result_assoc["mode"],
                                $result_assoc["interval"],
                                $result_assoc["efactor"]
                        )
                        : null;
        }

	public function get_user_entry_results_count()
	{	
		$mysqli = Connection::get_shared_instance();

                $iteration_result = $mysqli->query(sprintf(
                        "SELECT COUNT(*) AS row_count FROM user_entry_results " .
                        "WHERE user_entry_id = %d", $this->get_user_entry_id()
                ));
		if (!!$mysqli->error) return static::errors_push("Error fetching user_entry_results: " . $mysqli->error . ".");
                $iteration_assoc = $iteration_result->fetch_assoc();
                return intval($iteration_assoc["row_count"], 10);
	}

	public function update_practice_response($grade_id)
        {
                $mysqli = Connection::get_shared_instance();
		$failure_message = "Failed to update practice response";
                
                $mysqli->query(sprintf("INSERT INTO user_entry_results (user_entry_id, grade_id, mode) VALUES (%d, %d, %d)",
                        $this->get_user_entry_id(), $grade_id, $this->get_mode()
                ));
                
                if (!$mysqli->insert_id || !!$mysqli->error ||
                        !($grade = Grade::select_by_id($grade_id)))
			return static::errors_push("$failure_message: " . $mysqli->error . ".");
                $point = $grade->get_point();

		$_efactor = $this->get_efactor() + (0.1 - (4 - $point) * (0.08 + (4 - $point) * 0.02));
                $new_efactor = min(max($_efactor, 1.3), 2.5);
		$user_entry_results_count = $this->get_user_entry_results_count();
                if ($user_entry_results_count == 0 || $user_entry_results_count == 1)
                        $new_interval = 1;
                else if ($user_entry_results_count == 2)
                        $new_interval = 6;
                else
                        $new_interval = round($this->get_interval() * $new_efactor);

                if(!$mysqli->query(
                        "UPDATE user_practice SET `interval` = $new_interval, efactor = $new_efactor ".
                        "WHERE practice_entry_id = $this->practice_entry_id"
                        ))
                        return static::errors_push("$failure_message: " . $mysqli->error . ".");

                $this->interval = $new_interval;
                $this->efactor = $new_efactor;
                return $this;
        }
}

?>
