<?php

require_once './backend/connection.php';
require_once './tools/database.php';

class TestDB
{
	//users table
	public $user_ids = array ();
	public $emails = array ();
	public $handles = array ();
	public $passwords = array ();
	public $names_family = array ();
	public $names_given = array ();
	public $sessions = array ();

	//languages table
	public static $lang_id_0;
	public static $lang_id_1;
	public static $lang_code_0 = 'en';
	public static $lang_code_1 = 'cn';
	
	//dictionary table
	public $entry_ids = array ();
	public $word_0s = array ();
	public $word_1s = array ();
	public $word_1_pronuns = array ();
	
	public static $word_0 = 'Peace';
	public static $word_1 = 'Peace in CN';
	public static $word_1_pronun = 'Peace pronun in CN';
	
	public $user_entry_ids = array ();
	public $annotation_ids = array ();
	
	public static $entry_annotation = 'Some user annotation';

	//lists table
	public $list_ids = array ();
	public $list_names =  array ();
	private static $list_name = 'somelist';

	//courses table
	public $course_ids = array ();
	public $course_names = array ();
	public $course_messages = array ();
	public $course_unit_ids = array ();
	public $course_tests = array ();
	private static $course_name = 'some course';
	private static $course_message = 'some course message';
	private static $course_unit_name = 'some unit';
	
	public $practice_list_ids = array ();
	public $practice_user_entry_ids = array ();
	public $practice_entry_ids = array();
	public $practice_ids = array();

	public $test_entry_ids = array();
	public $test_user_entry_ids = array();

	public $grade_ids = array ();
	public $mode_ids = array ();

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
		Annotation::reset();
	
		$testdb = new TestDB();
		
		$link = database::recreate_database('cscie99test');
		$testdb->link = $link;
		Connection::set_mysqli($testdb->link);

	 	$testdb->add_languages();
		$testdb->add_grades();
		$testdb->add_modes();

