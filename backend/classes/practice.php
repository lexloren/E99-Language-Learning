<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

//  Let's think of a "Practice" object as representing one practice session
class Practice
{
	const PRACTICE_ENTRIES_CNT = 50;
	
	//  SHOULD THIS BE A PUBLIC STATIC FUNCTION RETURNING A NEW PRACTICE OBJECT?
	public static function generate($list_ids, $entries_count)
	{
		$count_limit = (isset($entries_count) && intval($entries_count, 10) > 0) ? 
			intval($entries_count, 10) : self::PRACTICE_ENTRIES_CNT;
		
		$mysqli = Connection::get_shared_instance();
		$list_ids_str = join(', ', $list_ids);

		$learned_entries = $mysqli->query(sprintf(
			"SELECT entry_id FROM user_entries WHERE entry_id IN (".
			"SELECT entry_id FROM list_entries LEFT JOIN user_entries USING (user_entry_id) WHERE list_id IN (%s)) ".
			"AND user_id = %d ORDER BY 'interval'",
			$list_ids_str, Session::get()->get_user()->get_user_id()
		));
		$learned_entry_set = self::from_mysql_entry_id_assoc($learned_entries)->get_entry_ids();
		$learned_count = count($learned_entry_set);
		
		$not_learned_entries = $mysqli->query(sprintf(
			"SELECT entry_id FROM list_entries LEFT JOIN user_entries USING (user_entry_id) WHERE list_id IN (%s) AND entry_id NOT IN (%s)",
			$list_ids_str,
			join(', ', $learned_entry_set))
		);
		$not_learned_entry_set = self::from_mysql_entry_id_assoc($not_learned_entries)->get_entry_ids();
		$not_learned_count = count($not_learned_entry_set);
		
		if (($learned_count + $not_learned_count) == 0 || $count_limit == 0)
		{
			return Practice(array());
		}
		else
		{
			$new_entry_count = min(
				$not_learned_count,
				ceil($not_learned_count / ($learned_count + $not_learned_count) * $count_limit)
			);
		
			$practice_entry_set = array_merge(
				array_slice($not_learned_entry_set, 0, $new_entry_count),
				array_slice($learned_entry_set, 0, $count_limit - $new_entry_count)
			);
			return new Practice($practice_entry_set);
		}
	}
	
	private $entry_ids = null;
	public function get_entry_ids()
	{
		return $this->entry_ids;
	}

	private $entries = null;
	public function get_entries()
	{
		return $this->entries;
	}

	public function __construct($entry_ids)
	{
		if (!is_array($entry_ids))
		{
			Session::get()->set_error_assoc("Construct", "Practice accepts entry_ids array, invalid input.");
			return;
		}
		$this->entries = array();
		foreach ($entry_ids as $entry_id)
		{
			$entry = Entry::select_by_id($entry_id)->copy_for_session_user();
			array_push($this->entries, $entry);
		}
		$this->entry_ids = $entry_ids;
	}

	private static function from_mysql_entry_id_assoc($result)
	{
		$entry_ids = array();
		if (!!$result)
		{
			while (($result_assoc = $result->fetch_assoc()))
			{
				array_push($entry_ids, $result_assoc["entry_id"]);
			}
		}
		return new Practice($entry_ids);
	}
	
	//  SHOULD THIS BE CLASS- OR INSTANCE- SCOPE?
	public static function update_practice_response($entry_id, $grade_id)
	{
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO user_entry_results (user_entry_id, grade_id) VALUES (".
			"(SELECT user_entry_id FROM user_entries WHERE user_id = %d AND entry_id = %d), %d)",
			Session::get()->get_user()->get_user_id(),
			$entry_id,
			$grade_id
		));
		
		if (!$mysqli->insert_id)
		{
			Session::get()->set_error_assoc("response", "Failed to update practice response details, ".$mysqli->error);
			return;
		}
		
		$user_entry = Entry::select_by_id($entry_id)->copy_for_session_user();
		$grade_result = $mysqli->query(sprintf("SELECT * FROM grades WHERE grade_id = $grade_id"));
		$grade_point = Grade::from_mysql_result_assoc($grade_result->fetch_assoc());
		if (!$grade_point || !$user_entry)
		{
			Session::get()->set_error_assoc("response", "Failed to update practice response details, ".$mysqli->error);
			return;
		}
		return $user_entry->update_repetition_details($grade_point->get_point());
	}
}

?>
