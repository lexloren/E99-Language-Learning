-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- Värd: 68.178.216.146
-- Skapad: 09 maj 2014 kl 16:10
-- Serverversion: 5.0.96
-- PHP-version: 5.1.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Databas: `cscie99`
--

-- --------------------------------------------------------

--
-- Struktur för tabell `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `course_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` char(255) default NULL,
  `lang_id_0` bigint(20) unsigned NOT NULL,
  `lang_id_1` bigint(20) unsigned NOT NULL,
  `public` tinyint(1) NOT NULL default '0',
  `password` char(127) default NULL,
  `open` bigint(20) unsigned default NULL,
  `close` bigint(20) unsigned default NULL,
  `message` text,
  PRIMARY KEY  (`course_id`),
  KEY `lang_id_0` (`lang_id_0`),
  KEY `lang_id_1` (`lang_id_1`),
  KEY `open` (`open`),
  KEY `close` (`close`),
  KEY `user_id` (`user_id`),
  KEY `name` (`name`),
  KEY `public` (`public`),
  KEY `pswd_hash` (`password`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=42 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_instructors`
--

DROP TABLE IF EXISTS `course_instructors`;
CREATE TABLE IF NOT EXISTS `course_instructors` (
  `instructor_id` bigint(20) unsigned NOT NULL auto_increment,
  `course_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`instructor_id`),
  UNIQUE KEY `course_id` (`course_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=57 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_researchers`
--

DROP TABLE IF EXISTS `course_researchers`;
CREATE TABLE IF NOT EXISTS `course_researchers` (
  `researcher_id` bigint(20) unsigned NOT NULL auto_increment,
  `course_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`researcher_id`),
  UNIQUE KEY `course_id` (`course_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_students`
--

DROP TABLE IF EXISTS `course_students`;
CREATE TABLE IF NOT EXISTS `course_students` (
  `student_id` bigint(20) unsigned NOT NULL auto_increment,
  `course_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`student_id`),
  UNIQUE KEY `course_id` (`course_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=96 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_units`
--

DROP TABLE IF EXISTS `course_units`;
CREATE TABLE IF NOT EXISTS `course_units` (
  `unit_id` bigint(20) unsigned NOT NULL auto_increment,
  `course_id` bigint(20) unsigned NOT NULL,
  `num` smallint(5) unsigned NOT NULL,
  `name` char(255) default NULL,
  `open` bigint(20) unsigned default NULL,
  `close` bigint(20) unsigned default NULL,
  `message` text,
  PRIMARY KEY  (`unit_id`),
  UNIQUE KEY `course_id` (`course_id`,`num`),
  KEY `open` (`open`),
  KEY `close` (`close`),
  KEY `num` (`num`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_unit_lists`
--

DROP TABLE IF EXISTS `course_unit_lists`;
CREATE TABLE IF NOT EXISTS `course_unit_lists` (
  `unit_id` bigint(20) unsigned NOT NULL,
  `list_id` bigint(20) unsigned NOT NULL,
  `message` text,
  PRIMARY KEY  (`unit_id`,`list_id`),
  KEY `list_id` (`list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_unit_tests`
--

DROP TABLE IF EXISTS `course_unit_tests`;
CREATE TABLE IF NOT EXISTS `course_unit_tests` (
  `test_id` bigint(20) unsigned NOT NULL auto_increment,
  `unit_id` bigint(20) unsigned NOT NULL,
  `name` char(255) default NULL,
  `open` bigint(20) unsigned default NULL,
  `close` bigint(20) unsigned default NULL,
  `timer` int(10) unsigned default NULL,
  `disclosed` tinyint(1) NOT NULL default '0',
  `message` text,
  PRIMARY KEY  (`test_id`),
  KEY `unit_id` (`unit_id`),
  KEY `open` (`open`),
  KEY `close` (`close`),
  KEY `name` (`name`),
  KEY `disclosed` (`disclosed`),
  KEY `timer` (`timer`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=74 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_unit_test_entries`
--

DROP TABLE IF EXISTS `course_unit_test_entries`;
CREATE TABLE IF NOT EXISTS `course_unit_test_entries` (
  `test_entry_id` bigint(20) unsigned NOT NULL auto_increment,
  `test_id` bigint(20) unsigned NOT NULL,
  `user_entry_id` bigint(20) unsigned NOT NULL,
  `num` smallint(5) unsigned NOT NULL,
  `mode` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`test_entry_id`),
  UNIQUE KEY `test_id` (`test_id`,`num`),
  UNIQUE KEY `test_id_2` (`test_id`,`user_entry_id`),
  KEY `number` (`num`),
  KEY `user_entry_id` (`user_entry_id`),
  KEY `mode` (`mode`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=168 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_unit_test_entry_patterns`
--

DROP TABLE IF EXISTS `course_unit_test_entry_patterns`;
CREATE TABLE IF NOT EXISTS `course_unit_test_entry_patterns` (
  `pattern_id` bigint(20) unsigned NOT NULL auto_increment,
  `test_entry_id` bigint(20) unsigned NOT NULL,
  `mode` tinyint(3) unsigned NOT NULL default '1',
  `prompt` tinyint(1) NOT NULL default '0',
  `contents` char(255) default NULL,
  `score` tinyint(4) default NULL,
  `message` text,
  PRIMARY KEY  (`pattern_id`),
  UNIQUE KEY `test_entry_id` (`test_entry_id`,`mode`,`contents`),
  KEY `score` (`score`),
  KEY `prompt` (`prompt`),
  KEY `mode` (`mode`),
  KEY `contents` (`contents`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=957 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_unit_test_sittings`
--

DROP TABLE IF EXISTS `course_unit_test_sittings`;
CREATE TABLE IF NOT EXISTS `course_unit_test_sittings` (
  `sitting_id` bigint(20) unsigned NOT NULL auto_increment,
  `test_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `start` bigint(20) unsigned NOT NULL,
  `stop` bigint(20) unsigned default NULL,
  `message` text,
  PRIMARY KEY  (`sitting_id`),
  UNIQUE KEY `test_id` (`test_id`,`student_id`),
  KEY `student_id` (`student_id`),
  KEY `start` (`start`),
  KEY `stop` (`stop`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=67 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `course_unit_test_sitting_responses`
--

DROP TABLE IF EXISTS `course_unit_test_sitting_responses`;
CREATE TABLE IF NOT EXISTS `course_unit_test_sitting_responses` (
  `response_id` bigint(20) unsigned NOT NULL auto_increment,
  `sitting_id` bigint(20) unsigned NOT NULL,
  `timestamp` bigint(20) default NULL,
  `pattern_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`response_id`),
  UNIQUE KEY `sitting_id` (`sitting_id`,`pattern_id`),
  KEY `timestamp` (`timestamp`),
  KEY `pattern_id` (`pattern_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=182 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `dictionary`
--

DROP TABLE IF EXISTS `dictionary`;
CREATE TABLE IF NOT EXISTS `dictionary` (
  `entry_id` bigint(20) unsigned NOT NULL auto_increment,
  `lang_id_0` bigint(20) unsigned NOT NULL,
  `lang_id_1` bigint(20) unsigned NOT NULL,
  `word_0` char(255) NOT NULL,
  `word_1` char(255) NOT NULL,
  `word_1_pronun` char(255) default NULL,
  `user_id` bigint(20) unsigned default NULL,
  PRIMARY KEY  (`entry_id`),
  KEY `lang_id_known` (`lang_id_0`),
  KEY `lang_id_unknw` (`lang_id_1`),
  KEY `user_id` (`user_id`),
  FULLTEXT KEY `lang_unknw` (`word_1`),
  FULLTEXT KEY `lang_known` (`word_0`),
  FULLTEXT KEY `pronunciation` (`word_1_pronun`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=847826 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `dictionary_queries`
--

DROP TABLE IF EXISTS `dictionary_queries`;
CREATE TABLE IF NOT EXISTS `dictionary_queries` (
  `query_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_id` bigint(20) unsigned NOT NULL,
  `timestamp` bigint(20) unsigned NOT NULL,
  `contents` char(255) NOT NULL,
  PRIMARY KEY  (`query_id`),
  KEY `contents` (`contents`),
  KEY `user_id` (`user_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `dictionary_query_languages`
--

DROP TABLE IF EXISTS `dictionary_query_languages`;
CREATE TABLE IF NOT EXISTS `dictionary_query_languages` (
  `query_id` bigint(20) unsigned NOT NULL,
  `lang_code` char(2) NOT NULL,
  UNIQUE KEY `query_id` (`query_id`,`lang_code`),
  KEY `lang_code` (`lang_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktur för tabell `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `grade_id` bigint(20) unsigned NOT NULL auto_increment,
  `point` int(11) NOT NULL,
  `desc_short` char(255) default NULL,
  `desc_long` char(255) default NULL,
  PRIMARY KEY  (`grade_id`),
  UNIQUE KEY `grade_point` (`point`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `languages`
--

DROP TABLE IF EXISTS `languages`;
CREATE TABLE IF NOT EXISTS `languages` (
  `lang_id` bigint(20) unsigned NOT NULL auto_increment,
  `lang_code` char(2) NOT NULL,
  PRIMARY KEY  (`lang_id`),
  UNIQUE KEY `lang_code` (`lang_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `language_names`
--

DROP TABLE IF EXISTS `language_names`;
CREATE TABLE IF NOT EXISTS `language_names` (
  `lang_id` bigint(20) unsigned NOT NULL,
  `lang_id_name` bigint(20) unsigned NOT NULL,
  `name` char(63) NOT NULL,
  PRIMARY KEY  (`lang_id`,`lang_id_name`),
  KEY `lang_id` (`lang_id`),
  KEY `lang_id_name` (`lang_id_name`),
  KEY `lang_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktur för tabell `lists`
--

DROP TABLE IF EXISTS `lists`;
CREATE TABLE IF NOT EXISTS `lists` (
  `list_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` char(255) default NULL,
  `public` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`list_id`),
  KEY `user_id` (`user_id`),
  KEY `list_name` (`name`),
  KEY `share_public` (`public`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=28 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `list_entries`
--

DROP TABLE IF EXISTS `list_entries`;
CREATE TABLE IF NOT EXISTS `list_entries` (
  `list_entry_id` bigint(20) unsigned NOT NULL auto_increment,
  `list_id` bigint(20) unsigned NOT NULL,
  `user_entry_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`list_entry_id`),
  UNIQUE KEY `list_id` (`list_id`,`user_entry_id`),
  KEY `user_entry_id` (`user_entry_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=72 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `modes`
--

DROP TABLE IF EXISTS `modes`;
CREATE TABLE IF NOT EXISTS `modes` (
  `mode_id` tinyint(3) unsigned NOT NULL,
  `from` char(255) default NULL,
  `to` char(255) default NULL,
  PRIMARY KEY  (`mode_id`),
  UNIQUE KEY `from_to` (`from`,`to`),
  KEY `to` (`to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktur för tabell `outbox`
--

DROP TABLE IF EXISTS `outbox`;
CREATE TABLE IF NOT EXISTS `outbox` (
  `message_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_id` bigint(20) unsigned default NULL,
  `course_id` bigint(20) unsigned default NULL,
  `to` char(255) NOT NULL,
  `subject` char(255) NOT NULL,
  `contents` text NOT NULL,
  PRIMARY KEY  (`message_id`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=52 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` bigint(20) unsigned NOT NULL auto_increment,
  `status_id` bigint(20) unsigned default NULL,
  `handle` char(63) NOT NULL,
  `pswd_hash` char(127) NOT NULL,
  `email` char(63) NOT NULL,
  `name_given` char(255) default NULL,
  `name_family` char(255) default NULL,
  `login_token` char(255) default NULL,
  `session` char(63) default NULL,
  `timestamp` timestamp NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `handle` (`handle`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `session` (`session`),
  UNIQUE KEY `login_token` (`login_token`),
  KEY `last_activity` (`timestamp`),
  KEY `pswd_hash` (`pswd_hash`),
  KEY `status_id` (`status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=51 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `user_entries`
--

DROP TABLE IF EXISTS `user_entries`;
CREATE TABLE IF NOT EXISTS `user_entries` (
  `user_entry_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_id` bigint(20) unsigned NOT NULL,
  `entry_id` bigint(20) unsigned NOT NULL,
  `word_0` char(255) default NULL,
  `word_1` char(255) default NULL,
  `word_1_pronun` char(255) default NULL,
  PRIMARY KEY  (`user_entry_id`),
  UNIQUE KEY `entry_id` (`entry_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `word_0` (`word_0`),
  KEY `word_1` (`word_1`),
  KEY `word_1_pronun` (`word_1_pronun`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=154 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `user_entry_annotations`
--

DROP TABLE IF EXISTS `user_entry_annotations`;
CREATE TABLE IF NOT EXISTS `user_entry_annotations` (
  `annotation_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_entry_id` bigint(20) unsigned NOT NULL,
  `contents` char(255) NOT NULL,
  PRIMARY KEY  (`annotation_id`),
  KEY `user_entry_id` (`user_entry_id`),
  KEY `contents` (`contents`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `user_entry_results`
--

DROP TABLE IF EXISTS `user_entry_results`;
CREATE TABLE IF NOT EXISTS `user_entry_results` (
  `result_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_entry_id` bigint(20) unsigned NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `mode` tinyint(3) unsigned NOT NULL default '1',
  `grade_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`result_id`),
  KEY `timestamp` (`timestamp`),
  KEY `grade_id` (`grade_id`),
  KEY `user_entry_id` (`user_entry_id`),
  KEY `mode` (`mode`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=50 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `user_languages`
--

DROP TABLE IF EXISTS `user_languages`;
CREATE TABLE IF NOT EXISTS `user_languages` (
  `interest_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_id` bigint(20) unsigned NOT NULL,
  `lang_id` bigint(20) unsigned NOT NULL,
  `years` tinyint(3) unsigned default NULL,
  PRIMARY KEY  (`interest_id`),
  UNIQUE KEY `user_id` (`user_id`,`lang_id`),
  KEY `lang_id` (`lang_id`),
  KEY `years` (`years`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `user_practice`
--

DROP TABLE IF EXISTS `user_practice`;
CREATE TABLE IF NOT EXISTS `user_practice` (
  `practice_entry_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_entry_id` bigint(20) unsigned NOT NULL,
  `mode` tinyint(3) unsigned NOT NULL default '1',
  `interval` int(11) NOT NULL default '0',
  `efactor` decimal(3,2) NOT NULL default '2.50',
  PRIMARY KEY  (`practice_entry_id`),
  UNIQUE KEY `user_entry_id` (`user_entry_id`,`mode`),
  KEY `mode` (`mode`),
  KEY `interval` (`interval`),
  KEY `efactor` (`efactor`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=834 ;

-- --------------------------------------------------------

--
-- Struktur för tabell `user_statuses`
--

DROP TABLE IF EXISTS `user_statuses`;
CREATE TABLE IF NOT EXISTS `user_statuses` (
  `status_id` bigint(20) unsigned NOT NULL auto_increment,
  `desc` char(255) NOT NULL,
  PRIMARY KEY  (`status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Restriktioner för dumpade tabeller
--

--
-- Restriktioner för tabell `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`lang_id_0`) REFERENCES `languages` (`lang_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`lang_id_1`) REFERENCES `languages` (`lang_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `courses_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_instructors`
--
ALTER TABLE `course_instructors`
  ADD CONSTRAINT `course_instructors_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_instructors_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_researchers`
--
ALTER TABLE `course_researchers`
  ADD CONSTRAINT `course_researchers_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_researchers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_students`
--
ALTER TABLE `course_students`
  ADD CONSTRAINT `course_students_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_students_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_units`
--
ALTER TABLE `course_units`
  ADD CONSTRAINT `course_units_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_unit_lists`
--
ALTER TABLE `course_unit_lists`
  ADD CONSTRAINT `course_unit_lists_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`unit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_lists_ibfk_3` FOREIGN KEY (`list_id`) REFERENCES `lists` (`list_id`) ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_unit_tests`
--
ALTER TABLE `course_unit_tests`
  ADD CONSTRAINT `course_unit_tests_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`unit_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_unit_test_entries`
--
ALTER TABLE `course_unit_test_entries`
  ADD CONSTRAINT `course_unit_test_entries_ibfk_6` FOREIGN KEY (`mode`) REFERENCES `modes` (`mode_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_test_entries_ibfk_4` FOREIGN KEY (`user_entry_id`) REFERENCES `user_entries` (`user_entry_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_test_entries_ibfk_5` FOREIGN KEY (`test_id`) REFERENCES `course_unit_tests` (`test_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_unit_test_entry_patterns`
--
ALTER TABLE `course_unit_test_entry_patterns`
  ADD CONSTRAINT `course_unit_test_entry_patterns_ibfk_2` FOREIGN KEY (`mode`) REFERENCES `modes` (`mode_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_test_entry_patterns_ibfk_1` FOREIGN KEY (`test_entry_id`) REFERENCES `course_unit_test_entries` (`test_entry_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_unit_test_sittings`
--
ALTER TABLE `course_unit_test_sittings`
  ADD CONSTRAINT `course_unit_test_sittings_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `course_unit_tests` (`test_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_test_sittings_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `course_students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `course_unit_test_sitting_responses`
--
ALTER TABLE `course_unit_test_sitting_responses`
  ADD CONSTRAINT `course_unit_test_sitting_responses_ibfk_1` FOREIGN KEY (`sitting_id`) REFERENCES `course_unit_test_sittings` (`sitting_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_test_sitting_responses_ibfk_3` FOREIGN KEY (`pattern_id`) REFERENCES `course_unit_test_entry_patterns` (`pattern_id`) ON UPDATE CASCADE;

--
-- Restriktioner för tabell `dictionary_queries`
--
ALTER TABLE `dictionary_queries`
  ADD CONSTRAINT `dictionary_queries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `dictionary_query_languages`
--
ALTER TABLE `dictionary_query_languages`
  ADD CONSTRAINT `dictionary_query_languages_ibfk_2` FOREIGN KEY (`lang_code`) REFERENCES `languages` (`lang_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dictionary_query_languages_ibfk_1` FOREIGN KEY (`query_id`) REFERENCES `dictionary_queries` (`query_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `language_names`
--
ALTER TABLE `language_names`
  ADD CONSTRAINT `language_names_ibfk_1` FOREIGN KEY (`lang_id`) REFERENCES `languages` (`lang_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `language_names_ibfk_2` FOREIGN KEY (`lang_id_name`) REFERENCES `language_names` (`lang_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `lists`
--
ALTER TABLE `lists`
  ADD CONSTRAINT `lists_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `list_entries`
--
ALTER TABLE `list_entries`
  ADD CONSTRAINT `list_entries_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `lists` (`list_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `list_entries_ibfk_2` FOREIGN KEY (`user_entry_id`) REFERENCES `user_entries` (`user_entry_id`) ON UPDATE CASCADE;

--
-- Restriktioner för tabell `outbox`
--
ALTER TABLE `outbox`
  ADD CONSTRAINT `outbox_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `outbox_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `user_statuses` (`status_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restriktioner för tabell `user_entries`
--
ALTER TABLE `user_entries`
  ADD CONSTRAINT `user_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `user_entry_annotations`
--
ALTER TABLE `user_entry_annotations`
  ADD CONSTRAINT `user_entry_annotations_ibfk_1` FOREIGN KEY (`user_entry_id`) REFERENCES `user_entries` (`user_entry_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `user_entry_results`
--
ALTER TABLE `user_entry_results`
  ADD CONSTRAINT `user_entry_results_ibfk_2` FOREIGN KEY (`mode`) REFERENCES `modes` (`mode_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `grade_id` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`grade_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_entry_results_ibfk_1` FOREIGN KEY (`user_entry_id`) REFERENCES `user_entries` (`user_entry_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restriktioner för tabell `user_languages`
--
ALTER TABLE `user_languages`
  ADD CONSTRAINT `user_languages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_languages_ibfk_2` FOREIGN KEY (`lang_id`) REFERENCES `languages` (`lang_id`) ON UPDATE CASCADE;

--
-- Restriktioner för tabell `user_practice`
--
ALTER TABLE `user_practice`
  ADD CONSTRAINT `user_practice_ibfk_2` FOREIGN KEY (`mode`) REFERENCES `modes` (`mode_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_practice_ibfk_1` FOREIGN KEY (`user_entry_id`) REFERENCES `user_entries` (`user_entry_id`) ON DELETE CASCADE ON UPDATE CASCADE;
