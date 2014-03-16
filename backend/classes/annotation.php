<?php

require_once "./backend/connection.php";
require_once "./backend/support.php";

class Annotation
{
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
	
	private $entry_id;
	public function get_entry_id()
	{
		return $this->entry_id;
	}
	
	private $user_id;
	public function get_user_id()
	{
		return $this->user_id;
	}
	
	private function __construct($annotation_id, $entry_id, $user_id, $contents)
	{
		$this->annotation_id = intval($annotation_id, 10);
		$this->entry_id = intval($entry_id, 10);
		$this->user_id = intval($user_id, 10);
		$this->contents = $contents;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		return new Annotation(
			$result_assoc["annotation_id"],
			$result_assoc["entry_id"],
			$result_assoc["user_id"],
			$result_assoc["contents"]
		);
	}
	
	public function delete()
	{
		if (!Session::get_user()) return null;
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("DELETE FROM user_entry_annotations WHERE user_id = %d AND annotation_id = %d",
			Session::get_user()->get_user_id(),
			$this->get_annotation_id()
		));
		
		return null;
	}
	
	public function assoc_for_json()
	{
		return array (
			"annotationId" => $this->annotation_id,
			"contents" => $this->contents
		);
	}
}

?>