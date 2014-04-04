<?php

require_once './backend/connection.php';
require_once './tools/database.php';

class TestDB
{
	//users table
	public $user_ids = Array();
	public $emails = Array();
	public $handles = Array();
	public $passwords = Array();
	public $names_family = Array();
	public $names_given = Array();
	public $sessions = Array();

	//languages table
	public static $lang_id_0;
	public static $lang_id_1;
	public static $lang_code_0 = 'en';
	public static $lang_code_1 = 'cn';
	
	//dictionary table
	public $entry_ids = Array();
	public $word_0s = Array();
	public $word_1s = Array();
	public $word_1_pronuns = Array();
	
	public static $word_0 = 'Peace';
	public static $word_1 = 'Peace in CN';
	public static $word_1_pronun = 'Peace pronun in CN';
	
	public $user_entry_ids = Array();
	public $annotation_ids = Array();
	
	private static $entry_annotation = 'Some user annotation';

	//lists table
	public $list_ids = Array();
	public $list_names =  Array();
	private static $list_name = 'somelist';

	//courses table
	public $course_ids = Array();
	public $course_names = Array();
	private static $course_name = 'some course';
	private static $course_unit_name = 'some unit';
	
	public $practice_list_ids = array();
    	public $practice_entry_ids = array();

	public $link = null;

	private function __construct()
	{
	}
	
	//Creates empty test database with data only in languages table
	public static function create()
	{
		User::reset();
		Grade::reset();
		//Entry::reset();  //Does not use the cache
		Course::reset();
		Dictionary::reset();
		Unit::reset();
		EntryList::reset();
		Test::reset();
		Section::reset();
		Annotation::reset();
	
		$testdb = new TestDB();
		
		$link = database::recreate_database('cscie99test');
		$testdb->link = $link;
		Connection::set_shared_instance($testdb->link);

	 	$testdb->add_languages();

		return $testdb;
	}

