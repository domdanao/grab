-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 01, 2013 at 07:14 AM
-- Server version: 5.6.12-log
-- PHP Version: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `grab`
--
CREATE DATABASE IF NOT EXISTS `grab` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `grab`;

-- --------------------------------------------------------

--
-- Table structure for table `firstsend`
--

CREATE TABLE IF NOT EXISTS `firstsend` (
  `msisdn` varchar(16) NOT NULL,
  `sentwhen` datetime NOT NULL,
  KEY `msisdn` (`msisdn`),
  KEY `sentwhen` (`sentwhen`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `grab_bag`
--

CREATE TABLE IF NOT EXISTS `grab_bag` (
  `gid` int(9) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(16) NOT NULL,
  `adcopy` varchar(32) NOT NULL,
  `info` varchar(255) NOT NULL,
  `grab_start` datetime NOT NULL,
  `grab_end` datetime NOT NULL,
  `operator` varchar(8) NOT NULL,
  `total_grabs_allowed` int(9) NOT NULL,
  PRIMARY KEY (`gid`),
  KEY `keyword` (`keyword`),
  KEY `grab_start` (`grab_start`),
  KEY `grab_end` (`grab_end`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='The Grab Bag' AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `grab_ipad_1`
--

CREATE TABLE IF NOT EXISTS `grab_ipad_1` (
  `grab_id` bigint(12) NOT NULL AUTO_INCREMENT,
  `msisdn` varchar(16) NOT NULL,
  `grab_time` decimal(16,6) NOT NULL,
  `lost_time` decimal(16,6) NOT NULL,
  PRIMARY KEY (`grab_id`),
  KEY `msisdn` (`msisdn`),
  KEY `hold_time` (`lost_time`),
  KEY `grab_time` (`grab_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Grab table for ipad' AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `http_req_rep`
--

CREATE TABLE IF NOT EXISTS `http_req_rep` (
  `t_id` int(10) NOT NULL AUTO_INCREMENT,
  `mo_id` int(10) NOT NULL,
  `req_type` varchar(8) NOT NULL,
  `host` varchar(255) NOT NULL,
  `port` int(5) NOT NULL,
  `path` varchar(255) NOT NULL,
  `query` text NOT NULL,
  `time_start` decimal(16,6) NOT NULL,
  `http_code` int(3) NOT NULL,
  `time_recd` decimal(16,6) NOT NULL,
  `total_time` decimal(9,6) NOT NULL,
  `body_content` text NOT NULL,
  `trans_type` varchar(8) NOT NULL,
  PRIMARY KEY (`t_id`),
  KEY `time_start` (`time_start`),
  KEY `http_code` (`http_code`),
  KEY `time_recd` (`time_recd`),
  KEY `total_time` (`total_time`),
  KEY `trans_type` (`trans_type`),
  KEY `host` (`host`),
  KEY `port` (`port`),
  KEY `path` (`path`),
  KEY `mo_id` (`mo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=69 ;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE IF NOT EXISTS `members` (
  `m_id` int(10) NOT NULL AUTO_INCREMENT,
  `msisdn` varchar(16) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `age` int(3) NOT NULL,
  `alert` int(1) NOT NULL,
  `joinedwhen` datetime NOT NULL,
  PRIMARY KEY (`m_id`),
  KEY `msisdn` (`msisdn`),
  KEY `alert` (`alert`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Members table' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `msg_in`
--

CREATE TABLE IF NOT EXISTS `msg_in` (
  `mo_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `msisdn` varchar(16) NOT NULL,
  `raw_msg` varchar(160) NOT NULL,
  `recipient` mediumint(12) NOT NULL,
  `operator` varchar(8) NOT NULL,
  `msg_id` varchar(16) NOT NULL,
  `came_from` varchar(8) NOT NULL,
  `time_in` datetime NOT NULL,
  PRIMARY KEY (`mo_id`),
  KEY `msisdn` (`msisdn`),
  KEY `msg_id` (`msg_id`),
  KEY `raw_msg` (`raw_msg`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Inbound message' AUTO_INCREMENT=53 ;

-- --------------------------------------------------------

--
-- Table structure for table `unlisubs`
--

CREATE TABLE IF NOT EXISTS `unlisubs` (
  `m_id` int(10) NOT NULL,
  `grab_bag_table` varchar(32) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  KEY `m_id` (`m_id`,`start_time`,`end_time`),
  KEY `grab_bag_table` (`grab_bag_table`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Unlimited subs';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
