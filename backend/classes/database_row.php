<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class DatabaseRow extends ErrorReporter
{
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
		return parent::reset();
	}
	
	protected static function select($table, $column, $id, $override_safety = false)
	{
		$mysqli = Connection::get_shared_instance();
		
		if (!$override_safety)
		{
			if (is_string($id)) $id = "'".$mysqli->escape_string($id)."'";
			else $id = intval($id, 10);
		}
		
		if (isset(static::$instances_by_id[$id])) return static::$instances_by_id[$id];
		
		$result = $mysqli->query("SELECT * FROM $table WHERE $column = $id LOCK IN SHARE MODE");
		
		if (!!$mysqli->error) return static::errors_push("Failed to select from $table: " . $mysqli->error . ".", ErrorReporter::ERRCODE_DATABASE);
		
		if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
		{
			return static::from_mysql_result_assoc($result_assoc);
		}
		
		return static::errors_push("Failed to select any rows from $table where $column = $id.", ErrorReporter::ERRCODE_UNKNOWN);
	}
	
	protected static function delete_this($instance, $table, $column, $id)
	{
		if (!$instance->session_user_can_write())
		{
			return static::errors_push("Failed to delete from $table where $column = $id: Session user is not owner.", ErrorReporter::ERRCODE_PERMISSIONS);
		}
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM $table WHERE $column = %d",
			intval($id, 10)
		));
		
		if ($mysqli->error)
		{
			return static::errors_push("Failed to delete from $table where $column = $id: " . $mysqli->error . ".", ErrorReporter::ERRCODE_DATABASE);
		}
		
		if (isset(static::$instances_by_id[$id])) unset(static::$instances_by_id[$id]);
		return $instance;
	}
	
	protected static function count($table, $column, $id)
	{
		$id = intval($id, 10);
		
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query("SELECT COUNT($column) AS count FROM $table WHERE $column = $id GROUP BY $column");
		
		if (!!$mysqli->error)
		{
			return static::errors_push("Failed to count $table where $column = $id: " . $mysqli->error . ".", ErrorReporter::ERRCODE_DATABASE);
		}
		
		if ($result->num_rows == 0) return 0;
		
		if (!($result_assoc = $result->fetch_assoc()))
		{
			return static::errors_push("Failed to count $table where $column = $id.", ErrorReporter::ERRCODE_DATABASE);
		}
		
		return intval($result_assoc["count"], 10);
	}
	
	protected static function assoc_contains_keys($assoc, $keys)
	{
		if (!isset($assoc) || !$assoc || !is_array($assoc))
		{
			return static::errors_push("Invalid result_assoc: " . $assoc . ".", ErrorReporter::ERRCODE_UNKNOWN);
		}
		
		$keys_missing = array_diff($keys, array_keys($assoc));
		
		if (count($keys_missing) > 0)
		{
			return static::errors_push("Missing keys in result_assoc: " . implode(", ", $keys_missing) . ".", ErrorReporter::ERRCODE_UNKNOWN);
		}
		
		return true;
	}
	
	public function uncache_all()
	{
	}
	
	protected static function collect($member_class, $table, $anchor_column, $anchor_id, $columns = "*", $order_by = null, $key_by = null)
	{
		$cache = array ();
			
		$anchor_id = intval($anchor_id, 10);
		
		$mysqli = Connection::get_shared_instance();
	
		$result = $mysqli->query("SELECT $columns FROM $table WHERE $anchor_column = $anchor_id $order_by");
		
		if ($mysqli->error)
		{
			return static::errors_push("Failed to select $columns from $table where $anchor_column = $anchor_id $order_by: " . $mysqli->error . ".", ErrorReporter::ERRCODE_DATABASE);
		}
		
		while (($result_assoc = $result->fetch_assoc()))
		{
			if (!($member = $member_class::from_mysql_result_assoc($result_assoc)))
			{
				unset ($cache);
				return static::errors_push("Failed to select from $table where $anchor_column = $anchor_id: " . $member_class::errors_unset(), ErrorReporter::ERRCODE_UNKNOWN);
			}
			$cache[$key_by !== null ? $result_assoc[$key_by] : count($cache)] = $member;
		}
		
		return $cache;
	}
	
	protected static function cache(&$cache, $member_class, $table, $anchor_column, $anchor_id, $columns = "*", $order_by = null, $key_by = null)
	{
		if (!isset($cache))
		{
			$cache = self::collect($member_class, $table, $anchor_column, $anchor_id, $columns, $order_by, $key_by);
		}
		
		return $cache;
	}
	
	protected static function update_this($instance, $table, $assignments, $id_column, $id, $override_safety = false)
	{
		$mysqli = Connection::get_shared_instance();
		
		$assignments_sql = array ();
		foreach ($assignments as $column => $value)
		{
			if (!$override_safety)
			{
				if (is_string($value))
				{
					$value = "'".$mysqli->escape_string($value)."'";
				}
				else
				{
					$value = intval($value, 10);
				}
			}
			
			array_push($assignments_sql, "$column = $value");
		}
		$assignments_sql = implode(", ", $assignments_sql);
		
		$failure_message = "Failed to update $table setting $assignments_sql where $id_column = $id";
		
		if (!$instance->session_user_can_write())
		{
			return static::errors_push("$failure_message: Session user is not owner.", ErrorReporter::ERRCODE_PERMISSIONS);
		}
		
		$id = intval($id, 10);
		
		$result = $mysqli->query("UPDATE $table SET $assignments_sql WHERE $id_column = $id");
		
		return !$mysqli->error ? $instance : static::errors_push("$failure_message: " . $mysqli->error . ".", ErrorReporter::ERRCODE_DATABASE);
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
	
	public function user_can_read($user)
	{
		return $this->user_can_write($user);
	}
	public function user_can_write($user)
	{
		return $this->user_is_owner($user);
	}
	public function user_can_execute($user)
	{
		return null;
	}
	
	public function session_user_can_read()
	{
		return !!Session::get() && $this->user_can_read(Session::get()->get_user());
	}
	public function session_user_can_write()
	{
		return !!Session::get() && $this->user_can_write(Session::get()->get_user());
	}
	public function session_user_can_execute()
	{
		return !!Session::get() && $this->user_can_execute(Session::get()->get_user());
	}
	
	public function json_assoc($privacy = null)
	{
		return null;
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		return $this->json_assoc($privacy);
	}
	
	protected function privacy()
	{
		return $this->session_user_can_write() ? false : !$this->session_user_can_read();
	}
	
	protected function privacy_mask($array, $exceptions = array (), $privacy = null)
	{
		if ($privacy === null) $privacy = $this->privacy();
		
		if ($privacy)
		{
			foreach ($array as $key => $value)
			{
				if (!in_array($key, $exceptions)) $array[$key] = null;
			}
		}
		
		$array["hiddenFromSessionUser"] = $privacy;
		
		$array["sessionUserPermissions"] = array (
			"read" => $this->session_user_can_read(),
			"write" => $this->session_user_can_write(),
			"execute" => $this->session_user_can_execute()
		);
		
		return $array;
	}
	
	protected static function json_array($array)
	{
		if (!is_array($array))
		{
			return static::errors_push("Back end expected associative array of DatabaseRow objects but received '$array'.", ErrorReporter::ERRCODE_UNKNOWN);
		}
		
		$assocs = array ();
		foreach ($array as $item)
		{
			if (!is_subclass_of($item, "DatabaseRow"))
			{
				return static::errors_push("Back end expected associative array of DatabaseRow objects, but one such object was '$item'.", ErrorReporter::ERRCODE_UNKNOWN);
			}
			array_push($assocs, $item->json_assoc());
		}
		
		return $assocs;
	}
}

?>