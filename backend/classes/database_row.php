<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class DatabaseRow
{
	protected static function set_error_description($error_description)
	{
		static::$error_description = (!!static::$error_description ? static::$error_description . "\n" : "") . $error_description;
		return null;
	}
	public static function unset_error_description()
	{
		$error_description = static::$error_description;
		static::$error_description = null;
		return $error_description;
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
	
	public static function reset()
	{
		static::$instances_by_id = array ();
		return static::unset_error_description();
	}
	
	protected static function select($table, $column, $id)
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
		if (!$instance->session_user_can_write())
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
	
	protected static function get_cached_collection(&$cache, $member_class, $table, $anchor_column, $anchor_id, $columns = "*")
	{
		if (!isset($cache))
		{
			$cache = array ();
			
			$anchor_id = intval($anchor_id, 10);
			
			$mysqli = Connection::get_shared_instance();
		
			$result = $mysqli->query("SELECT $columns FROM $table WHERE $anchor_column = $anchor_id");
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				if (!($member = $member_class::from_mysql_result_assoc($result_assoc)))
				{
					unset ($cache);
					return static::set_error_description("Failed to select from $table where $anchor_column = $anchor_id: " . $member_class::unset_error_description());
				}
				array_push($cache, $member);
			}
		}
		
		return $cache;
	}
	
	protected static function update_this($instance, $table, $assignments, $id_column, $id)
	{
		$mysqli = Connection::get_shared_instance();
		
		$assignments_sql = array ();
		foreach ($assignments as $column => $value)
		{
			if (is_string($value))
			{
				$value = "'".$mysqli->escape_string($value)."'";
			}
			else
			{
				value = intval($value, 10);
			}
			
			array_push($assignments_sql, "$column = $value");
		}
		$assignments_sql = implode(", ", $assignments_sql);
		
		$failure_message = "Failed to update $table setting $assignments_sql where $id_column = $id";
		
		if (!$instance->session_user_can_write())
		{
			return static::set_error_description("$failure_message: Session user is not owner.");
		}
		
		$id = intval($id, 10);
		
		$result = $mysqli->query("UPDATE $table SET $assignments_sql WHERE $id_column = $id");
		
		return !$mysqli->error ? $instance : static::set_error_description("$failure_message: " . $mysqli->error);
	}
	
	protected function get_owner()
	{
		return null;
	}
	
	public function user_is_owner($user)
	{
		return !!$this->get_owner() && $this->get_owner()->equals($user);
	}
	
	public function session_user_is_owner()
	{
		return !!Session::get() && $this->user_is_owner(Session::get()->get_user());
	}
	
	public function user_can_write($user)
	{
		return $this->user_is_owner($user);
	}
	
	public function session_user_can_write()
	{
		return $this->session_user_is_owner();
	}
	
	public function user_can_read($user)
	{
		return $this->user_can_write($user);
	}
	
	public function session_user_can_read()
	{
		return $this->session_user_can_write();
	}
	
	public function assoc_for_json($privacy = null)
	{
		return null;
	}
	
	public function detailed_assoc_for_json($privacy = null)
	{
		return $this->assoc_for_json($privacy);
	}
	
	protected static function array_for_json($array)
	{
		if (!is_array($array))
		{
			return static::set_error_description("Back end expected associative array of DatabaseRow objects but received '$array'.");
		}
		
		$assocs = array ();
		foreach ($array as $item)
		{
			if (!is_subclass_of($item, "DatabaseRow"))
			{
				return static::set_error_description("Back end expected associative array of DatabaseRow objects, but one such object was '$item'.");
			}
			array_push($assocs, $item->assoc_for_json());
		}
		
		return $assocs;
	}
}

?>