		return $testdb;
	
	}

	public function add_users($num)
	{
		$link = $this->link;
		for ($i = 0; $i < $num; $i++) 
		{
			$suffix = count($this->user_ids);
			
			array_push($this->emails, 'email'.$suffix.'@domain.com');
			array_push($this->handles, 'handle'.$suffix);
			array_push($this->passwords, 'P@ssword'.$suffix);
			array_push($this->names_family, 'SomeFamily'.$suffix);
			array_push($this->names_given, 'SomeGiven'.$suffix);
			array_push($this->sessions, 'somesessionid'.$suffix);
			
			$link->query(sprintf("INSERT INTO users (handle, email, pswd_hash, name_given, name_family, session) VALUES ('%s', '%s', PASSWORD('%s'), '%s', '%s', '%s')",
			$link->escape_string($this->handles[$suffix]),
			$link->escape_string($this->emails[$suffix]),
			$link->escape_string($this->passwords[$suffix]),
			$link->escape_string($this->names_given[$suffix]),
			$link->escape_string($this->names_family[$suffix]),
			$link->escape_string($this->sessions[$suffix])
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


		$link->query(sprintf("INSERT INTO language_names (lang_id, lang_id_name, name) VALUES (%d, %d, '%s')",
			self::$lang_id_0, self::$lang_id_0, $link->escape_string('English in English')));
		
		$link->query(sprintf("INSERT INTO language_names (lang_id, lang_id_name, name) VALUES (%d, %d, '%s')",
			self::$lang_id_0, self::$lang_id_1, $link->escape_string('English in Chinese')));
			
		$link->query(sprintf("INSERT INTO language_names (lang_id, lang_id_name, name) VALUES (%d, %d, '%s')",
			self::$lang_id_1, self::$lang_id_0, $link->escape_string('Chinese in English')));

		$link->query(sprintf("INSERT INTO language_names (lang_id, lang_id_name, name) VALUES (%d, %d, '%s')",
			self::$lang_id_1, self::$lang_id_1, $link->escape_string('Chinese in Chinese')));
	}
	
	public function add_list($user_id, $entry_ids, $is_public=0)
	{
		$suffix = count($this->list_ids);
		$name = self::$list_name.$suffix;
		
		$link = $this->link;
		$link->query(sprintf("INSERT INTO lists (user_id, name, public) VALUES (%d, '%s', %d)",
			$user_id,
			$link->escape_string($name),
			$is_public
		));

		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);
		
		$list_id = $link->insert_id;

		array_push($this->list_ids, $list_id);
		array_push($this->list_names, $name);
		
	
		foreach($entry_ids as $entry_id)
		{
			$user_entry_id = 0;
			$result = $link->query(sprintf("SELECT user_entry_id FROM user_entries WHERE entry_id = %d AND user_id = %d",
					$entry_id, $user_id
				));
			if (!!$result && $result->num_rows == 1 && !!($result_assoc = $result->fetch_assoc()))
			{
				$user_entry_id = $result_assoc["user_entry_id"];
			}
			else
			{
				$link->query(sprintf("INSERT INTO user_entries (entry_id, user_id, word_0, word_1, word_1_pronun) SELECT entry_id, %d, word_0, word_1, word_1_pronun FROM dictionary WHERE entry_id = %d",
					$user_id, $entry_id
				));
				if (!$link->insert_id)
					exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);

				$user_entry_id = $link->insert_id;
				array_push($this->user_entry_ids, $user_entry_id);

				if (!$link->insert_id)
					exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);

				$link->query(sprintf("INSERT INTO user_entry_annotations (user_entry_id, contents) VALUES (%d, '%s')",
					$user_entry_id, $link->escape_string(self::$entry_annotation)
				));

				if (!$link->insert_id)
					exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);
				array_push($this->annotation_ids, $link->insert_id);
			}
			
			$link->query(sprintf("INSERT INTO list_entries (list_id, user_entry_id) VALUES (%d, %d)",
				$list_id,
				$user_entry_id
			));
		}
		
		return $list_id;
	}
	
	public function add_dictionary_entries($num_words)
	{
		$count_start = count($this->word_0s);
		$count_end = $count_start + $num_words;
		$link = $this->link;
		$added_entries = array ();
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
	
	public function add_course($user_id, $is_public=0)
	{
		$link = $this->link;
		$suffix = count($this->course_ids);
		$name = self::$course_name.$suffix;
		$message = self::$course_message.$suffix;
		
		$link->query(sprintf("INSERT INTO courses (user_id, name, lang_id_0, lang_id_1, message, public) VALUES (%d, '%s', %d, %d, '%s', %d)",
			$user_id,
			$link->escape_string($name),
			self::$lang_id_0,
			self::$lang_id_1,
			$link->escape_string($message),
			$is_public
		));

		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);
		
		$course_id = $link->insert_id;
		array_push($this->course_ids, $course_id);
		array_push($this->course_names, $name);
		array_push($this->course_messages, $message);
		
		$link->query(sprintf("INSERT INTO course_instructors (course_id, user_id) VALUES (%d, %d)",
			$course_id,
			$user_id
		));
		
		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);
			
		return $course_id;
	}

	public function add_course_unit($course_id)
	{
		$suffix = count($this->course_unit_ids);
		$this->link->query(sprintf("INSERT INTO course_units (course_id, name, num) VALUES (%d, '%s', %d)",
			$course_id,
			self::$course_unit_name.$suffix,
			$suffix
		));
		
		if (!$this->link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);
		
		array_push($this->course_unit_ids, $this->link->insert_id);
		return $this->link->insert_id;
	}
	
	public function add_unit_list($unit_id, $list_id)
	{
		$this->link->query(sprintf("INSERT IGNORE INTO course_unit_lists (unit_id, list_id) VALUES (%d, %d)",
			$unit_id,
			$list_id
		));
	}
	
	private function add_practice_data_for_list_one_time($user_id, $list_id, $mode_id)
	{
		$result = $this->link->query("SELECT user_entry_id FROM list_entries WHERE list_id = ".$list_id);
		
		$num_rows = $result->num_rows;
		
		for ($i=0; $i<$num_rows; $i++) 
		{
			$result_assoc = $result->fetch_assoc();
			//print_r($result_assoc);
			
			$this->link->query(sprintf("INSERT IGNORE INTO user_entries (user_id, entry_id, word_0, word_1, word_1_pronun) SELECT %d, entry_id, word_0, word_1, word_1_pronun FROM user_entries WHERE user_entry_id = %d",
				$user_id,
				$result_assoc['user_entry_id']
			));
			
			if (!!$this->link->error)
				exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);
			
			$user_entry_id = 0;
			if (!$this->link->insert_id)
			{
				$sql = sprintf("SELECT user_entry_id FROM user_entries WHERE user_id = %d AND entry_id IN (SELECT entry_id FROM user_entries WHERE user_entry_id = %d)",
				$user_id,
				$result_assoc['user_entry_id']
				);
				
				//print $sql;
				
				$result1 = $this->link->query($sql);
				
				if (!!$this->link->error)
					exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);

				$result_assoc1 = $result1->fetch_assoc();
				$user_entry_id = $result_assoc1['user_entry_id'];
			}
			else
				$user_entry_id = $this->link->insert_id;
				
			if ($user_entry_id == 0)
				exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': ');
			
			$grade_indx = rand(0, count($this->grade_ids) - 1);
			
			$this->link->query(sprintf("INSERT INTO user_entry_results (user_entry_id, grade_id, mode) VALUES (%d, %d, %d)",
				$user_entry_id,
				$this->grade_ids[$grade_indx],
				$mode_id
			));

			if (!$this->link->insert_id)
				exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);

		}
	}
	
	public function add_course_student($course_id, $user_id)
	{
		$this->link->query(sprintf("INSERT INTO course_students (course_id, user_id) VALUES (%d, %d)",
			$course_id,
			$user_id
		));
		
		if (!$this->link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);
		return $this->link->insert_id;
	}
	
	public function add_course_researcher($course_id, $user_id)
	{
		$this->link->query(sprintf("INSERT INTO course_researchers (course_id, user_id) VALUES (%d, %d)",
			$course_id,
			$user_id
		));
		
		if (!$this->link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);
		return $this->link->insert_id;
	}

	public function add_practice_data_for_list($list_id, $user_id, $count, $mode_id)
	{
		for($j=0; $j<$count; $j++)
		{
			$this->add_practice_data_for_list_one_time($user_id, $list_id, $mode_id);
		}
	}
	
	public function add_course_instructor($course_id, $user_id)
	{
		$this->link->query(sprintf("INSERT INTO course_instructors (course_id, user_id) VALUES (%d, %d)",
			$course_id,
			$user_id
		));
		
		if (!$this->link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);
	}
	
	public function add_unit_test($unit_id, $open=null, $close=null)
	{
		$test_name = "course test ".count($this->course_tests);
		$link = $this->link;
		
		if (!!$open && !!$close)
		{
			$link->query("INSERT INTO course_unit_tests (unit_id, name, open, close) VALUES ($unit_id, '$test_name', $open, $close)");
		}
		else
		{
			$link->query(sprintf("INSERT INTO course_unit_tests (unit_id, name) VALUES (%d, %d)",
				$unit_id,
				$test_name
			));
		}
		
		if (!$link->insert_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);
			
		array_push($this->course_tests, $link->insert_id);
		return $link->insert_id;
	}

	public function add_unit_test_entries($test_id, $user_id, $num_entries)
	{
		$link = $this->link;
		for ($j = 1; $j <= $num_entries; $j++)
		{
			$link->query(sprintf("INSERT INTO dictionary (lang_id_0, lang_id_1, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
				self::$lang_id_0, self::$lang_id_1, self::$word_0."-test-".$j, self::$word_1."-test-".$j, self::$word_1_pronun."-test-".$j));

			$entry_id = $link->insert_id;

			$link->query(sprintf("INSERT INTO user_entries (entry_id, user_id, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
						$entry_id, $user_id, self::$word_0."-test-".$j, self::$word_1."-test-".$j, self::$word_1_pronun."-test-".$j
					));

			$user_entry_id = $link->insert_id;
			array_push($this->test_user_entry_ids, $user_entry_id);

			$link->query("INSERT INTO course_unit_test_entries (test_id, user_entry_id, num) VALUES ($test_id, $user_entry_id, $j)");
			$test_entry_id = $link->insert_id;
			if (!$test_entry_id)
				exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$link->error);
				
			array_push($this->test_entry_ids, $test_entry_id);
		}
		return $this->test_entry_ids;
	}

	public function add_unit_test_sittings($test_id, $student_id)
	{
		$link = $this->link;
		$start = time() - (60 * 60);
		$stop = time();
		
		$link->query("INSERT INTO course_unit_test_sittings (test_id, start, stop, student_id) VALUES($test_id, $stop, $start, $student_id)");
		$sitting_id = $link->insert_id;
		if (!$sitting_id)
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);

		return $sitting_id;
	}
	
	public function add_unit_test_sitting_responses($sitting_id, $scores)
	{
		$result = $this->link->query("SELECT * FROM course_unit_test_sittings WHERE sitting_id = $sitting_id");
		
		if (!!$result && $result->num_rows == 1 && !!($result_assoc = $result->fetch_assoc()))
		{
			$test_id = $result_assoc["test_id"];
			$user_id = $result_assoc["student_id"];
			
			$result = $this->link->query("SELECT * FROM course_unit_test_entries WHERE test_id = $test_id");
		
			while (!!($result_assoc = $result->fetch_assoc()))
			{
				$user_entry_id = $result_assoc["user_entry_id"];
				$test_entry_id = $result_assoc["test_entry_id"];
				$mode = $result_assoc["mode"];
				$prompt = 0;
				$score = $scores[$test_entry_id];
				$contents = "content x";
				$this->link->query("INSERT IGNORE INTO course_unit_test_entry_patterns (test_entry_id, mode, prompt, contents, score) VALUES ($test_entry_id, $mode, $prompt, '$contents', $score)" );
				if (!!$this->link->error)
					exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);

				$pattern_id = $this->link->insert_id;
				if (!$pattern_id)
				{
					$result_pattern = $this->link->query("SELECT pattern_id FROM course_unit_test_entry_patterns WHERE test_entry_id = $test_entry_id AND mode = $mode AND prompt = $prompt AND contents = '$contents' AND score = $score");
					if (!!$this->link->error)
						exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);
					$assoc = $result_pattern->fetch_assoc();
					$pattern_id = $assoc["pattern_id"];
				}
				$this->link->query("INSERT INTO course_unit_test_sitting_responses (sitting_id, pattern_id) VALUES ($sitting_id, $pattern_id)");
				if (!!$this->link->error)
					exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);

			}
		}
		else
			exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.': '.$this->link->error);
	}
	
	public function add_practice_data($user_id, $num_lists, $num_entries)
	{
		$link = $this->link;
		for ($i = 1; $i <= $num_lists; $i++)
		{
			$link->query(sprintf("INSERT INTO lists (user_id, name, public) VALUES (%d, '%s', 1)",
								 $user_id,
								 $link->escape_string(self::$list_name).$i
								 ));
			$list_id = $link->insert_id;
			array_push($this->practice_list_ids, $list_id);
			
			for ($j = 1; $j <= $num_entries; $j++)
			{
				$link->query(sprintf("INSERT INTO dictionary (lang_id_0, lang_id_1, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
					self::$lang_id_0, self::$lang_id_1, self::$word_0.$i.$j, self::$word_1.$i.$j, self::$word_1_pronun.$i.$j));
				
				$entry_id = $link->insert_id;
				array_push($this->practice_entry_ids, $entry_id);
				
				$link->query(sprintf("INSERT INTO user_entries (entry_id, user_id, word_0, word_1, word_1_pronun) VALUES (%d, %d, '%s', '%s', '%s')",
						$entry_id, $user_id, self::$word_0.$i.$j, self::$word_1.$i.$j, self::$word_1_pronun.$i.$j
						));
				
				$user_entry_id = $link->insert_id;
				array_push($this->practice_user_entry_ids, $user_entry_id);
				
				$link->query(sprintf("INSERT INTO list_entries (list_id, user_entry_id) VALUES (%d, %d)",
									 $list_id,
									 $user_entry_id
									 ));

				if (!$link->insert_id)
					exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);
				foreach($this->mode_ids as $mode) 
				{
					$link->query(sprintf("INSERT INTO user_practice (user_entry_id, mode) VALUES(%d, %d)",
                                                                        $user_entry_id,
									$mode
                                                                        ));
					$practice_id = $link->insert_id;
					if (!$link->insert_id)
	                                        exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);
					array_push($this->practice_ids, $practice_id);
				}
			}
		}
	}
	
	private function add_grades()
	{
		$link = $this->link;
		$grade_entries = array ();
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
                for ($i = 0; $i < count($grade_entries); $i ++)
		{
			$grade = $grade_entries[$i];
			$link->query(sprintf(
						"INSERT into grades values (%d, %d, '%s', '%s')",
						$i+1, $grade["point"], $grade["desc_short"], $grade["desc_long"])
						 );
			if (!$link->insert_id)
				exit ('Failed to create TestDB: '.__FILE__.' '.__Line__);
			
			array_push($this->grade_ids, $i+1);			
		}
	}

	private function add_modes()
	{
		$link = $this->link;
		$mode_entries = array ();
		$mode_entries[] = array(
								"from" => 'Unknown Language',
								"to" => 'Known Language'
								);
		$mode_entries[] = array(
								"from" => 'Known Language',
								"to" => 'Unknown Language'
								);
		$mode_entries[] = array(
								"from" => 'Unknown Language',
								"to" => 'Unknown Pronunciation'
								);
		$mode_entries[] = array(
								"from" => 'Unknown Pronunciation',
								"to" => 'Known Language'
								);
		$mode_entries[] = array(
								"from" => 'Unknown Pronunciation',
								"to" => 'Unknown Language'
								);
		$mode_entries[] = array(
								"from" => 'Known Language',
								"to" => 'Unknown Pronunciation'
								);
		$mode_entries[] = array(
								"from" => 'Unknown Language & Unknown Pronunciation',
								"to" => 'Known Language'
								);
		for ($i = 0; $i < count($mode_entries); $i ++)
		{
			$mode = $mode_entries[$i];
			$link->query(sprintf("INSERT INTO modes (mode_id, `from`, `to`) values ($i, '%s', '%s')",
								 $mode["from"], $mode["to"]));
			if ($link->error) exit ('Failed to create TestDB: '.__FILE__.' '.__Line__.": ".$link->error);
			array_push($this->mode_ids, $i);
		}
	}
}
?>
