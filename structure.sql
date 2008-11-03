-- Copyright (C) 2008 Ben Webb <dreamer@freedomdreams.co.uk>
-- This file is part of AGPLMail.
-- 
-- AGPLMail is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as
-- published by the Free Software Foundation, either version 3 of the
-- License, or (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.
--
-- You should have received a copy of the GNU Affero General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

-- --------------------------------------------------------

-- SQL Table structure for AGPLMail

-- --------------------------------------------------------

-- 
-- Table structure for table `agplmail_mess`
-- 

CREATE TABLE `agplmail_mess` (
  `messid` text NOT NULL,
  `archived` tinyint(1) NOT NULL,
  `tags` mediumtext NOT NULL,
  `account` text NOT NULL,
  `deleted` tinyint(1) NOT NULL
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

