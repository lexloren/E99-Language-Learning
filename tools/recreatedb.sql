-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- Host: 68.178.216.146
-- Generation Time: Mar 22, 2014 at 01:57 PM
-- Server version: 5.0.96
-- PHP Version: 5.1.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `cscie99`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `course_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_id` bigint(20) unsigned NOT NULL,
  `course_name` char(255) default NULL,
  `lang_id_0` bigint(20) unsigned NOT NULL,
  `lang_id_1` bigint(20) unsigned NOT NULL,
  `public` tinyint(1) NOT NULL default '0',
  `open` datetime default NULL,
  `close` datetime default NULL,
  PRIMARY KEY  (`course_id`),
  KEY `course_name` (`course_name`),
  KEY `lang_id_0` (`lang_id_0`),
  KEY `lang_id_1` (`lang_id_1`),
  KEY `share_public` (`public`),
  KEY `open` (`open`),
  KEY `close` (`close`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_instructors`
--

DROP TABLE IF EXISTS `course_instructors`;
CREATE TABLE `course_instructors` (
  `instructor_id` bigint(20) unsigned NOT NULL auto_increment,
  `course_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`instructor_id`),
  UNIQUE KEY `course_id` (`course_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_students`
--

DROP TABLE IF EXISTS `course_students`;
CREATE TABLE `course_students` (
  `student_id` bigint(20) unsigned NOT NULL auto_increment,
  `course_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`student_id`),
  UNIQUE KEY `course_id` (`course_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_units`
--

DROP TABLE IF EXISTS `course_units`;
CREATE TABLE `course_units` (
  `unit_id` bigint(20) unsigned NOT NULL auto_increment,
  `course_id` bigint(20) unsigned NOT NULL,
  `unit_nmbr` smallint(5) unsigned NOT NULL,
  `unit_name` char(255) default NULL,
  `open` datetime default NULL,
  `close` datetime default NULL,
  PRIMARY KEY  (`unit_id`),
  UNIQUE KEY `course_id` (`course_id`,`unit_nmbr`),
  KEY `unit_name` (`unit_name`),
  KEY `unit_nmbr` (`unit_nmbr`),
  KEY `open` (`open`),
  KEY `close` (`close`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_unit_lists`
--

DROP TABLE IF EXISTS `course_unit_lists`;
CREATE TABLE `course_unit_lists` (
  `unit_id` bigint(20) unsigned NOT NULL,
  `list_id` bigint(20) unsigned NOT NULL,
  `shared` tinyint(1) NOT NULL,
  PRIMARY KEY  (`unit_id`,`list_id`),
  KEY `list_id` (`list_id`),
  KEY `share_class` (`shared`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `course_unit_tests`
--

DROP TABLE IF EXISTS `course_unit_tests`;
CREATE TABLE `course_unit_tests` (
  `test_id` bigint(20) unsigned NOT NULL auto_increment,
  `unit_id` bigint(20) unsigned NOT NULL,
  `test_name` char(255) default NULL,
  `open` datetime default NULL,
  `close` datetime default NULL,
  PRIMARY KEY  (`test_id`),
  KEY `unit_id` (`unit_id`),
  KEY `test_name` (`test_name`),
  KEY `open` (`open`),
  KEY `close` (`close`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_unit_test_entries`
--

DROP TABLE IF EXISTS `course_unit_test_entries`;
CREATE TABLE `course_unit_test_entries` (
  `test_entry_id` bigint(20) unsigned NOT NULL auto_increment,
  `test_id` bigint(20) unsigned NOT NULL,
  `entry_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`test_entry_id`),
  KEY `test_id` (`test_id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_unit_test_entry_results`
--

DROP TABLE IF EXISTS `course_unit_test_entry_results`;
CREATE TABLE `course_unit_test_entry_results` (
  `test_result_id` bigint(20) unsigned NOT NULL auto_increment,
  `test_entry_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`test_result_id`),
  KEY `test_entry_id` (`test_entry_id`),
  KEY `timestamp` (`timestamp`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `dictionary`
--

DROP TABLE IF EXISTS `dictionary`;
CREATE TABLE `dictionary` (
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
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE `grades` (
  `grade_id` bigint(20) unsigned NOT NULL auto_increment,
  `point` int(11) NOT NULL,
  `desc_short` char(255) default NULL,
  `desc_long` char(255) default NULL,
  PRIMARY KEY  (`grade_id`),
  UNIQUE KEY `grade_point` (`point`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
CREATE TABLE `languages` (
  `lang_id` bigint(20) unsigned NOT NULL auto_increment,
  `lang_code` char(2) NOT NULL,
  PRIMARY KEY  (`lang_id`),
  UNIQUE KEY `lang_code` (`lang_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `language_names`
--

DROP TABLE IF EXISTS `language_names`;
CREATE TABLE `language_names` (
  `lang_id` bigint(20) unsigned NOT NULL,
  `lang_id_name` bigint(20) unsigned NOT NULL,
  `lang_name` char(63) NOT NULL,
  PRIMARY KEY  (`lang_id`,`lang_id_name`),
  KEY `lang_id` (`lang_id`),
  KEY `lang_id_name` (`lang_id_name`),
  KEY `lang_name` (`lang_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `lists`
--

DROP TABLE IF EXISTS `lists`;
CREATE TABLE `lists` (
  `list_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_id` bigint(20) unsigned NOT NULL,
  `list_name` char(255) default NULL,
  `public` tinyint(1) NOT NULL,
  PRIMARY KEY  (`list_id`),
  KEY `user_id` (`user_id`),
  KEY `list_name` (`list_name`),
  KEY `share_public` (`public`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Table structure for table `list_entries`
--

DROP TABLE IF EXISTS `list_entries`;
CREATE TABLE `list_entries` (
  `list_entry_id` bigint(20) unsigned NOT NULL auto_increment,
  `list_id` bigint(20) unsigned NOT NULL,
  `entry_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`list_entry_id`),
  UNIQUE KEY `list_id` (`list_id`,`entry_id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` bigint(20) unsigned NOT NULL auto_increment,
  `handle` char(63) NOT NULL,
  `pswd_hash` char(127) NOT NULL,
  `email` char(63) NOT NULL,
  `name_given` char(255) default NULL,
  `name_family` char(255) default NULL,
  `login_token` char(255) default NULL,
  `session` char(63) default NULL,
  `timestamp` timestamp NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `handle` (`handle`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `session` (`session`),
  UNIQUE KEY `login_token` (`login_token`),
  KEY `last_activity` (`timestamp`),
  KEY `pswd_hash` (`pswd_hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_entries`
--

DROP TABLE IF EXISTS `user_entries`;
CREATE TABLE `user_entries` (
  `user_id` bigint(20) unsigned NOT NULL,
  `entry_id` bigint(20) unsigned NOT NULL,
  `word_0` char(255) default NULL,
  `word_1` char(255) default NULL,
  `word_1_pronun` char(255) default NULL,
  `interval` int(11) NOT NULL default '0',
  `efactor` decimal(3,2) NOT NULL default '2.50',
  PRIMARY KEY  (`entry_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `word_0` (`word_0`),
  KEY `word_1` (`word_1`),
  KEY `word_1_pronun` (`word_1_pronun`),
  KEY `interval` (`interval`),
  KEY `efactor` (`efactor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_entry_annotations`
--

DROP TABLE IF EXISTS `user_entry_annotations`;
CREATE TABLE `user_entry_annotations` (
  `annotation_id` bigint(20) unsigned NOT NULL auto_increment,
  `entry_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `contents` char(255) NOT NULL,
  PRIMARY KEY  (`annotation_id`),
  KEY `entry_id` (`entry_id`),
  KEY `user_id` (`user_id`),
  KEY `annotation_text` (`contents`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_entry_results`
--

DROP TABLE IF EXISTS `user_entry_results`;
CREATE TABLE `user_entry_results` (
  `result_id` bigint(20) unsigned NOT NULL auto_increment,
  `entry_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `grade_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`result_id`),
  KEY `timestamp` (`timestamp`),
  KEY `user_id` (`user_id`),
  KEY `entry_id` (`entry_id`),
  KEY `grade_id` (`grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_languages`
--

DROP TABLE IF EXISTS `user_languages`;
CREATE TABLE `user_languages` (
  `interest_id` bigint(20) unsigned NOT NULL auto_increment,
  `user_id` bigint(20) unsigned NOT NULL,
  `lang_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`interest_id`),
  UNIQUE KEY `user_id` (`user_id`,`lang_id`),
  KEY `lang_id` (`lang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`lang_id_0`) REFERENCES `languages` (`lang_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`lang_id_1`) REFERENCES `languages` (`lang_id`) ON UPDATE CASCADE;

--
-- Constraints for table `course_instructors`
--
ALTER TABLE `course_instructors`
  ADD CONSTRAINT `course_instructors_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_instructors_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `course_students`
--
ALTER TABLE `course_students`
  ADD CONSTRAINT `course_students_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_students_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_units`
--
ALTER TABLE `course_units`
  ADD CONSTRAINT `course_units_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_unit_lists`
--
ALTER TABLE `course_unit_lists`
  ADD CONSTRAINT `course_unit_lists_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `lists` (`list_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_lists_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`unit_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_unit_tests`
--
ALTER TABLE `course_unit_tests`
  ADD CONSTRAINT `course_unit_tests_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`unit_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_unit_test_entries`
--
ALTER TABLE `course_unit_test_entries`
  ADD CONSTRAINT `course_unit_test_entries_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `course_unit_tests` (`test_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_test_entries_ibfk_2` FOREIGN KEY (`entry_id`) REFERENCES `user_entries` (`entry_id`) ON UPDATE CASCADE;

--
-- Constraints for table `course_unit_test_entry_results`
--
ALTER TABLE `course_unit_test_entry_results`
  ADD CONSTRAINT `course_unit_test_entry_results_ibfk_1` FOREIGN KEY (`test_entry_id`) REFERENCES `course_unit_test_entries` (`test_entry_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_test_entry_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `course_students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lists`
--
ALTER TABLE `lists`
  ADD CONSTRAINT `lists_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `list_entries`
--
ALTER TABLE `list_entries`
  ADD CONSTRAINT `list_entries_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `lists` (`list_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `list_entries_ibfk_2` FOREIGN KEY (`entry_id`) REFERENCES `user_entries` (`entry_id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_entry_annotations`
--
ALTER TABLE `user_entry_annotations`
  ADD CONSTRAINT `user_entry_annotations_ibfk_3` FOREIGN KEY (`entry_id`) REFERENCES `user_entries` (`entry_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_entry_annotations_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `user_entries` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_entry_results`
--
ALTER TABLE `user_entry_results`
  ADD CONSTRAINT `grade_id` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`grade_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_entry_results_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_entry_results_ibfk_3` FOREIGN KEY (`entry_id`) REFERENCES `user_entries` (`entry_id`) ON UPDATE CASCADE;
