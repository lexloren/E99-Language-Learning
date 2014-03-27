<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class DatabaseRow
{
	protected static function set_error_description($error_description)
	{
		static::$error_description = $error_description;
		return null;
	}
	public static function get_error_description()
	{
		return static::$error_description;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		return null;
	}
	
	protected static function register($id, $instance)
	{
		static::$instances_by_id[$id] = $instance;
	}
	
	protected static function select_by_id($table, $column, $id)
	{
		$id = intval($id, 10);
		
		if (isset(static::$instances_by_id[$id])) return static::$instances_by_id[$id];
		
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query("SELECT * FROM $table WHERE $column = $id");
		
		if (!!$mysqli->error) return static::set_error_description("Failed to select from $table: " . $mysqli->error);
		
		if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
		{
			return static::from_mysql_result_assoc($result_assoc);
		}
		
		return static::set_error_description("Failed to select any rows from $table where $column = $id.");
	}
	
	protected static function delete_this($instance, $table, $column, $id)
	{
		if (!$instance->session_user_is_owner())
		{
			return static::set_error_description("Failed to delete from $table where $column = $id: Session user is not owner.");
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM $table WHERE $column = %d",
			intval($id, 10)
		));
		
		return !$mysqli->error ? $instance : static::set_error_description("Failed to delete from $table where $column = $id: " . $mysqli->error);
	}
	
	protected static function assoc_contains_keys($assoc, $keys)
	{
		if (!isset($assoc) || !$assoc || !is_array($assoc))
		{
			return static::set_error_description("Invalid result_assoc: " . $assoc . ".");
		}
		
		$keys_missing = array_diff($keys, array_keys($assoc));
		
		if (count($keys_missing) > 0)
		{
			return static::set_error_description("Missing keys in result_assoc: " . implode(", ", $keys_missing) . ".");
		}
		
		return true;
	}
	
	public function get_owner()
	{
		return null;
	}
	
	public function get_cached_collection(&$cache, $member_class, $table, $anchor_column, $anchor_id)
	{
		if (!isset($cache))
		{
			$cache = array ();
			
			$anchor_id = intval($anchor_id, 10);
			
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query("SELECT * FROM $table WHERE $anchor_column = $anchor_id");
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				if (!($member = $member_class::from_mysql_result_assoc($result_assoc)))
				{
					unset ($cache);
					return static::set_error_description("Failed to select from $table where $anchor_column = $anchor_id: " . $member_class::get_error_description());
				}
				array_push($cache, $member);
			}
		}
		
		return $cache;
	}
	
	public function session_user_is_owner()
	{
		return !!Session::get() && !!$this->get_owner()
			&& $this->get_owner()->equals(Session::get()->get_user());
	}
	
	public function assoc_for_json()
	{
		return null;
	}
}

?>