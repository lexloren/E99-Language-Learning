<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Grade extends DatabaseRow
{
	/***    STATIC/CLASS    ***/
	protected static $errors = null;
	protected static $instances_by_id = array ();
	
	public static function insert($point, $desc_short = null, $desc_long = null)
	{
		return static::errors_push("Grade insertion forbidden.");
	}

	public static function select_by_id($grade_id)
	{
		return parent::select("grades", "grade_id", $grade_id);
	}

	public static function select_by_point($point)
	{
		return parent::select("grades", "point", $point);
	}

	/***    INSTANCE    ***/

	private $grade_id = null;
	public function get_grade_id()
	{
		return $this->grade_id;
	}

	private $point = null;
	public function get_point()
	{
		return $this->point;
	}

	private $desc_short = null;
	public function get_desc_short()
	{
		return $this->desc_short;
	}

	private $desc_long = null;
	public function get_desc_long()
	{
		return $this->desc_long;
	}

	private function __construct($grade_id, $point, $desc_short = null, $desc_long = null)
	{
		$this->grade_id = intval($grade_id, 10);
		$this->point = intval($point, 10);
		$this->desc_short = !!$desc_short && strlen($desc_short) > 0 ? $desc_short : null;
		$this->desc_long = !!$desc_long && strlen($desc_long) > 0 ? $desc_long : null;

		self::register($this->grade_id, $this);
	}

	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array ("grade_id", "point", "desc_short", "desc_long");
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["grade_id"],
				$result_assoc["point"],
				!!$result_assoc["desc_short"] && strlen($result_assoc["desc_short"]) > 0 ? $result_assoc["desc_short"] : null,
				!!$result_assoc["desc_long"] && strlen($result_assoc["desc_long"]) > 0 ? $result_assoc["desc_long"] : null
			)
			: null;
	}

	public function delete()
	{
		return static::errors_push("Grade deletion forbidden.");
	}

	public function json_assoc($privacy = null)
	{
		$assoc = array (
			"gradeId" => $this->get_grade_id(),
			"point" => $this->get_point(),
			"descriptionShort" => $this->get_desc_short(),
			"descriptionLong" => $this->get_desc_long()
		);
		
		return $this->prune($assoc, array_keys($assoc), false);
	}
}

?>
