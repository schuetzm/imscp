<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA02110-1301, USA.
 *
 * @category	iMSCP
 * @package	 iMSCP_Update
 * @subpackageDatabase
 * @copyright 2010-2011 by i-MSCP team
 * @author	Daniel Andreca <sci2tech@gmail.com>
 * @author	Laurent Declercq <l.declercq@nuxwin.com>
 * @version	 SVN: $Id$
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license	 http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/** @see iMSCP_Update */
require_once 'iMSCP/Update.php';

/**
 * Update version class.
 *
 * Checks if an update is available for i-MSCP.
 *
 * @category	iMSCP
 * @package	 iMSCP_Update
 * @subpackageDatabase
 * @author	Daniel Andreca <sci2tech@gmail.com>
 * @author	Laurent Declercq <l.declercq@nuxwin.com>
 * @version	 0.0.1
 */
class iMSCP_Update_Database extends iMSCP_Update
{
	/**
	 * @var iMSCP_Update
	 */
	protected static $_instance;

	/**
	 * Tells whether or not a request must be send to the i-MSCP daemon after that
	 * all database updates were applied.
	 *
	 * @var bool
	 */
	protected $_daemonRequest = false;

	/**
	 * Singleton - Make new unavailable.
	 */
	protected function __construct()
	{

	}

	/**
	 * Singleton - Make clone unavailable.
	 *
	 * @return void
	 */
	protected function __clone()
	{

	}

	/**
	 * Implements Singleton design pattern.
	 *
	 * @return iMSCP_Update
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Checks for available database update.
	 *
	 * @return bool TRUE if an update is available, FALSE otherwise
	 */
	public function isAvailableUpdate()
	{
		if ($this->getLastAppliedUpdate() < $this->getNextUpdate()) {
			return true;
		}

		return false;
	}

	/**
	 * Apply all available database updates.
	 *
	 * @return bool TRUE on success, FALSE othewise
	 */
	public function applyUpdates()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		/** @var $pdo PDO */
		$pdo = iMSCP_Database::getRawInstance();

		while ($this->isAvailableUpdate()) {
			$databaseUpdateRevision = $this->getNextUpdate();

			// Get the database update method name
			$databaseUpdateMethod = '_databaseUpdate_' . $databaseUpdateRevision;

			// Gets the querie(s) from the databse update method
			// A database update can return void, an array or a string
			$queryStack = $this->$databaseUpdateMethod();

			if (!empty($queryStack)) {
				// Checks if the current database update was already executed with a
				// failed status
				if (isset($dbConfig->FAILED_UPDATE)) {
					list($failedUpdate, $failedQueryIndex) = $dbConfig->FAILED_UPDATE;
				} else {
					$failedUpdate = '';
					$failedQueryIndex = -1;
				}

				// Execute all queries from the queries stack returned by the database
				// update method
				foreach ((array)$queryStack as $index => $query)
				{
					// Query was already applied with success ?
					if ($databaseUpdateMethod == $failedUpdate &&
						$index < $failedQueryIndex
					) {
						continue;
					}

					try {
						// Execute query
						$pdo->query($query);
					} catch (PDOException $e) {
						// Store the query index that failed and the database update
						// method that wrap it
						$dbConfig->FAILED_UPDATE = "$databaseUpdateMethod;$index";

						// Prepare error message
						$errorMessage = tr(
							'Database update %s failed', $databaseUpdateRevision
						);

						// Extended error message
						if (PHP_SAPI != 'cli') {
							$errorMessage .= ':<br /><br />' . $e->getMessage() .
											 '<br /><br />Query: ' . trim($query);
						} else {
							$errorMessage .= ":\n\n" . $e->getMessage() .
											 "\nQuery: " . trim($query);
						}

						$this->_lastError = $errorMessage;

						return false;
					}
				}
			}

			// Database update was successfully applied - updating revision number
			// in the database and do some cleanup if needed
			$dbConfig->set('DATABASE_REVISION', $databaseUpdateRevision);
			if ($dbConfig->exists('FAILED_UPDATE')) {
				$dbConfig->del('FAILED_UPDATE');
			}
		}

		// We should never run the backend scripts from the CLI update script
		if (PHP_SAPI != 'cli' && $this->_daemonRequest) {
			send_request();
		}

