<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Grade extends DatabaseRow
{
	/***    STATIC/CLASS    ***/

	private static $grades_by_id = array ();

	public static function insert()
	{
		return;
	}

	public static function select($grade_id)
	{
		$grade_id = intval($grade_id, 10);

		$mysqli = Connection::get_shared_instance();

		$result = $mysqli->query("SELECT * FROM grades WHERE grade_id = $grade_id");

		if (!!$result && $result->num_rows > 0 && !!($result_assoc = $result->fetch_assoc()))
		{
			return Grade::from_mysql_result_assoc($result_assoc);
		}

		return Grade::set_error_description("No grade matches grade_id = $grade_id.");
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

		Grade::$grades_by_id[$this->grade_id] = $this;
	}

	public static function from_mysql_result_assoc($result_assoc)
	{
		if (!$result_assoc)
		{
			return Grade::set_error_description("Invalid result_assoc.");
		}

		return new Grade(
			$result_assoc["grade_id"],
			$result_assoc["point"],
			!!$result_assoc["desc_short"] && strlen($result_assoc["desc_short"]) > 0 ? $result_assoc["desc_short"] : null,
			!!$result_assoc["desc_long"] && strlen($result_assoc["desc_long"]) > 0 ? $result_assoc["desc_long"] : null
		);
	}

	public function delete()
	{
		return;
	}

	public function assoc_for_json()
	{
		return array (
			"gradeId" => $this->get_grade_id(),
			"point" => $this->get_point(),
			"descShort" => $this->get_desc_short(),
			"descLong" => $this->get_desc_long()
		);
	}
}

?>
