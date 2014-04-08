<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Annotation extends DatabaseRow
{
	protected static $error_description = null;
	protected static $instances_by_id = array ();
	
	public static function select_by_id($annotation_id)
	{
		return parent::select("user_entry_annotations LEFT JOIN user_entries USING (user_entry_id)", "annotation_id", $annotation_id);
	}

	private $contents;
	public function get_contents()
	{
		return $this->contents;
	}
	
	private $annotation_id;
	public function get_annotation_id()
	{
		return $this->annotation_id;
	}
	
	private $user_entry_id;
	public function get_user_entry_id()
	{
		return $this->user_entry_id;
	}
	public function get_user_entry()
	{
		return UserEntry::select_by_user_entry_id($this->get_user_entry_id());
	}
	
	private $user_id;
	public function get_user_id()
	{
		return $this->get_user_entry()->get_user_id();
	}
	public function get_owner()
	{
		return User::select_by_id($this->get_user_id());
	}
	
	private function __construct($annotation_id, $user_entry_id, $contents)
	{
		$this->annotation_id = intval($annotation_id, 10);
		$this->user_entry_id = intval($user_entry_id, 10);
		$this->contents = $contents;
		
		self::register($this->annotation_id, $this);
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"annotation_id",
			"user_entry_id",
			"contents"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new Annotation(
				$result_assoc["annotation_id"],
				$result_assoc["user_entry_id"],
				$result_assoc["contents"]
			)
			: null;
	}
	
	public static function insert($user_entry_id, $contents)
	{
		$user_entry_id = intval($user_entry_id, 10);
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO user_entry_annotations (user_entry_id, contents) VALUES (%d, '%s')",
			$user_entry_id,
			$mysqli->escape_string($contents)
		));
		
		if (!!$mysqli->error)
		{
			return Annotation::set_error_description("Failed to insert annotation: " . $mysqli->error . ".");
		}
		
		return self::select_by_id($mysqli->insert_id);
	}
	
	public function delete()
	{
		return self::delete_this($this, "user_entry_annotations", "annotation_id", $this->get_annotation_id());
	}
	
	public function assoc_for_json($privacy = null)
	{
		return $this->privacy_mask(array (
			"annotationId" => $this->annotation_id,
			"contents" => !$privacy ? $this->contents : null
		), array (0 => "annotationId"), $privacy);
	}
}

?>