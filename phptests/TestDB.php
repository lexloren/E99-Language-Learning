<?php

require_once './backend/connection.php';
require_once './tools/database.php';

class TestDB
{
	public static $user_id;
	public static $user_entry_id;
	public static $email = 'email@domain.com';
	public static $handle = 'username';
	public static $password = 'P@ssword1';
	public static $name_family = 'SomeFamily';
	public static $name_given = 'SomeGiven';
	public static $session = 'somesessionid';

	public static $lang_id_0;
	public static $lang_id_1;
	public static $lang_code_0 = 'en';
	public static $lang_code_1 = 'cn';
	
	public static $entry_id;
	public static $word_0 = 'Peace';
	public static $word_1 = 'Peace in CN';
	public static $word_1_pronun = 'Peace pronun in CN';
	public static $entry_annotation = 'Some user annotation';
	public static $entry_annotation_id;

	public static $list_id;
	public static $list_entry_id;
	public static $list_name = 'somelist';

	public static $course_id;
	public static $course_name = 'some course';
	public static $course_unit_id;
	public static $course_unit_name = 'some unit';
	
	public static $practice_list_ids = array();
        public static $practice_entry_ids = array();

	public $link = null;

	private function __construct()
	{
	}
	
	public static function create()
	{
		User::unregister_all();
		Grade::unregister_all();
		//Entry::unregister_all();  //Does not use the cache
		Course::unregister_all();
		Unit::unregister_all();
		EntryList::unregister_all();
		Test::unregister_all();
		Section::unregister_all();
		Annotation::unregister_all();
	
		$testdb = new TestDB();
		
		$link = database::recreate_database('cscie99test');
		$testdb->link = $link;
		Connection::set_shared_instance($testdb->link);
	
		self::add_languages($link);
		self::add_dictionary($link);
		self::add_user($link);
		self::add_list($link);
		self::add_course($link);
		self::add_practice_data($link);
		
		return $testdb;
	}
	
	private static function add_user($link)
	{
		$link->query(sprintf("INSERT INTO users (handle, email, pswd_hash, name_given, name_family, session) VALUES ('%s', '%s', PASSWORD('%s'), '%s', '%s', '%s')",
			$link->escape_string(self::$handle),
			$link->escape_string(self::$email),
			$link->escape_string(self::$password),
			$link->escape_string(self::$name_given),
			$link->escape_string(self::$name_family),
			$link->escape_string(self::$session)
		));

		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);
			
		self::$user_id = $link->insert_id;
	}

	private static function add_languages($link)
	{
		$link->query(sprintf("INSERT INTO languages (lang_code) VALUES ('%s')", self::$lang_code_0));
		self::$lang_id_0 = $link->insert_id;

		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);

			$link->query(sprintf("INSERT INTO languages (lang_code) VALUES ('%s')", self::$lang_code_1));			
		self::$lang_id_1 = $link->insert_id;
		
		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);


		$link->query(sprintf("INSERT INTO language_names (lang_id, lang_id_name, lang_name) VALUES (%d, %d, '%s')",
			self::$lang_id_0, self::$lang_id_0, $link->escape_string('English in English')));
		
		$link->query(sprintf("INSERT INTO language_names (lang_id, lang_id_name, lang_name) VALUES (%d, %d, '%s')",
			self::$lang_id_0, self::$lang_id_1, $link->escape_string('English in Chinese')));
			
		$link->query(sprintf("INSERT INTO language_names (lang_id, lang_id_name, lang_name) VALUES (%d, %d, '%s')",
			self::$lang_id_1, self::$lang_id_0, $link->escape_string('Chinese in English')));

		$link->query(sprintf("INSERT INTO language_names (lang_id, lang_id_name, lang_name) VALUES (%d, %d, '%s')",
			self::$lang_id_1, self::$lang_id_1, $link->escape_string('Chinese in Chinese')));
	}
	
	private static function add_list($link)
	{
		$link->query(sprintf("INSERT INTO lists (user_id, list_name) VALUES (%d, '%s')",
			self::$user_id,
			$link->escape_string(self::$list_name)
		));

		self::$list_id = $link->insert_id;
		
		$link->query(sprintf("INSERT INTO user_entries (entry_id, user_id, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
			self::$entry_id, self::$user_id, self::$word_0, self::$word_1, self::$word_1_pronun
		));
		
		self::$user_entry_id = $link->insert_id;

		$link->query(sprintf("INSERT INTO list_entries (list_id, user_entry_id) VALUES (%d, %d)",
			self::$list_id,
			self::$user_entry_id
		));

		self::$list_entry_id = $link->insert_id;

		$link->query(sprintf("INSERT INTO user_entry_annotations (user_entry_id, contents) VALUES (%d, '%s')",
			self::$user_entry_id, $link->escape_string(self::$entry_annotation)
		));

		self::$entry_annotation_id = $link->insert_id;
	}
	
	private static function add_dictionary($link)
	{
		$link->query(sprintf("INSERT INTO dictionary (lang_id_0, lang_id_1, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
			self::$lang_id_0, self::$lang_id_1, self::$word_0, self::$word_1, self::$word_1_pronun));
			
		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);

		self::$entry_id = $link->insert_id;
	}
	
	private static function add_course($link)
	{
		//Add one course
		$link->query(sprintf("INSERT INTO courses (user_id, course_name, lang_id_0, lang_id_1) VALUES (%d, '%s', %d, %d)",
			self::$user_id,
			$link->escape_string(self::$course_name),
			self::$lang_id_0,
			self::$lang_id_1
		));

		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);
		self::$course_id = $link->insert_id;
		
		$link->query(sprintf("INSERT INTO course_units (course_id, unit_name, unit_num) VALUES (%d, '%s', %d)",
			self::$course_id,
			self::$course_unit_name,
			1
		));
		
		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);
		self::$course_unit_id = $link->insert_id;

		$link->query(sprintf("INSERT IGNORE INTO course_unit_lists (unit_id, list_id) VALUES (%d, %d)",
			self::$course_unit_id,
			self::$list_id
		));
	}

	private static function add_practice_data($link)
        {
                for ($i = 1; $i <= 2; $i++)
                {
                        $link->query(sprintf("INSERT INTO lists (user_id, list_name) VALUES (%d, '%s')",
                                self::$user_id,
                                $link->escape_string(self::$list_name).$i
                        ));
                        $list_id = $link->insert_id;
                        array_push(self::$practice_list_ids, $list_id);

                        for ($j = 1; $j <= 10; $j++)
                        {
                                $link->query(sprintf(
                                        "INSERT INTO dictionary (lang_id_0, lang_id_1, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
                                        self::$lang_id_0, self::$lang_id_1, self::$word_0.$i.$j, self::$word_1.$i.$j, self::$word_1_pronun.$i.$j));

                                $entry_id = $link->insert_id;
                                array_push(self::$practice_entry_ids, $entry_id);

                                $link->query(sprintf("INSERT INTO user_entries (entry_id, user_id, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
                                        $entry_id, self::$user_id, self::$word_0.$i.$j, self::$word_1.$i.$j, self::$word_1_pronun.$i.$j
                                ));

                                $user_entry_id = $link->insert_id;

                                $link->query(sprintf("INSERT INTO list_entries (list_id, user_entry_id) VALUES (%d, %d)",
                                        $list_id,
                                        $user_entry_id
                                ));

                                if (!$link->insert_id)
                                        exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);
                        }
                }
        }
}

	
	
	
	

?>
