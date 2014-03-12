-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- Host: 68.178.216.146
-- Generation Time: Mar 11, 2014 at 08:19 PM
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
  `course_name` char(255) NOT NULL,
  `lang_id_1` bigint(20) unsigned NOT NULL,
  `lang_id_2` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`course_id`),
  KEY `course_name` (`course_name`),
  KEY `lang_id_1` (`lang_id_1`),
  KEY `lang_id_2` (`lang_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_lists`
--

DROP TABLE IF EXISTS `course_lists`;
CREATE TABLE `course_lists` (
  `course_id` bigint(20) unsigned NOT NULL,
  `list_id` bigint(20) unsigned NOT NULL,
  `share_class` tinyint(1) NOT NULL,
  PRIMARY KEY  (`course_id`,`list_id`),
  KEY `list_id` (`list_id`),
  KEY `share_class` (`share_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  PRIMARY KEY  (`unit_id`),
  UNIQUE KEY `course_id` (`course_id`,`unit_nmbr`),
  KEY `unit_name` (`unit_name`),
  KEY `unit_nmbr` (`unit_nmbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_unit_tests`
--

DROP TABLE IF EXISTS `course_unit_tests`;
CREATE TABLE `course_unit_tests` (
  `test_id` bigint(20) unsigned NOT NULL auto_increment,
  `unit_id` bigint(20) unsigned NOT NULL,
  `test_name` char(255) default NULL,
  `open_date` date default NULL,
  `close_date` date default NULL,
  PRIMARY KEY  (`test_id`),
  KEY `unit_id` (`unit_id`),
  KEY `test_name` (`test_name`),
  KEY `open_date` (`open_date`),
  KEY `close_date` (`close_date`)
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
  `lang_id_known` bigint(20) unsigned NOT NULL,
  `lang_id_unknw` bigint(20) unsigned NOT NULL,
  `lang_known` char(255) NOT NULL,
  `lang_unknw` char(255) NOT NULL,
  `pronunciation` char(255) default NULL,
  `user_id` bigint(20) unsigned default NULL,
  PRIMARY KEY  (`entry_id`),
  KEY `lang_id_known` (`lang_id_known`),
  KEY `lang_id_unknw` (`lang_id_unknw`),
  KEY `user_id` (`user_id`),
  FULLTEXT KEY `lang_unknw` (`lang_unknw`),
  FULLTEXT KEY `lang_known` (`lang_known`),
  FULLTEXT KEY `pronunciation` (`pronunciation`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=847826 ;

-- --------------------------------------------------------

--
-- Table structure for table `entries`
--

DROP TABLE IF EXISTS `entries`;
CREATE TABLE `entries` (
  `entry_id` bigint(20) unsigned NOT NULL auto_increment,
  `lang_id_known` bigint(20) unsigned NOT NULL,
  `lang_id_unknw` bigint(20) unsigned NOT NULL,
  `lang_known` char(255) NOT NULL,
  `lang_unknw` char(255) NOT NULL,
  `pronunciation` char(255) default NULL,
  `user_id` bigint(20) unsigned default NULL,
  PRIMARY KEY  (`entry_id`),
  UNIQUE KEY `translation` (`lang_id_known`,`lang_id_unknw`,`lang_known`,`lang_unknw`,`pronunciation`),
  KEY `lang_unknw` (`lang_unknw`),
  KEY `pronunciation` (`pronunciation`),
  KEY `lang_id_known` (`lang_id_known`),
  KEY `lang_id_unknw` (`lang_id_unknw`),
  KEY `lang_known` (`lang_known`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `entry_annotations`
--

DROP TABLE IF EXISTS `entry_annotations`;
CREATE TABLE `entry_annotations` (
  `annotation_id` bigint(20) unsigned NOT NULL auto_increment,
  `entry_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `annotation_text` char(255) NOT NULL,
  PRIMARY KEY  (`annotation_id`),
  KEY `entry_id` (`entry_id`),
  KEY `user_id` (`user_id`),
  KEY `annotation_text` (`annotation_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `entry_results`
--

DROP TABLE IF EXISTS `entry_results`;
CREATE TABLE `entry_results` (
  `result_id` bigint(20) unsigned NOT NULL auto_increment,
  `entry_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`result_id`),
  KEY `timestamp` (`timestamp`),
  KEY `user_id` (`user_id`),
  KEY `entry_id` (`entry_id`)
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
  `lang_id_known` bigint(20) unsigned default NULL,
  `lang_id_unknw` bigint(20) unsigned default NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `list_name` char(255) default NULL,
  `share_public` tinyint(1) NOT NULL,
  PRIMARY KEY  (`list_id`),
  KEY `user_id` (`user_id`),
  KEY `list_name` (`list_name`),
  KEY `share_public` (`share_public`),
  KEY `lang_id_known` (`lang_id_known`,`lang_id_unknw`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `last_activity` timestamp NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `handle` (`handle`),
  UNIQUE KEY `session` (`session`),
  KEY `login_token` (`login_token`),
  KEY `email` (`email`),
  KEY `last_activity` (`last_activity`),
  KEY `pswd_hash` (`pswd_hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

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
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`lang_id_1`) REFERENCES `languages` (`lang_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`lang_id_2`) REFERENCES `languages` (`lang_id`) ON UPDATE CASCADE;

--
-- Constraints for table `course_instructors`
--
ALTER TABLE `course_instructors`
  ADD CONSTRAINT `course_instructors_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_instructors_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `course_lists`
--
ALTER TABLE `course_lists`
  ADD CONSTRAINT `course_lists_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `lists` (`list_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_lists_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `course_unit_tests`
--
ALTER TABLE `course_unit_tests`
  ADD CONSTRAINT `course_unit_tests_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`unit_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_unit_test_entries`
--
ALTER TABLE `course_unit_test_entries`
  ADD CONSTRAINT `course_unit_test_entries_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `course_unit_tests` (`test_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_test_entries_ibfk_2` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`entry_id`) ON UPDATE CASCADE;

--
-- Constraints for table `course_unit_test_entry_results`
--
ALTER TABLE `course_unit_test_entry_results`
  ADD CONSTRAINT `course_unit_test_entry_results_ibfk_1` FOREIGN KEY (`test_entry_id`) REFERENCES `course_unit_test_entries` (`test_entry_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_unit_test_entry_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `course_students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `entries`
--
ALTER TABLE `entries`
  ADD CONSTRAINT `entries_ibfk_1` FOREIGN KEY (`lang_id_known`) REFERENCES `languages` (`lang_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `entries_ibfk_2` FOREIGN KEY (`lang_id_unknw`) REFERENCES `languages` (`lang_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `entries_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `entry_annotations`
--
ALTER TABLE `entry_annotations`
  ADD CONSTRAINT `entry_annotations_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`entry_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `entry_annotations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `entry_results`
--
ALTER TABLE `entry_results`
  ADD CONSTRAINT `entry_results_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `entry_results_ibfk_3` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`entry_id`) ON UPDATE CASCADE;

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
  ADD CONSTRAINT `list_entries_ibfk_2` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`entry_id`) ON UPDATE CASCADE;
