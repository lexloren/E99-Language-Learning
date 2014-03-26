<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

//  Let's think of a "Practice" object as representing one practice session
class Practice
{
	const PRACTICE_ENTRIES_CNT = 50;
	
	private $lists;
	private $entries;

	//  SHOULD THIS BE A PUBLIC STATIC FUNCTION RETURNING A NEW PRACTICE OBJECT?
	//  public static function generate($list_ids, $entries_count)
	public function get_practice_entries($list_ids, $entries_count)
	{
		$count_limit = isset($entries_count) ? $entries_count : Practice::PRACTICE_ENTRIES_CNT;
		
		$mysqli = Connection::get_shared_instance();
		$list_ids_str = join(', ', $list_ids);
		
		$learned_entries = $mysqli->query(sprintf(
			"SELECT entry_id FROM user_entries WHERE entry_id IN (".
			"SELECT entry_id FROM list_entries WHERE list_id IN (%s)) ".
			"AND user_id = %d ORDER BY interval",
			$list_ids_str, Session::get()->get_user()->get_user_id()
		));
		$learned_entry_set = Practice::from_mysql_entry_id_assoc($learned_entries);
		$learned_count = count($learned_entry_set);
		
		$not_learned_entries = $mysqli->query(sprintf("SELECT entry_id FROM list_entries WHERE list_id IN (%s) ".
																						"AND entry_id NOT IN (%s)",
			$list_ids_str,
			join(', ', $learned_entry_set))
		);
		$not_learned_entry_set = Practice::from_mysql_entry_id_assoc($not_learned_entries);
		$not_learned_count = count($not_learned_entry_set);
		
		$new_entry_count = min(
			$not_learned_count,
			ceil($not_learned_count / ($learned_count + $not_learned_count) * $count_limit)
		);
		
		$practice_entry_set = array_merge(
			array_slice($not_learned_entry_set, 0, $new_entry_count),
			array_slice($learned_entry_set, 0, $count_limit - $new_entry_count)
		);
		
		$result_entries = array();
		foreach ($practice_entry_set as $entry_id)
		{
			$entry = Dictionary::select_entry($entry_id);
			array_push($result_entries, $entry->assoc_for_json());
		}
		
		return $result_entries;
	}
	
	public function __construct(/* ARGUMENTS */)
	{
		//  TO IMPLEMENT
	}

	//  TO FIX: FOR CONSISTENCY, SHOULD RETURN A NEW PRACTICE OBJECT
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
		return $entry_ids;
	}
	
	//  SHOULD THIS BE CLASS- OR INSTANCE- SCOPE?
	public static function update_practice_response($entry_id, $grade_id)
	{
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO user_entry_results (user_id, entry_id, grade_id) VALUES (%d, %d, %d)",
			Session::get()->get_user()->get_user_id(),
			$entry_id,
			$grade_id
		));
		
		if (!$mysqli->insert_id)
		{
			Session::get()->set_error_assoc("Failed to update practice response details.");
		}
		
		$mysqli->query(sprintf("INSERT IGNORE INTO user_entries (user_id, entry_id) VALUES (%d, %d)",
			Session::get()->get_user()->get_user_id(),
			$entry_id)
		);
		$user_entry_result = $mysqli->query(sprintf("SELECT * FROM user_entries WHERE entry_id = %d AND user_id = %d",
			$entry_id,
			Session::get()->get_user()->get_user_id())
		);
		$user_entry = Entry::from_mysql_result_assoc($user_entry_result->fetch_assoc());
		$grade_result = $mysqli->query(sprintf("SELECT point FROM grades WHERE grade_id = $grade_id"));
		$grade_point = Grade::from_mysql_result_assoc($grade_result->fetch_assoc());
		if (!$grade_point || !$user_entry)
		{
			Session::get()->set_error_assoc("Failed to update practice response details.");
		}
		return $user_entry->update_repetition_details($grade_point->get_point());
	}
}

?>
