<?php

class Language extends DatabaseRow
{
	/***    CLASS/STATIC    ***/
	protected static $instances_by_id = array ();
	protected static $error_description = null;
	
	public static function select_by_id($lang_id)
	{
		return parent::select("languages", "lang_id", $lang_id);
	}
	
	public static function select_by_code($lang_code)
	{
		return parent::select("languages", "lang_code", $lang_code);
	}
	
	public static function select_all()
	{
		$mysqli = Connection::get_shared_instance();
		
		$result = $mysqli->query("SELECT * FROM languages");
		
		$languages = array ();
		
		while (($result_assoc = $result->fetch_assoc()))
		{
			array_push($languages, self::from_mysql_result_assoc($result_assoc));
		}
		
		return $languages;
	}
	
	/***    INSTANCE    ***/

	protected $lang_id = null;
	public function get_lang_id()
	{
		return $this->lang_id;
	}
	
	protected $lang_code = null;
	public function get_lang_code()
	{
		return $this->lang_code;
	}

	private $names;
	public function get_names()
	{
		if (!isset($this->names))
		{
			$this->names = array ();
			
			$mysqli = Connection::get_shared_instance();
			
			$naming = "(SELECT lang_id AS lang_id_name, lang_code AS lang_code_name FROM languages) AS language_naming";
			
			$result = $mysqli->query("SELECT * FROM (language_names CROSS JOIN languages USING (lang_id)) LEFT JOIN $naming USING (lang_id_name)");
			
			while (($result_assoc = $result->fetch_assoc()))
			{
				if ($result_assoc["lang_id"] === "".$this->get_lang_id())
				{
					$this->names[$result_assoc["lang_code_name"]] = $result_assoc["name"];
				}
			}
		}
		
		return $this->names;
	}

	private function __construct($lang_id, $lang_code)
	{
		$this->lang_id = intval($lang_id, 10);
		$this->lang_code = $lang_code;
		
		static::$instances_by_id[$this->lang_id] = $this;
		static::$instances_by_id[$this->lang_code] = $this;
	}
	
	public static function from_mysql_result_assoc($result_assoc)
	{
		$mysql_columns = array (
			"lang_id",
			"lang_code"
		);
		
		return self::assoc_contains_keys($result_assoc, $mysql_columns)
			? new self(
				$result_assoc["lang_id"],
				$result_assoc["lang_code"]
			)
			: null;
	}

	public function json_assoc($privacy = null)
	{
		return array (
			"code" => $this->get_lang_code(),
			"names" => $this->get_names()
		);
	}
	
	public function json_assoc_detailed($privacy = null)
	{
		return parent::json_assoc_detailed($privacy);
	}
}

?>