	public function add_users($num)
	{
		$link = $this->link;
		for ($i = 0; $i < $num; $i++) 
		{
			array_push($this->emails, 'email'.$i.'@domain.com');
			array_push($this->handles, 'handle'.$i);
			array_push($this->passwords, 'P@ssword'.$i);
			array_push($this->names_family, 'SomeFamily'.$i);
			array_push($this->names_given, 'SomeGiven'.$i);
			array_push($this->sessions, 'somesessionid'.$i);
			
			$link->query(sprintf("INSERT INTO users (handle, email, pswd_hash, name_given, name_family, session) VALUES ('%s', '%s', PASSWORD('%s'), '%s', '%s', '%s')",
			$link->escape_string($this->handles[$i]),
			$link->escape_string($this->emails[$i]),
			$link->escape_string($this->passwords[$i]),
			$link->escape_string($this->names_given[$i]),
			$link->escape_string($this->names_family[$i]),
			$link->escape_string($this->sessions[$i])
			));
			if (!$link->insert_id)
				exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);
			array_push($this->user_ids, $link->insert_id);
		}
	}

	private function add_languages()
	{
		$link = $this->link;
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
	
	public function add_list($user_id, $entry_ids)
	{
		$suffix = count($this->list_ids);
		$list_name = self::$list_name.$suffix;
		
		$link = $this->link;
		$link->query(sprintf("INSERT INTO lists (user_id, list_name) VALUES (%d, '%s')",
			$user_id,
			$link->escape_string($list_name)
		));

		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);
		
		array_push($this->list_ids, $link->insert_id);
		array_push($this->list_names, $list_name);
		
		$list_id = $link->insert_id;
	
		foreach($entry_ids as $entry_id)
		{
			$link->query(sprintf("INSERT INTO user_entries (entry_id, user_id) VALUES (%d, %d)",
				$entry_id, $user_id
			));
			
			$user_entry_id = $link->insert_id;
			array_push($this->user_entry_ids, $user_entry_id);

			$link->query(sprintf("INSERT INTO list_entries (list_id, user_entry_id) VALUES (%d, %d)",
				$list_id,
				$user_entry_id
			));

			$link->query(sprintf("INSERT INTO user_entry_annotations (user_entry_id, contents) VALUES (%d, '%s')",
				$user_entry_id, $link->escape_string(self::$entry_annotation)
			));

			array_push($this->annotation_ids, $link->insert_id);
		}
		
		return $list_id;
	}
	
	public function add_dictionary_entries($num_words)
	{
		$count_start = count($this->word_0s);
		$count_end = $count_start + $num_words;
		$link = $this->link;
		$added_entries = Array();
		for ($i = $count_start; $i < $count_end; $i++) 
		{
			array_push($this->word_0s, self::$word_0.$i);
			array_push($this->word_1s, self::$word_1.$i);
			array_push($this->word_1_pronuns, self::$word_1_pronun.$i);
			
			$link->query(sprintf("INSERT INTO dictionary (lang_id_0, lang_id_1, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
				self::$lang_id_0, self::$lang_id_1, $this->word_0s[$i], $this->word_1s[$i], $this->word_1_pronuns[$i]));
				
			if (!$link->insert_id)
				exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);

			array_push($this->entry_ids, $link->insert_id);
			array_push($added_entries, $link->insert_id);
		}
		return $added_entries;
	}
	
	public function add_course($user_id)
	{
		$link = $this->link;
		$suffix = count($this->course_ids);
		$course_name = self::$course_name.$suffix;
		
		$link->query(sprintf("INSERT INTO courses (user_id, course_name, lang_id_0, lang_id_1) VALUES (%d, '%s', %d, %d)",
			$user_id,
			$link->escape_string($course_name),
			self::$lang_id_0,
			self::$lang_id_1
		));

		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);
		
		$course_id = $link->insert_id;
		array_push($this->course_ids, $course_id);
		array_push($this->course_names, $course_name);
		
		$link->query(sprintf("INSERT INTO course_units (course_id, unit_name, unit_num) VALUES (%d, '%s', %d)",
			$course_id,
			self::$course_unit_name,
			1
		));
		
		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);
		
		$course_unit_id = $link->insert_id;
		
		$list_id = $this->add_list($user_id, $this->entry_ids);

		$link->query(sprintf("INSERT IGNORE INTO course_unit_lists (unit_id, list_id) VALUES (%d, %d)",
			$course_unit_id,
			$list_id
		));
		
		return $course_id;
	}

	public function add_practice_data($user_id, $num_lists, $num_entries)
        {
		$link = $this->link;
                for ($i = 1; $i <= $num_lists; $i++)
                {
                        $link->query(sprintf("INSERT INTO lists (user_id, list_name) VALUES (%d, '%s')",
                                $user_id,
                                $link->escape_string(self::$list_name).$i
                        ));
                        $list_id = $link->insert_id;
                        array_push($this->practice_list_ids, $list_id);

                        for ($j = 1; $j <= $num_entries; $j++)
                        {
                                $link->query(sprintf(
                                        "INSERT INTO dictionary (lang_id_0, lang_id_1, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
                                        self::$lang_id_0, self::$lang_id_1, self::$word_0.$i.$j, self::$word_1.$i.$j, self::$word_1_pronun.$i.$j));

                                $entry_id = $link->insert_id;
                                array_push($this->practice_entry_ids, $entry_id);

                                $link->query(sprintf("INSERT INTO user_entries (entry_id, user_id, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
                                        $entry_id, $user_id, self::$word_0.$i.$j, self::$word_1.$i.$j, self::$word_1_pronun.$i.$j
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

        public function add_grades()
        {
		$link = $this->link;
                $grade_entries = array();
                $grade_entries[] = array(
                        "point" => 0,
                        "desc_short" => 'No-Clue',
                        "desc_long" => 'Complete blackout; Student doesn’t even recall ever knowing the answer'
                );
                $grade_entries[] = array(
                        "point" => 1,
                        "desc_short" => 'Fail',
                        "desc_long" => 'Wrong answer; but the student seems to be familiar with the correct answer'
                );
                $grade_entries[] = array(
                        "point" => 2,
                        "desc_short" => 'Ok',
                        "desc_long" => 'Wrong answer; but showing the answer makes the student to go like ‘I knew it!’'
                );
                $grade_entries[] = array(
                        "point" => 3,
                        "desc_short" => 'Fine',
                        "desc_long" => 'Correct answer; student gave the correct response after some consideration'
                );
                $grade_entries[] = array(
                        "point" => 4,
                        "desc_short" => 'Nailed-it',
                        "desc_long" => 'Correct answer; student answered it right away without any hesitation'
                );
                foreach ($grade_entries as $grade)
                {
                        $link->query(sprintf(
                                "INSERT into grades (point, desc_short, desc_long) values (%d, '%s', '%s')",
                                $grade["point"], $grade["desc_short"], $grade["desc_long"])
                        );
                         if (!$link->insert_id)
                                exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);

                }
        }

}
	

?>
