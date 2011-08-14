--
-- i-MSCP - internet Multi Server Control Panel
--
-- Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
-- Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
-- Copyright (C) 2010-2011 by internet Multi Server Control Panel - http://i-mscp.net
--
-- Version: $Id$
--
-- The contents of this file are subject to the Mozilla Public License
-- Version 1.1 (the "License"); you may not use this file except in
-- compliance with the License. You may obtain a copy of the License at
-- http://www.mozilla.org/MPL/
--
-- Software distributed under the License is distributed on an "AS IS"
-- basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
-- License for the specific language governing rights and limitations
-- under the License.
--
-- The Original Code is "VHCS - Virtual Hosting Control System".
--
-- The Initial Developer of the Original Code is moleSoftware GmbH.
-- Portions created by Initial Developer are Copyright (C) 2001-2006
-- by moleSoftware GmbH. All Rights Reserved.
--
-- Portions created by the ispCP Team are Copyright (C) 2006-2010 by
-- isp Control Panel. All Rights Reserved.
--
-- Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
-- internet Multi Server Control Panel. All Rights Reserved.
--
-- The i-MSCP Home Page is:
--
--    http://i-mscp.net
--
-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `admin_pass` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `admin_type` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `admin_created` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_id` varchar(200) COLLATE utf8_unicode_ci DEFAULT '0',
  `created_by` int(10) unsigned DEFAULT '0',
  `fname` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lname` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firm` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street1` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street2` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `uniqkey` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `uniqkey_time` timestamp NULL DEFAULT NULL,
  `admin_status` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'toadd',
  UNIQUE KEY `admin_id` (`admin_id`),
  UNIQUE KEY `admin_name` (`admin_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- --------------------------------------------------------

--
-- Table structure for table `autoreplies_log`
--

