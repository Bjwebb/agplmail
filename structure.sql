-- SQL Table structure for AGPLMail

-- --------------------------------------------------------

-- 
-- Table structure for table `agplmail_mess`
-- 

CREATE TABLE `agplmail_mess` (
  `msgno` mediumint(9) NOT NULL,
  `archived` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `tags` mediumtext NOT NULL,
  `account` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `agplmail_settings`
-- 

CREATE TABLE `agplmail_settings` (
  `account` text NOT NULL,
  `name` text NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