		return true;
	}

	/**
	 * Return next database update revision.
	 *
	 * @return int 0 if no update available
	 */
	protected function getNextUpdate()
	{
		$lastAvailableUpdateRevision = $this->getLastAvailableUpdateRevision();
		$nextUpdateRevision = $this->getLastAppliedUpdate();

		if ($nextUpdateRevision < $lastAvailableUpdateRevision) {
			return $nextUpdateRevision + 1;
		}

		return 0;
	}

	/**
	 * Returns the revision of the last available datababse update.
	 *
	 * Note: For performances reasons, the revision is retrieved once.
	 *
	 * @return int Therevision of the last available database update
	 */
	protected function getLastAvailableUpdateRevision()
	{
		static $lastAvailableUpdateRevision = null;

		if (null === $lastAvailableUpdateRevision) {
			$reflection = new ReflectionClass(__CLASS__);
			$databaseUpdateMethods = array();

			foreach ($reflection->getMethods() as $method)
			{
				if (strpos($method->name, '_databaseUpdate_') !== false) {
					$databaseUpdateMethods[] = $method->name;
				}
			}

			$databaseUpdateMethod = (string)end($databaseUpdateMethods);
			$lastAvailableUpdateRevision = (int)substr(
				$databaseUpdateMethod, strrpos($databaseUpdateMethod, '_') + 1);
		}

		return $lastAvailableUpdateRevision;
	}

	/**
	 * Returns revision of the last applied database update.
	 *
	 * @return int Revision of the last applied database update
	 */
	protected function getLastAppliedUpdate()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (!isset($dbConfig->DATABASE_REVISION)) {
			$dbConfig->DATABASE_REVISION = 1;
		}

		return (int)$dbConfig->DATABASE_REVISION;
	}

	/**
	 * Checks if a column exists in a database table and if not, execute a query to
	 * add that column.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4509
	 * @param string $table Database table name
	 * @param string $column Column to be added in the database table
	 * @param string $query Query to create column
	 * @return string Query to be executed
	 */
	protected function secureAddColumnTable($table, $column, $query){
		$dbName = iMSCP_Registry::get('config')->DATABASE_NAME;

		return "
			DROP PROCEDURE IF EXISTS test;
			CREATE PROCEDURE test()
			BEGIN
				if not exists(
					SELECT
						*
					FROM
						information_schema.COLUMNS
					WHERE
						column_name='$column'
					AND
						table_name='$table'
					AND
						table_schema='$dbName'
				) THEN
					$query;
				END IF;
			END;
			CALL test();
			DROP PROCEDURE IF EXISTS test;
		";
	}

	/**
	 * Checks if a column exists in a database table and if not, execute a query to
	 * add that column.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4509
	 * @param string $table Database table name
	 * @param string $column Column to be added in the database table
	 * @param string $query Query to create column
	 * @return string Query to be executed
	 */
	protected function dropColumn($table, $column){
		$dbName = iMSCP_Registry::get('config')->DATABASE_NAME;
		return "
			DROP PROCEDURE IF EXISTS test;
			CREATE PROCEDURE test()
			BEGIN
				IF EXISTS (
					SELECT
						COLUMN_NAME
					FROM
						information_schema.COLUMNS
					WHERE
						TABLE_NAME = '$table'
					AND
						COLUMN_NAME = '$column'
					AND
						table_schema = '$dbName'
				) THEN
					ALTER TABLE `$table` DROP column `$column`;
				END IF;
			END;
			CALL test();
			DROP PROCEDURE IF EXISTS test;
		";
	}

	/**
	 * Checks if a column exists in a database table and if not, execute a query to
	 * add that column.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4509
	 * @param string $table Database table name
	 * @param string $column Column to be added in the database table
	 * @param string $query Query to create column
	 * @return string Query to be executed
	 */
	protected function addColumn($table, $column, $structure){
		$dbName = iMSCP_Registry::get('config')->DATABASE_NAME;

		return ("
			DROP PROCEDURE IF EXISTS test;
			CREATE PROCEDURE test()
			BEGIN
				IF NOT EXISTS (
					SELECT
						COLUMN_NAME
					FROM
						information_schema.COLUMNS
					WHERE
						COLUMN_NAME = '$column'
					AND
						TABLE_NAME = '$table'
					AND
						TABLE_SCHEMA = '$dbName'
				) THEN
					ALTER TABLE `$dbName`.`$table` ADD `$column` $structure;
				END IF;
			END;
			CALL test();
			DROP PROCEDURE IF EXISTS test;
		");
	}

	/**
	 * Catch any database update that were removed.
	 *
	 * @paramstring $updateMethod Database method name
	 * @paramarray $param $parameter
	 * @return void
	 */
	public function __call($updateMethod, $param){}

	/**
	 * Fixes some CSRF issues in admin log.
	 *
	 * @author Thomas Wacker <thomas.wacker@ispcp.net>
	 * @since r3695
	 * @return array SQL Statement
	 */
	protected function _databaseUpdate_46(){
		return 'TRUNCATE TABLE `log`;';
	}

	/**
	 * Removes useless 'suexec_props' table.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r3709
	 * @return array SQL Statement
	 */
	protected function _databaseUpdate_47(){
		return 'DROP TABLE IF EXISTS `suexec_props`;';
	}

	/**
	 * Adds table for software installer (ticket #14).
	 *
	 * @author Sascha Bay <worst.case@gmx.de>
	 * @sincer3695
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_48(){
		$sqlUpd = array();
		$sqlUpd[] = "
	 		CREATE TABLE IF NOT EXISTS
	 			`web_software` (
					`software_id` int(10) unsigned NOT NULL auto_increment,
					`software_master_id` int(10) unsigned NOT NULL default '0',
					`reseller_id` int(10) unsigned NOT NULL default '0',
					`software_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_version` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_language` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_type` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_db` tinyint(1) NOT NULL,
					`software_archive` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_installfile` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_prefix` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_link` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_desc` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_active` int(1) NOT NULL,
					`software_status` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`rights_add_by` int(10) unsigned NOT NULL default '0',
					`software_depot` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
					PRIMARY KEY(`software_id`)
				) ENGINE=MyISAMDEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_inst` (
					`domain_id` int(10) unsigned NOT NULL,
					`alias_id` int(10) unsigned NOT NULL default '0',
					`subdomain_id` int(10) unsigned NOT NULL default '0',
					`subdomain_alias_id` int(10) unsigned NOT NULL default '0',
					`software_id` int(10) NOT NULL,
					`software_master_id` int(10) unsigned NOT NULL default '0',
					`software_res_del` int(1) NOT NULL default '0',
					`software_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_version` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_language` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`path` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`software_prefix` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`db` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`database_user` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`database_tmp_pwd` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`install_username` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`install_password` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`install_email` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`software_status` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_depot` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
					KEY `software_id` (`software_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

		$sqlUpd[] = self::secureAddColumnTable(
			'domain', 'domain_software_allowed',
			"
				ALTER TABLE
					`domain`
				ADD
					`domain_software_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'no'
			"
		);

		$sqlUpd[] = self::secureAddColumnTable(
			'reseller_props', 'software_allowed',
			"
				ALTER TABLE
					`reseller_props`
				ADD
					`software_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'no'
			"
		);

		$sqlUpd[] = self::secureAddColumnTable(
			'reseller_props', 'softwaredepot_allowed',
			"
				ALTER TABLE
					`reseller_props`
				ADD
					`softwaredepot_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'yes'
			"
		);

		$sqlUpd[] = "UPDATE `hosting_plans` SET `props` = CONCAT(`props`,';_no_');";

		return $sqlUpd;
	}

	/**
	 * Adds i-MSCP daemon service properties.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r4004
	 * @return void
	 */
	protected function _databaseUpdate_50(){
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');
		$dbConfig->PORT_IMSCP_DAEMON = "9876;tcp;i-MSCP-Daemon;1;0;127.0.0.1";
	}

	/**
	 * Adds field for on-click-logon from the ftp-user site(such as PMA).
	 *
	 * @author William Lightning <kassah@gmail.com>
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_51(){
		$query = "
			ALTER IGNORE TABLE
				`ftp_users`
			ADD
				`rawpasswd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
			AFTER
				`passwd`
		";

		return self::secureAddColumnTable('ftp_users', 'rawpasswd', $query);
	}

	/**
	 * Adds new options for applications instller.
	 *
	 * @author Sascha Bay <worst.case@gmx.de>
	 * @sincer4036
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_52()
	{
		$sqlUpd = array();

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_depot` (
					`package_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`package_install_type` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
					`package_title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_version` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
					`package_language` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
					`package_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
					`package_description` mediumtext character set utf8 collate utf8_unicode_ci NOT NULL,
					`package_vendor_hp` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_download_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_signature_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (`package_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_options` (
					`use_webdepot` tinyint(1) unsigned NOT NULL DEFAULT '1',
					`webdepot_xml_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					`webdepot_last_update` datetime NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

		$sqlUpd[] = "
			REPLACE INTO
				`web_software_options` (`use_webdepot`, `webdepot_xml_url`, `webdepot_last_update`)
			VALUES
				('1', 'http://app-pkg.i-mscp.net/imscp_webdepot_list.xml', '0000-00-00 00:00:00')
			;
		";

		$sqlUpd[] = self::secureAddColumnTable(
			'web_software',
			'software_installtype',
			"
				ALTER IGNORE TABLE
					`web_software`
				ADD
					`software_installtype` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL
				AFTER
					`reseller_id`
			"
		);

		$sqlUpd[] = " UPDATE `web_software` SET `software_installtype` = 'install'";

		$sqlUpd[] = self::secureAddColumnTable(
			'reseller_props',
			'websoftwaredepot_allowed',
			"
				ALTER IGNORE TABLE
					`reseller_props`
				ADD
					`websoftwaredepot_allowed` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL DEFAULT 'yes'
			"
		);

		return $sqlUpd;
	}

	/**
	 * Decrypt email, ftp and sql users password in database.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4509
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_53()
	{
		$sqlUpd = array();

		$status = iMSCP_Registry::get('config')->ITEM_CHANGE_STATUS;

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		$query = "
			SELECT
				`mail_id`, `mail_pass`
			FROM
				`mail_users`
			WHERE
				`mail_type` RLIKE '^normal_mail'
			OR
				`mail_type` RLIKE '^alias_mail'
			OR
				`mail_type` RLIKE '^subdom_mail'
		";

		$stmt = execute_query($query);

		if ($stmt->recordCount() != 0) {
			while (!$stmt->EOF) {
				$sqlUpd[] = "
					UPDATE
						`mail_users`
					SET
						`mail_pass`= " . $db->quote(decrypt_db_password($stmt->fields['mail_pass'])) . ",
						`status` = '$status' WHERE `mail_id` = '" . $stmt->fields['mail_id'] . "'
					;
				";

				$stmt->moveNext();
			}
		}

		$stmt = exec_query("SELECT `sqlu_id`, `sqlu_pass` FROM `sql_user`");

		if ($stmt->recordCount() != 0) {
			while (!$stmt->EOF) {
				$sqlUpd[] = "
					UPDATE
						`sql_user`
					SET
						`sqlu_pass` = " . $db->quote(decrypt_db_password($stmt->fields['sqlu_pass'])) . "
					WHERE `sqlu_id` = '" . $stmt->fields['sqlu_id'] . "'
					;
				";

				$stmt->moveNext();
			}
		}

		$stmt = exec_query("SELECT `userid`, `rawpasswd` FROM `ftp_users`");

		if ($stmt->recordCount() != 0) {
			while (!$stmt->EOF) {
				$sqlUpd[] = "
					UPDATE
						`ftp_users`
					SET
						`rawpasswd` = " . $db->quote(decrypt_db_password($stmt->fields['rawpasswd'])) . "
					WHERE
						`userid` = '" . $stmt->fields['userid'] . "'
					;
				";

				$stmt->moveNext();
			}
		}

		return $sqlUpd;
	}

	/**
	 * Convert tables to InnoDB.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4509
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_54()
	{
		$sqlUpd = array();

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		$tables = $db->metaTables();

		foreach ($tables as $table) {
			$sqlUpd[] = "ALTER TABLE `$table` ENGINE=InnoDB";
		}

		return $sqlUpd;
	}

	/**
	 * Adds unique index on user_gui_props.user_id column.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r4592
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_56(){
		$dbName = iMSCP_Registry::get('config')->DATABASE_NAME;

		$sqlUpd = array();

		$sqlUpd[] = "
			DROP PROCEDURE IF EXISTS schema_change;
				CREATE PROCEDURE schema_change()
				BEGIN
					IF EXISTS (
						SELECT
							CONSTRAINT_NAME
						FROM
							`information_schema`.`KEY_COLUMN_USAGE`
						WHERE
							TABLE_NAME = 'user_gui_props'
						AND
							CONSTRAINT_NAME = 'user_id'
						AND
							TABLE_SCHEMA = '$dbName'
					) THEN
						ALTER IGNORE TABLE `$dbName`.`user_gui_props` DROP INDEX `user_id`;
					END IF;
				END;
				CALL schema_change();
			DROP PROCEDURE IF EXITST schema_change;
		";

		$sqlUpd[] = 'ALTER TABLE `$dbName`.`user_gui_props` ADD UNIQUE (`user_id`)';

		return $sqlUpd;
	}

	/**
	 * Drop useless column in user_gui_props table.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r4644
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_59(){

		$dbName = iMSCP_Registry::get('config')->DATABASE_NAME;

		return "
			DROP PROCEDURE IF EXISTS schema_change;
				CREATE PROCEDURE schema_change()
				BEGIN
					IF EXISTS (
						SELECT
							COLUMN_NAME
						FROM
							information_schema.COLUMNS
						WHERE
							TABLE_NAME = 'user_gui_props'
						AND
							COLUMN_NAME = 'id'
						AND
							TABLE_SCHEMA = '$dbName'
					) THEN
						ALTER TABLE `$dbName`.`user_gui_props` DROP column `id`;
					END IF;
				END;
				CALL schema_change();
			DROP PROCEDURE IF EXITST schema_change;
		";
	}

	/**
	 * Convert tables to InnoDB.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4650
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_60()
	{
		return 'ALTER TABLE `autoreplies_log` ENGINE=InnoDB';
	}

	/**
	 * Deletes old parameters from config table.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r4779
	 * @return void
	 */
	protected function _databaseUpdate_66()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig->DUMP_GUI_DEBUG)) {
			$dbConfig->del('DUMP_GUI_DEBUG');
		}
	}


	/**
	 * #124: Enhancement - Switch to gettext (Machine Object Files)
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r4792
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_67()
	{
		$sqlUpd = array();

		// First step: Update default language (new naming convention)

		$dbConfig = iMSCP_Registry::get('dbConfig');
		if (isset($dbConfig->USER_INITIAL_LANG)) {
			$dbConfig->USER_INITIAL_LANG = str_replace(
				'lang_', '', $dbConfig->USER_INITIAL_LANG);
		}

		// second step: Removing all database languages tables

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		foreach ($db->metaTables() as $tableName) {
			if (strpos($tableName, 'lang_') !== false) {
				$sqlUpd[] = "DROP TABLE `$tableName`";
			}
		}

		// third step: Update users language property

		$languagesMap = array(
			'Arabic' => 'ar_AE', 'Azerbaijani' => 'az_AZ', 'BasqueSpain' => 'eu_ES',
			'Bulgarian' => 'bg_BG', 'Catalan' => 'ca_ES', 'ChineseChina' => 'zh_CN',
			'ChineseHongKong' => 'zh_HK', 'ChineseTaiwan' => 'zh_TW', 'Czech' => 'cs_CZ',
			'Danish' => 'da_DK', 'Dutch' => 'nl_NL', 'EnglishBritain' => 'en_GB',
			'FarsiIran' => 'fa_IR', 'Finnish' => 'fi_FI', 'FrenchFrance' => 'fr_FR',
			'Galego' => 'gl_ES', 'GermanGermany' => 'de_DE', 'GreekGreece' => 'el_GR',
			'Hungarian' => 'hu_HU', 'ItalianItaly' => 'it_IT', 'Japanese' => 'ja_JP',
			'Lithuanian' => 'lt_LT', 'NorwegianNorway' => 'nb_NO', 'Polish' => 'pl_PL',
			'PortugueseBrazil' => 'pt_BR', 'Portuguese' => 'pt_PT', 'Romanian' => 'ro_RO',
			'Russian' => 'ru_RU', 'Slovak' => 'sk_SK', 'SpanishArgentina' => 'es_AR',
			'SpanishSpain' => 'es_ES', 'Swedish' => 'sv_SE', 'Thai' => 'th_TH',
			'Turkish' => 'tr_TR', 'Ukrainian' => 'uk_UA');

		// Updates language property of each users by using new naming convention
		// Thanks to Marc Pujol for idea
		foreach($languagesMap as $language => $locale) {
			$sqlUpd[] = "
				UPDATE
					`user_gui_props`
				SET
					`lang` = '$locale'
				WHERE
					`lang` = 'lang_{$language}'";
		}

		return $sqlUpd;
	}

	/**
	 * #119: Defect - Error when adding IP's
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4844
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_68()
	{
		$sqlUpd = array();

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		$stmt = exec_query("SELECT `ip_id`, `ip_card` FROM `server_ips`");

		if ($stmt->recordCount() != 0) {
			while (!$stmt->EOF) {
				$cardname = explode(':', $stmt->fields['ip_card']);
				$cardname = $cardname[0];
				$sqlUpd[] = "
					UPDATE
						`server_ips`
					SET
						`ip_card` = " . $db->quote($cardname) . "
					WHERE
						`ip_id` = '" . $stmt->fields['ip_id'] . "'
				";

				$stmt->moveNext();
			}
		}

		return $sqlUpd;
	}

	/**
	 * Some fixes for user_gui_props table.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r4961
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_69()
	{
		return array(
			"ALTER TABLE `user_gui_props` CHANGE `user_id` `user_id` INT( 10 ) UNSIGNED NOT NULL",
			"ALTER TABLE `user_gui_props` CHANGE `layout` `layout`
				VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL",
			"ALTER TABLE `user_gui_props` CHANGE `logo` `logo`
				VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''",
			"ALTER TABLE `user_gui_props` CHANGE `lang` `lang`
				VARCHAR( 5 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL",
			"UPDATE `user_gui_props` SET `logo` = '' WHERE `logo` = 0");
	}

	/**
	 * Deletes possible orphan items.
	 *
	 * See #145 on i-MSCP issue tracker for more information.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r4961
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_70()
	{
		$sqlUpd = array();

		$tablesToForeignKey = array(
			'email_tpls' => 'owner_id', 'hosting_plans' => 'reseller_id',
			'orders' => 'user_id', 'orders_settings' => 'user_id',
			'reseller_props' => 'reseller_id', 'tickets' => 'ticket_to',
			'tickets' => 'ticket_from', 'user_gui_props' => 'user_id',
			'web_software' => 'reseller_id');

		$stmt = execute_query('SELECT `admin_id` FROM `admin`');
		$usersIds = implode(',', $stmt->fetchall(PDO::FETCH_COLUMN));

		foreach ($tablesToForeignKey as $table => $foreignKey) {
			$sqlUpd[] = "DELETE FROM `$table` WHERE `$foreignKey` NOT IN ($usersIds)";
		}

		return $sqlUpd;
	}

	/**
	 * Changes log table schema to allow storage of large messages.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r5002
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_71()
	{
		return '
			ALTER TABLE `log` CHANGE `log_message` `log_message`
			TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL';
	}

	/**
	 * Changes log table schema to allow storage of large messages.
	 *
	 * @author Daniel Andreca<sci2tech@gmail.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_72() {
		return 'ALTER IGNORE TABLE `web_software_options` ADD UNIQUE (`use_webdepot`)';
	}

	/**
	 * Add dovecot quota table.
	 *
	 * @author Daniel Andreca<sci2tech@gmail.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_73() {
		return "
			CREATE TABLE IF NOT EXISTS `quota_dovecot` (
			`username` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
			`bytes` bigint(20) NOT NULL DEFAULT '0',
			`messages` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`username`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";
	}

	/**
	 * Increase quota value.
	 *
	 * @author Daniel Andreca<sci2tech@gmail.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_75() {
		return "
			UPDATE `mail_users` SET `quota` = '104857600' WHERE `quota` = '10485760';
		";
	}

	protected function _databaseUpdate_76(){
		return array(
			"CREATE TABLE IF NOT EXISTS `user` (
				`user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`user_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_pass` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_type` enum('admin','reseller', 'client') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'client',
				`user_created` int(10) unsigned NOT NULL DEFAULT '0',
				`user_created_by` int(10) unsigned DEFAULT '0',
				`user_uniqkey` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_uniqkey_time` timestamp NULL DEFAULT NULL,
				`user_status` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'toadd',
				UNIQUE KEY `user_id` (`user_id`),
				UNIQUE KEY `user_name` (`user_name`)
			) ENGINE=InnoDB	DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			",
			"CREATE TABLE IF NOT EXISTS `user_data` (
				`user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`user_fname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_lname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_gender` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_firm` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_zip` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_fax` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_street1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				`user_street2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				UNIQUE KEY `user_id` (`user_id`)
			) ENGINE=InnoDB	DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			",
			"CREATE TABLE IF NOT EXISTS `user_system_props` (
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
				`user_cron` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
				`user_php` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
				`user_cgi` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
				`user_backups` enum('full','sql','domain','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
				`user_dns` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
				`user_software_allowed` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
				UNIQUE KEY `user_id` (`user_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		"
		);
	}
	/**
	 * Migrate user props to user_system_prop table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_77(){
		return "
			REPLACE INTO
				`user`
			(
				`user_id`, `user_name`, `user_pass`, `user_created`, `user_created_by`,
				`user_uniqkey`, `user_uniqkey_time`, `user_status`, `user_type`
			)
			SELECT
				`admin_id`, `admin_name`, `admin_pass`, `domain_created`,
				`created_by`, `uniqkey`, `uniqkey_time`, 'toadd' as `admin_status`,
				if(`admin_type` = 'user', 'client', `admin_type`)
			FROM
				`admin`
		";
	}

	/**
	 * Migrate user props to user_system_prop table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_78(){
		return "
			REPLACE INTO
				`user_system_props`
			(
				`user_id`, `user_mailacc_limit`, `user_ftpacc_limit`,
				`user_traffic_limit`, `user_sqld_limit`, `user_sqlu_limit`,
				`user_domain_limit`, `user_subd_limit`, `user_ip_ids`,
				`user_disk_limit`, `user_disk_usage`, `user_php`, `user_cgi`,
				`user_backups`, `user_dns`, `user_software_allowed`
			)
			SELECT
				`domain_admin_id`, `domain_mailacc_limit`, `domain_ftpacc_limit`,
				`domain_traffic_limit`, `domain_sqld_limit`, `domain_sqlu_limit`,
				`domain_alias_limit`, `domain_subd_limit`, `domain_ip_id`,
				`domain_disk_limit`, `domain_disk_usage`, `domain_php`,
				`domain_cgi`, `allowbackup`, `domain_dns`, `domain_software_allowed`
			FROM
				`domain`
		";
	}

	/**
	 * Migrate user props to user_system_prop table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_79(){
		return "
			REPLACE INTO
				`user_data`
			(
				`user_id`, `user_fname`, `user_lname`, `user_gender`, `user_firm`,
				`user_zip`, `user_city`, `user_state`, `user_country`, `user_email`,
				`user_phone`, `user_fax`, `user_street1`, `user_street2`
			)
			SELECT
				`admin_id`, `fname`, `lname`, `gender`, `firm`, `zip`, `city`, `state`,
				`country`, `email`, `phone`, `fax`, `street1`, `street2`
			FROM
				`admin`
		";
	}

	/**
	 * Add user status column.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_80(){
		$sqlUpd = array();
		$columns = array(
			array('domain',		'domain_mount_point',	"VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '/'"),
			array('domain',		'url_forward',			"VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'"),
			array('ftp_group',	'user_id',				"INT(10) unsigned NOT NULL DEFAULT '0' FIRST"),
			array('ftp_users',	'user_id',				"INT(10) unsigned NOT NULL DEFAULT '0' FIRST"),
			array('web_software_inst', 'id',			"INT(10) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)"),
		);
		foreach($columns as $column){
			$sqlUpd[] = self::addColumn($column[0], $column[1], $column[2]);
		}
		return $sqlUpd;
	}

	/**
	 * Rename subdomains as full name.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_81(){

		return "
			REPLACE INTO
				`subdomain`
			(
				`subdomain_id`,
				`domain_id`,
				`subdomain_name`,
				`subdomain_mount`,
				`subdomain_url_forward`,
				`subdomain_status`
			)
			SELECT
				`t1`.`subdomain_id`,
				`t1`.`domain_id`,
				CONCAT(`t1`.`subdomain_name`, '.', `t2`.`domain_name`) AS `subdomain_name`,
				`t1`.`subdomain_mount`,
				`t1`.`subdomain_url_forward`,
				`t1`.`subdomain_status`
			FROM
				`subdomain` as `t1`
			LEFT JOIN
				`domain` AS `t2`
			ON
				`t1`.`domain_id` = `t2`.`domain_id`
		";
	}

	/**
	 * Rename aliasses subdomains as full name.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_82(){

		return "
			REPLACE INTO
				`subdomain_alias`
			(
				`subdomain_alias_id`,
				`alias_id`,
				`subdomain_alias_name`,
				`subdomain_alias_mount`,
				`subdomain_alias_url_forward`,
				`subdomain_alias_status`
			)
			SELECT
				`t1`.`subdomain_alias_id`,
				`t1`.`alias_id`,
				CONCAT(`t1`.`subdomain_alias_name`, '.', `t2`.`alias_name`) AS `subdomain_alias_name`,
				`t1`.`subdomain_alias_mount`,
				`t1`.`subdomain_alias_url_forward`,
				`t1`.`subdomain_alias_status`
			FROM
				`subdomain_alias` as `t1`
			LEFT JOIN
				`domain_aliasses` AS `t2`
			ON
				`t1`.`alias_id` = `t2`.`alias_id`
		";
	}

	/**
	 * Move alias to domain table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_83(){

		return array("
			REPLACE INTO
				`domain`
			(
				`domain_name`,
				`domain_created`,
				`domain_expires`,
				`domain_last_modified`,
				`domain_status`,
				`domain_mount_point`,
				`domain_admin_id`,
				`domain_created_id`,
				`domain_ip_id`,
				`url_forward`
			)
			SELECT
				`t1`.`alias_name` AS `domain_name`,
				'0' AS `domain_created`,
				'0' AS `domain_expires`,
				'0' AS `domain_last_modified`,
				'toadd' AS `domain_status`,
				`t1`.`alias_mount` AS `domain_mount_point`,
				`t2`.`domain_admin_id`,
				`t2`.`domain_created_id`,
				`t2`.`domain_ip_id`,
				`t1`.`url_forward`
			FROM
				`domain_aliasses` AS `t1`
			LEFT JOIN
				`domain` AS `t2`
			ON
				`t1`.`domain_id` = `t2`.`domain_id`
		",
		"UPDATE `domain` SET `url_forward` = 'no' WHERE `url_forward` IS NULL",
		);
	}

	/**
	 * Update alias in domain_dns table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_84(){

		return "
			REPLACE INTO
				`domain_dns`
			(
				`domain_dns_id`,
				`domain_id`,
				`alias_id`,
				`domain_dns`,
				`domain_class`,
				`domain_type`,
				`domain_text`,
				`protected`
			)
			SELECT
				`t1`.`domain_dns_id`,
				`t3`.`domain_id`,
				'0' AS `alias_id`,
				`t1`.`domain_dns`,
				`t1`.`domain_class`,
				`t1`.`domain_type`,
				`t1`.`domain_text`,
				`t1`.`protected`
			FROM
				`domain_dns` as `t1`
			LEFT JOIN
				`domain_aliasses` AS `t2`
			ON
				`t1`.`alias_id` = `t2`.`alias_id`
			LEFT JOIN
				`domain` AS `t3`
			ON
				`t2`.`alias_name` = `t3`.`domain_name`
			WHERE
				`t3`.`domain_id` IS NOT NULL
		";
	}

	/**
	 * Update alias in domain_dns table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_85(){

		return array("
			REPLACE INTO
				`ftp_group`
			(
				`user_id`,
				`groupname`,
				`gid`,
				`members`
			)
			SELECT
				`t2`.`domain_admin_id`,
				`t1`.`groupname`,
				`t1`.`gid`,
				`t1`.`members`
			FROM
				`ftp_group` as `t1`
			LEFT JOIN
				`domain` AS `t2`
			ON
				`t1`.`gid` = `t2`.`domain_gid`
		", "DELETE FROM `ftp_group` WHERE `user_id` = 0"
		);
	}

	/**
	 * Update alias in domain_dns table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_86(){

		return array("
			REPLACE INTO
				`ftp_users`
			(
				`user_id`,
				`userid`,
				`passwd`,
				`rawpasswd`,
				`uid`,
				`gid`,
				`shell`,
				`homedir`
			)
			SELECT
				`t2`.`domain_admin_id`,
				`t1`.`userid`,
				`t1`.`passwd`,
				`t1`.`rawpasswd`,
				`t1`.`uid`,
				`t1`.`gid`,
				`t1`.`shell`,
				`t1`.`homedir`
			FROM
				`ftp_users` as `t1`
			LEFT JOIN
				`domain` AS `t2`
			ON
				`t1`.`uid` = `t2`.`domain_uid`
		", "DELETE FROM `ftp_users` WHERE `user_id` = 0"
		);
	}

	/**
	 * Update alias in domain_dns table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_87(){

		return array("
			REPLACE INTO
				`sql_database`
			(
				`sqld_id`,
				`domain_id`,
				`sqld_name`
			)
			SELECT
				`t1`.`sqld_id`,
				`t2`.`domain_admin_id`,
				`t1`.`sqld_name`
			FROM
				`sql_database` as `t1`
			LEFT JOIN
				`domain` AS `t2`
			ON
				`t1`.`domain_id` = `t2`.`domain_id`
		", "ALTER TABLE `sql_database` CHANGE `domain_id` `user_id` INT( 10 ) UNSIGNED NULL DEFAULT '0'"
		);
	}

	/**
	 * Update subdomain alias in subdomain table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_88(){

		return "
			INSERT INTO
				`subdomain`
			(
				`domain_id`,
				`subdomain_name`,
				`subdomain_mount`,
				`subdomain_url_forward`,
				`subdomain_status`
			)
			SELECT
				`t3`.`domain_id`,
				`subdomain_alias_name` AS `subdomain_name`,
				`subdomain_alias_mount` AS `subdomain_mount`,
				`subdomain_alias_url_forward` AS `subdomain_url_forward`,
				'toadd' AS `subdomain_status`
			FROM
				`subdomain_alias` as `t1`
			LEFT JOIN
				`domain_aliasses` AS `t2`
			ON
				`t1`.`alias_id` = `t2`.`alias_id`
			LEFT JOIN
				`domain` AS `t3`
			ON
				`t2`.`alias_name` = `t3`.`domain_name`
		";
	}
	/**
	 * Update mail in domain_dns table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_89(){
		$sqlUpd = array();

		$columns = array(
			'alias_mail'				=> 'normal_mail',
			'alias_forward'				=> 'normal_forward',
			'alias_mail,alias_forward'	=> 'normal_mail,normal_forward',
		);

		foreach($columns as $oldValue => $newValue){
			$sqlUpd[] = "
				REPLACE INTO
					`mail_users`
				(
					`mail_id`, `mail_acc`, `mail_pass`, `mail_forward`, `domain_id`,
					`mail_type`, `sub_id`, `status`, `mail_auto_respond`,
					`mail_auto_respond_text`, `quota`, `mail_addr`
				)
				SELECT
					`t1`.`mail_id`, `t1`.`mail_acc`, `t1`.`mail_pass`,
					`t1`.`mail_forward`, `t3`.`domain_id`, '$newValue' AS `mail_type`,
					'0' AS `sub_id`, `t1`.`status`, `t1`.`mail_auto_respond`,
					`t1`.`mail_auto_respond_text`, `t1`.`quota`, `t1`.`mail_addr`
				FROM
					`mail_users` as `t1`
				LEFT JOIN
					`domain_aliasses` AS `t2`
				ON
					`t1`.`sub_id` = `t2`.`alias_id`
				LEFT JOIN
					`domain` AS `t3`
				ON
					`t2`.`alias_name` = `t3`.`domain_name`
				WHERE
					`t1`.`mail_type` = '$oldValue'
			";
		}
		return $sqlUpd;
	}
	/**
	 * Update catchall for alias.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_90(){

		return "
			REPLACE INTO
				`mail_users`
			(
				`mail_id`, `mail_acc`, `mail_pass`, `mail_forward`, `domain_id`,
				`mail_type`, `sub_id`, `status`, `mail_auto_respond`,
				`mail_auto_respond_text`, `quota`, `mail_addr`
			)
			SELECT
				`t1`.`mail_id`, `t1`.`mail_acc`, `t1`.`mail_pass`,
				`t1`.`mail_forward`, `t3`.`domain_id`, 'normal_catchall' AS `mail_type`,
				'0' AS `sub_id`, `t1`.`status`, `t1`.`mail_auto_respond`,
				`t1`.`mail_auto_respond_text`, `t1`.`quota`, `t1`.`mail_addr`
			FROM
				`mail_users` as `t1`
			LEFT JOIN
				`domain_aliasses` AS `t2`
			ON
				`t1`.`sub_id` = `t2`.`alias_id`
			LEFT JOIN
				`domain` AS `t3`
			ON
				`t2`.`alias_name` = `t3`.`domain_name`
			WHERE
				`t1`.`mail_type` = 'alias_catchall'
		";
	}

	/**
	 * Update mail in domain_dns table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_91(){
		$sqlUpd = array();

		$columns = array(
			'alssub_mail'					=> 'subdom_mail',
			'alssub_forward'				=> 'subdom_forward',
			'alssub_mail,alssub_forward'	=> 'subdom_mail,subdom_forward',
		);

		foreach($columns as $oldValue => $newValue){
			$sqlUpd[] = "
				REPLACE INTO
					`mail_users`
				(
					`mail_id`, `mail_acc`, `mail_pass`, `mail_forward`, `domain_id`,
					`mail_type`, `sub_id`, `status`, `mail_auto_respond`,
					`mail_auto_respond_text`, `quota`, `mail_addr`
				)
				SELECT
					`t1`.`mail_id`, `t1`.`mail_acc`, `t1`.`mail_pass`, `t1`.`mail_forward`,
					`t3`.`domain_id` AS `domain_id`, '$newValue' AS `mail_type`, `t3`.`subdomain_id` AS `sub_id`,
					`t1`.`status`, `t1`.`mail_auto_respond`, `t1`.`mail_auto_respond_text`,
					`t1`.`quota`, `t1`.`mail_addr`
				FROM
					`mail_users` as `t1`
				LEFT JOIN
					`subdomain_alias` AS `t2`
				ON
					`t1`.`sub_id` = `t2`.`subdomain_alias_id`
				LEFT JOIN
					`subdomain` AS `t3`
				ON
					`t2`.`subdomain_alias_name` = `t3`.`subdomain_name`
				WHERE
					`t1`.`mail_type` = '$oldValue'
			";
		}
		return $sqlUpd;
	}
	/**
	 * Update catchall for alias.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */

	protected function _databaseUpdate_92(){

		return "
			REPLACE INTO
				`mail_users`
			(
				`mail_id`, `mail_acc`, `mail_pass`, `mail_forward`, `domain_id`,
				`mail_type`, `sub_id`, `status`, `mail_auto_respond`,
				`mail_auto_respond_text`, `quota`, `mail_addr`
			)
			SELECT
				`t1`.`mail_id`, `t1`.`mail_acc`, `t1`.`mail_pass`, `t1`.`mail_forward`,
				`t3`.`domain_id` AS `domain_id`, 'subdom_catchall' AS `mail_type`, `t3`.`subdomain_id` AS `sub_id`,
				`t1`.`status`, `t1`.`mail_auto_respond`, `t1`.`mail_auto_respond_text`,
				`t1`.`quota`, `t1`.`mail_addr`
			FROM
				`mail_users` as `t1`
			LEFT JOIN
				`subdomain_alias` AS `t2`
			ON
				`t1`.`sub_id` = `t2`.`subdomain_alias_id`
			LEFT JOIN
				`subdomain` AS `t3`
			ON
				`t2`.`subdomain_alias_name` = `t3`.`subdomain_name`
			WHERE
				`t1`.`mail_type` = 'alssub_catchall'
		";
	}

	/**
	 * Update mail in domain_dns table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_93(){
		return "
			REPLACE INTO
				`mail_users`
			(
				`mail_id`, `mail_acc`, `mail_pass`, `mail_forward`, `domain_id`,
				`mail_type`, `sub_id`, `status`, `mail_auto_respond`,
				`mail_auto_respond_text`, `quota`, `mail_addr`
			)
			SELECT
				`t1`.`mail_id`, `t1`.`mail_acc`, `t1`.`mail_pass`, `t1`.`mail_forward`,
				`t1`.`domain_id`, `t1`.`mail_type`, `t1`.`sub_id`, `t1`.`status`,
				`t1`.`mail_auto_respond`, `t1`.`mail_auto_respond_text`,
				`t1`.`quota`,
			CONCAT(
				`t1`.`mail_acc`,
				'@',
				IF(
					`t3`.`subdomain_name` IS NULL,
					`t2`.`domain_name`,
					`t3`.`subdomain_name`
				)
			) AS `mail_addr`
			FROM
				`mail_users` AS `t1`
			LEFT JOIN
				`domain` as `t2`
			ON
				`t1`.`domain_id` = `t2`.`domain_id`
			LEFT JOIN
				`subdomain` as `t3`
			ON
				`t3`.`subdomain_id` = `t1`.`sub_id`
			WHERE
				`mail_addr` = ''
		";
	}
	/**
	 * Update mail in domain_dns table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_94(){

		return
			"REPLACE INTO
				`web_software_inst`
			(
				`id`, `domain_id`, `alias_id`, `subdomain_id`, `subdomain_alias_id`,
				`software_id`, `software_master_id`, `software_res_del`,
				`software_name`, `software_version`, `software_language`, `path`,
				`software_prefix`, `db`, `database_user`, `database_tmp_pwd`,
				`install_username`, `install_password`, `install_email`,
				`software_status`, `software_depot`
			)
			SELECT
				`t1`.`id`,
				IF(`t3`.`domain_id` IS NULL, '0', `t3`.`domain_id`) AS `domain_id`,
				'0' AS `alias_id`,
				IF(`t5`.`subdomain_id` IS NULL, '0', `t5`.`subdomain_id`) AS `subdomain_id`,
				'0' AS `subdomain_alias_id`,
				`t1`.`software_id`,
				`t1`.`software_master_id`,
				`t1`.`software_res_del`,
				`t1`.`software_name`,
				`t1`.`software_version`,
				`t1`.`software_language`,
				`t1`.`path`,
				`t1`.`software_prefix`,
				`t1`.`db`,
				`t1`.`database_user`,
				`t1`.`database_tmp_pwd`,
				`t1`.`install_username`,
				`t1`.`install_password`,
				`t1`.`install_email`,
				`t1`.`software_status`,
				`t1`.`software_depot`
			FROM `web_software_inst` as `t1`
			LEFT JOIN `domain_aliasses` AS `t2` ON `t1`.`alias_id` = `t2`.`alias_id`
			LEFT JOIN `domain` AS `t3` ON `t2`.`alias_name` = `t3`.`domain_name`
			LEFT JOIN `subdomain_alias` as `t4` ON
				`t1`.`subdomain_alias_id` = `t4`.`subdomain_alias_id`
			LEFT JOIN `subdomain` as `t5` ON
				`t4`.`subdomain_alias_name` = `t5`.`subdomain_name`
			WHERE `t1`.`alias_id` != '0' OR `t1`.`subdomain_alias_id` != '0'
		";
	}
	/**
	 * Drop not used columns.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_95(){

		$sqlUpd = array();

		$columns = array(

			'domain_gid'				=> 'domain',
			'domain_uid'				=> 'domain',
			'domain_mailacc_limit'		=> 'domain',
			'domain_ftpacc_limit'		=> 'domain',
			'domain_traffic_limit'		=> 'domain',
			'domain_sqld_limit'			=> 'domain',
			'domain_sqlu_limit'			=> 'domain',
			'domain_alias_limit'		=> 'domain',
			'domain_subd_limit'			=> 'domain',
			'domain_disk_limit'			=> 'domain',
			'domain_disk_usage'			=> 'domain',
			'domain_php'				=> 'domain',
			'domain_cgi'				=> 'domain',
			'allowbackup'				=> 'domain',
			'domain_dns'				=> 'domain',
			'domain_software_allowed'	=> 'domain',

			'alias_id'					=> 'domain_dns',

			'alias_mount'				=> 'domain_aliasses',
			'alias_ip_id'				=> 'domain_aliasses',
			'url_forward'				=> 'domain_aliasses',

			'alias_id'					=> 'domain_dns',

			'max_als_cnt'				=> 'reseller_props',

			'subdomain_alias_id'		=> 'web_software_inst',
			'alias_id'					=> 'web_software_inst'

		);
		foreach($columns as $column => $table){
			$sqlUpd[] = self::dropColumn($table, $column);
		}
		return $sqlUpd;
	}

	/**
	 * Drop not used table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_96(){
		return array(
			"DROP TABLE IF EXISTS `admin`",
			"DROP TABLE IF EXISTS `auto_num`",
			"DROP TABLE IF EXISTS `subdomain_alias`"
		);
	}

	/**
	 * Empty table.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_97(){
		return "TRUNCATE TABLE `domain_aliasses`";
	}

	/**
	 * Rename Columns.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since $Id$
	 * @return string SQL Statement
	 */

	protected function _databaseUpdate_98(){
		return "UPDATE `domain` SET `domain_mount_point` = CONCAT('/', `domain_name`)
				WHERE `domain_mount_point` = '/'";
	}
}