CREATE TABLE IF NOT EXISTS `autoreplies_log` (
  `time` datetime NOT NULL COMMENT 'Date and time of the sent autoreply',
  `from` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'autoreply message sender',
  `to` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'autoreply message recipient',
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Sent autoreplies log table';

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `config`
--

INSERT IGNORE INTO `config` (`name`, `value`) VALUES
('PORT_IMSCP_DAEMON', '9876;tcp;i-MSCP-Daemon;1;0;127.0.0.1'),
('PORT_FTP', '21;tcp;FTP;1;0;'),
('PORT_SSH', '22;tcp;SSH;1;0;'),
('PORT_TELNET', '23;tcp;TELNET;1;0;'),
('PORT_SMTP', '25;tcp;SMTP;1;0;'),
('PORT_SMTP-SSL', '465;tcp;SMTP-SSL;0;0;'),
('PORT_DNS', '53;tcp;DNS;1;0;'),
('PORT_HTTP', '80;tcp;HTTP;1;0;'),
('PORT_HTTPS', '443;tcp;HTTPS;0;0;'),
('PORT_POP3', '110;tcp;POP3;1;0;'),
('PORT_POP3-SSL', '995;tcp;POP3-SSL;0;0;'),
('PORT_IMAP', '143;tcp;IMAP;1;0;'),
('PORT_IMAP-SSL', '993;tcp;IMAP-SSL;0;0;'),
('PORT_POSTGREY', '10023;tcp;POSTGREY;1;1;localhost'),
('PORT_AMAVIS', '10024;tcp;AMaVis;0;1;localhost'),
('PORT_SPAMASSASSIN', '783;tcp;SPAMASSASSIN;0;1;localhost'),
('PORT_POLICYD-WEIGHT', '12525;tcp;POLICYD-WEIGHT;1;1;localhost'),
('SHOW_COMPRESSION_SIZE', '1'),
('PREVENT_EXTERNAL_LOGIN_ADMIN', '1'),
('PREVENT_EXTERNAL_LOGIN_RESELLER', '1'),
('PREVENT_EXTERNAL_LOGIN_CLIENT', '1'),
('DATABASE_REVISION', '75');

-- --------------------------------------------------------

--
-- Table structure for table `custom_menus`
--

CREATE TABLE IF NOT EXISTS `custom_menus` (
  `menu_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_level` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `menu_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `menu_link` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `menu_target` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain`
--

CREATE TABLE IF NOT EXISTS `domain` (
  `domain_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_name` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `domain_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_created_id` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_created` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_expires` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_last_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `domain_ip_id` int(10) unsigned DEFAULT NULL,
  `domain_mount_point` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '/',
  `url_forward` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  UNIQUE KEY `domain_id` (`domain_id`),
  UNIQUE KEY `domain_name` (`domain_name`),
  KEY `i_domain_admin_id` (`domain_admin_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_aliasses`
--

CREATE TABLE IF NOT EXISTS `domain_aliasses` (
  `alias_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned DEFAULT NULL,
  `alias_name` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alias_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`alias_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_dns`
--

CREATE TABLE IF NOT EXISTS `domain_dns` (
  `domain_dns_id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL,
  `domain_dns` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `domain_class` enum('IN','CH','HS') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'IN',
  `domain_type` enum('A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX','NAPTR','NSAP','NS','NXT','PTR','PX','SIG','SRV','TXT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A',
  `domain_text` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `protected` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY (`domain_dns_id`),
  UNIQUE KEY `domain_id` (`domain_id`,`domain_dns`,`domain_class`,`domain_type`,`domain_text`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_traffic`
--

CREATE TABLE IF NOT EXISTS `domain_traffic` (
  `dtraff_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned DEFAULT NULL,
  `dtraff_time` bigint(20) unsigned DEFAULT NULL,
  `dtraff_web` bigint(20) unsigned DEFAULT NULL,
  `dtraff_ftp` bigint(20) unsigned DEFAULT NULL,
  `dtraff_mail` bigint(20) unsigned DEFAULT NULL,
  `dtraff_pop` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`dtraff_id`),
  KEY `i_domain_id` (`domain_id`),
  KEY `i_dtraff_time` (`dtraff_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_tpls`
--

CREATE TABLE IF NOT EXISTS `email_tpls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8_unicode_ci,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_pages`
--

CREATE TABLE IF NOT EXISTS `error_pages` (
  `ep_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `error_401` text COLLATE utf8_unicode_ci NOT NULL,
  `error_403` text COLLATE utf8_unicode_ci NOT NULL,
  `error_404` text COLLATE utf8_unicode_ci NOT NULL,
  `error_500` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_group`
--

CREATE TABLE IF NOT EXISTS `ftp_group` (
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `groupname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gid` int(10) unsigned NOT NULL DEFAULT '0',
  `members` text COLLATE utf8_unicode_ci,
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_users`
--

CREATE TABLE IF NOT EXISTS `ftp_users` (
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `userid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `passwd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rawpasswd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `gid` int(10) unsigned NOT NULL DEFAULT '0',
  `shell` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `homedir` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hosting_plans`
--

CREATE TABLE IF NOT EXISTS `hosting_plans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reseller_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `props` text COLLATE utf8_unicode_ci,
  `description` text COLLATE utf8_unicode_ci,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `setup_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payment` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT '0',
  `tos` blob NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess`
--

CREATE TABLE IF NOT EXISTS `htaccess` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dmn_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `group_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auth_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auth_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_groups`
--

CREATE TABLE IF NOT EXISTS `htaccess_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dmn_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ugroup` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `members` text COLLATE utf8_unicode_ci,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_users`
--

CREATE TABLE IF NOT EXISTS `htaccess_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dmn_id` int(10) unsigned NOT NULL DEFAULT '0',
  `uname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `upass` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `log_message` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE IF NOT EXISTS `login` (
  `session_id` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ipaddr` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastaccess` int(10) unsigned DEFAULT NULL,
  `login_count` tinyint(1) DEFAULT '0',
  `captcha_count` tinyint(1) DEFAULT '0',
  `user_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mail_users`
--

CREATE TABLE IF NOT EXISTS `mail_users` (
  `mail_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mail_acc` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mail_pass` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mail_forward` text COLLATE utf8_unicode_ci,
  `domain_id` int(10) unsigned DEFAULT NULL,
  `mail_type` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sub_id` int(10) unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mail_auto_respond` tinyint(1) NOT NULL DEFAULT '0',
  `mail_auto_respond_text` text COLLATE utf8_unicode_ci,
  `quota` int(10) DEFAULT '104857600',
  `mail_addr` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`mail_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `plan_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_name` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `customer_id` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fname` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lname` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firm` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street1` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street2` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders_settings`
--

CREATE TABLE IF NOT EXISTS `orders_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `header` text COLLATE utf8_unicode_ci,
  `footer` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotalimits`
--

CREATE TABLE IF NOT EXISTS `quotalimits` (
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `quota_type` enum('user','group','class','all') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `per_session` enum('false','true') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'false',
  `limit_type` enum('soft','hard') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'soft',
  `bytes_in_avail` float NOT NULL DEFAULT '0',
  `bytes_out_avail` float NOT NULL DEFAULT '0',
  `bytes_xfer_avail` float NOT NULL DEFAULT '0',
  `files_in_avail` int(10) unsigned NOT NULL DEFAULT '0',
  `files_out_avail` int(10) unsigned NOT NULL DEFAULT '0',
  `files_xfer_avail` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotatallies`
--

CREATE TABLE IF NOT EXISTS `quotatallies` (
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `quota_type` enum('user','group','class','all') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `bytes_in_used` float NOT NULL DEFAULT '0',
  `bytes_out_used` float NOT NULL DEFAULT '0',
  `bytes_xfer_used` float NOT NULL DEFAULT '0',
  `files_in_used` int(10) unsigned NOT NULL DEFAULT '0',
  `files_out_used` int(10) unsigned NOT NULL DEFAULT '0',
  `files_xfer_used` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quota_dovecot`
--

CREATE TABLE IF NOT EXISTS `quota_dovecot` (
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `bytes` bigint(20) NOT NULL DEFAULT '0',
  `messages` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reseller_props`
--

CREATE TABLE IF NOT EXISTS `reseller_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `reseller_id` int(10) unsigned NOT NULL default '0',
  `current_dmn_cnt` int(11) default NULL,
  `max_dmn_cnt` int(11) default NULL,
  `current_sub_cnt` int(11) default NULL,
  `max_sub_cnt` int(11) default NULL,
  `current_als_cnt` int(11) default NULL,
  `max_als_cnt` int(11) default NULL,
  `current_mail_cnt` int(11) default NULL,
  `max_mail_cnt` int(11) default NULL,
  `current_ftp_cnt` int(11) default NULL,
  `max_ftp_cnt` int(11) default NULL,
  `current_sql_db_cnt` int(11) default NULL,
  `max_sql_db_cnt` int(11) default NULL,
  `current_sql_user_cnt` int(11) default NULL,
  `max_sql_user_cnt` int(11) default NULL,
  `current_disk_amnt` int(11) default NULL,
  `max_disk_amnt` int(11) default NULL,
  `current_traff_amnt` int(11) default NULL,
  `max_traff_amnt` int(11) default NULL,
  `support_system` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'yes',
  `customer_id` varchar(200) collate utf8_unicode_ci default NULL,
  `reseller_ips` text collate utf8_unicode_ci,
  `software_allowed` varchar(15) collate utf8_general_ci NOT NULL default 'no',
  `softwaredepot_allowed` varchar(15) collate utf8_general_ci NOT NULL default 'yes',
  `websoftwaredepot_allowed` varchar(15) collate utf8_general_ci NOT NULL default 'yes',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_ips`
--

CREATE TABLE IF NOT EXISTS `server_ips` (
  `ip_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_number` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip_domain` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip_alias` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip_card` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip_ssl_domain_id` int(10) DEFAULT NULL,
  `ip_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY `ip_id` (`ip_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_traffic`
--

CREATE TABLE IF NOT EXISTS `server_traffic` (
  `straff_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `traff_time` int(10) unsigned DEFAULT NULL,
  `bytes_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_out` bigint(20) unsigned DEFAULT NULL,
  `bytes_mail_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_mail_out` bigint(20) unsigned DEFAULT NULL,
  `bytes_pop_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_pop_out` bigint(20) unsigned DEFAULT NULL,
  `bytes_web_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_web_out` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`straff_id`),
  KEY `traff_time` (`traff_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_database`
--

CREATE TABLE IF NOT EXISTS `sql_database` (
  `sqld_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned DEFAULT '0',
  `sqld_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT 'n/a',
  UNIQUE KEY `sqld_id` (`sqld_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_user`
--

CREATE TABLE IF NOT EXISTS `sql_user` (
  `sqlu_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sqld_id` int(10) unsigned DEFAULT '0',
  `sqlu_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT 'n/a',
  `sqlu_pass` varchar(64) COLLATE utf8_unicode_ci DEFAULT 'n/a',
  UNIQUE KEY `sqlu_id` (`sqlu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `straff_settings`
--

CREATE TABLE IF NOT EXISTS `straff_settings` (
  `straff_max` int(10) unsigned DEFAULT NULL,
  `straff_warn` int(10) unsigned DEFAULT NULL,
  `straff_email` int(10) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `straff_settings`
--

INSERT IGNORE INTO `straff_settings` (`straff_max`, `straff_warn`, `straff_email`) VALUES (0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `subdomain`
--

CREATE TABLE IF NOT EXISTS `subdomain` (
  `subdomain_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned DEFAULT NULL,
  `subdomain_name` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subdomain_mount` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subdomain_url_forward` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subdomain_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`subdomain_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE IF NOT EXISTS `tickets` (
  `ticket_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_level` int(10) DEFAULT NULL,
  `ticket_from` int(10) unsigned DEFAULT NULL,
  `ticket_to` int(10) unsigned DEFAULT NULL,
  `ticket_status` int(10) unsigned DEFAULT NULL,
  `ticket_reply` int(10) unsigned DEFAULT NULL,
  `ticket_urgency` int(10) unsigned DEFAULT NULL,
  `ticket_date` int(10) unsigned DEFAULT NULL,
  `ticket_subject` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ticket_message` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_gui_props`
--

CREATE TABLE IF NOT EXISTS `user_gui_props` (
  `user_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) COLLATE utf8_unicode_ci DEFAULT '',
  `layout` varchar(100) COLLATE utf8_unicode_ci DEFAULT '',
  `logo` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_gui_props`
--

CREATE TABLE IF NOT EXISTS `user_system_props` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_mailacc_limit` int(11) NOT NULL DEFAULT '-1',
  `user_ftpacc_limit` int(11) NOT NULL DEFAULT '-1',
  `user_traffic_limit` bigint(20) NOT NULL DEFAULT '-1',
  `user_sqld_limit` int(11) NOT NULL DEFAULT '-1',
  `user_sqlu_limit` int(11) NOT NULL DEFAULT '-1',
  `user_domain_limit` int(11) NOT NULL DEFAULT '-1',
  `user_alias_limit` int(11) NOT NULL DEFAULT '-1',
  `user_subd_limit` int(11) NOT NULL DEFAULT '-1',
  `user_ip_ids` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_disk_limit` bigint(20) NOT NULL DEFAULT '-1',
  `user_disk_usage` bigint(20) unsigned DEFAULT '0',
  `user_ssh` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `user_ssl` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `user_php` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `user_cgi` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `user_backups` enum('full','sql','domain','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `user_dns` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `user_software_allowed` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `web_software`
--

CREATE TABLE IF NOT EXISTS `web_software` (
  `software_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `software_master_id` int(10) unsigned NOT NULL DEFAULT '0',
  `reseller_id` int(10) unsigned NOT NULL DEFAULT '0',
  `software_installtype` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `software_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `software_version` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `software_language` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `software_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `software_db` tinyint(1) NOT NULL,
  `software_archive` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `software_installfile` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `software_prefix` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `software_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `software_desc` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `software_active` int(1) NOT NULL,
  `software_status` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `rights_add_by` int(10) unsigned NOT NULL DEFAULT '0',
  `software_depot` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY (`software_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_depot`
--

CREATE TABLE IF NOT EXISTS `web_software_depot` (
  `package_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `package_install_type` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `package_title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `package_version` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `package_language` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `package_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `package_description` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `package_vendor_hp` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `package_download_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `package_signature_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`package_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_inst`
--

CREATE TABLE IF NOT EXISTS `web_software_inst` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned NOT NULL,
  `subdomain_id` int(10) unsigned NOT NULL DEFAULT '0',
  `software_id` int(10) NOT NULL,
  `software_master_id` int(10) unsigned NOT NULL DEFAULT '0',
  `software_res_del` int(1) NOT NULL DEFAULT '0',
  `software_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `software_version` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `software_language` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `software_prefix` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `db` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `database_user` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `database_tmp_pwd` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `install_username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `install_password` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `install_email` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `software_status` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `software_depot` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY (`id`),
  KEY `software_id` (`software_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_options`
--

CREATE TABLE IF NOT EXISTS `web_software_options` (
  `use_webdepot` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `webdepot_xml_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `webdepot_last_update` datetime NOT NULL,
  UNIQUE KEY `use_webdepot` (`use_webdepot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `web_software_options`
--

INSERT IGNORE INTO `web_software_options` (`use_webdepot`, `webdepot_xml_url`, `webdepot_last_update`) VALUES (1, 'http://app-pkg.i-mscp.net/imscp_webdepot_list.xml', '0000-00-00 00:00:00');
