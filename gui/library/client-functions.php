<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version	 SVN: $Id$
 * @link		http://i-mscp.net
 * @author	  ispCP Team
 * @author	  i-MSCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Returns domains id that belong to a specific user.
 *
 * @return array All domains of one user number
 */
function get_user_domains_id($user_id = null){

	static $ids = null;

	if(!is_null($ids)){ return $ids; }

	$user_id = is_null($user_id) ? $_SESSION['user_id'] : $user_id;

	$query = "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?";
	$stmt = exec_query($query, $user_id);

	$rows = $stmt->fetchAll();

	$ids = array();
	foreach($rows as $dmn){ array_push($ids, $dmn['domain_id']); }

	return $ids;
}


/**
 * Returns total number of subdomains that belong to a specific user.
 *
 * Note, this function doesn't make any differentiation between sub domains and the
 * aliasses subdomains. The result is simply the sum of both.
 *
 * @param  int $domain_id Domain unique identifier
 * @return int Total number of subdomains
 */
function get_user_running_sub_cnt($user_id = null){

	static $cnt = null;
	if(!is_null($cnt)){ return $cnt; }

	$user_id = is_null($user_id) ? $_SESSION['user_id'] : $user_id;

	$query = "
		SELECT
			SUM(`subdomains_on_domain`) AS `cnt`
		FROM
			`domain`
		LEFT JOIN
			(
				SELECT
					`domain_id` AS `id`,
					COUNT( `domain_id` ) AS `subdomains_on_domain`
				FROM
					`subdomain`
				GROUP BY
					`domain_id`
			) AS `subdomain_count`
		ON
			`domain`.`domain_id` = `subdomain_count`.`id`
		WHERE
			`domain_admin_id` = ?
	";
	$stmt = exec_query($query, $user_id);

	$cnt = $stmt->fields['cnt'];
	$cnt = is_null($cnt) ? 0 : $cnt;

	return $cnt;
}

/**
 * Returns number of domain aliasses that belong to a specific user.
 *
 * @param  int $domain_id Domain unique identifier
 * @return int Total number of domain aliasses
 */
function get_user_running_als_cnt($user_id = null){

	static $cnt = null;
	if(!is_null($cnt)){ return $cnt; }

	$user_id = is_null($user_id) ? $_SESSION['user_id'] : $user_id;

	$query = "
		SELECT
			SUM(`aliasses_on_domain`) AS `cnt`
		FROM
			`domain`
		LEFT JOIN
			(
				SELECT
					`domain_id` AS `id`,
					COUNT( `domain_id` ) AS `aliasses_on_domain`
				FROM
					`domain_aliasses`
				GROUP BY
					`domain_id`
			) AS `aliasses_count`
		ON
			`domain`.`domain_id` = `aliasses_count`.`id`
		WHERE
			`domain_admin_id` = ?
	";
	$stmt = exec_query($query, $user_id);

	$cnt = $stmt->fields['cnt'];
	$cnt = is_null($cnt) ? 0 : $cnt;

	return $cnt;
}

/**
 * Returns information about number of mail account for a specific user.
 *
 * @param  int $domain_id	 Domain unique identifier
 * @return array			  An array of values where the first item is the sum of
 *							all other items, and where each other item represents
 *							total number of a specific Mail account type
 */
function get_user_running_mail_acc_cnt($user_id = null){

	static $cnt = null;
	if(!is_null($cnt)){ return $cnt; }

	$user_id = is_null($user_id) ? $_SESSION['user_id'] : $user_id;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$ids = get_user_domains_id($user_id);
	if($ids == array()){ return 0; }

	$query = "
		SELECT
			COUNT(`mail_id`) AS `cnt`
		FROM
			`mail_users`
		WHERE
			`mail_type` NOT IN ('normal_catchall', 'subdom_catchall')
		AND
			`domain_id` IN (".implode(', ', $ids).")
	";

	if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES == 0) {
		$query .=
			"
			AND
				`mail_acc` NOT IN ('abuse', 'postmaster', 'webmaster')
		";
	}

	$stmt = exec_query($query);
	$cnt = $stmt->fields['cnt'];

	$cnt = is_null($cnt) ? 0 : $cnt;

	return $cnt;
}

/**
 * Returns information about number of ftp account for a specific user.
 *
 * @param int $user_id	 User unique identifier
 * @return array		 An array of values where the first item is the sum of
 *						 all other items, and where each other item represents
 *						 total number of a specific Mail account type
 */
function get_user_running_ftp_acc_cnt($user_id = null){

	static $cnt = null;
	if(!is_null($cnt)){ return $cnt; }

	$user_id = is_null($user_id) ? $_SESSION['user_id'] : $user_id;

	$ids = get_user_domains_id($user_id);
	if($ids == array()){ return 0; }

	$query = "
		SELECT
			COUNT(`userid`) AS `cnt`
		FROM
			`ftp_users`
		WHERE
			`user_id` = ?
	";

	$stmt = exec_query($query, $user_id);
	$cnt = $stmt->fields['cnt'];

	$cnt = is_null($cnt) ? 0 : $cnt;

	return $cnt;
}

/**
 * Returns total number of databases that belong to a specific user.
 *
 * @param  int $domain_id Domain unique identifier
 * @return int Total number of databases for a specific domain
 */
function get_user_running_sqld_acc_cnt($user_id = null){

	static $cnt = null;
	if(!is_null($cnt)){ return $cnt; }

	$user_id = is_null($user_id) ? $_SESSION['user_id'] : $user_id;

	$query = "
		SELECT
			COUNT(*) AS `cnt`
		FROM
			`sql_database`
		WHERE
			`user_id` = ?
	";

	$stmt = exec_query($query, $user_id);
	$cnt = $stmt->fields['cnt'];

	$cnt = is_null($cnt) ? 0 : $cnt;

	return $cnt;
}

/**
 * Returns total number of SQL user that belong to a specific user.
 *
 * @param  int $domain_id Domain unique identifier
 * @return int Total number of SQL users for a specific domain
 */
function get_user_running_sqlu_acc_cnt($user_id = null){

	static $cnt = null;
	if(!is_null($cnt)){ return $cnt; }

	$user_id = is_null($user_id) ? $_SESSION['user_id'] : $user_id;

	$query = "
		SELECT DISTINCT
			`t1`.`sqlu_name`
		FROM
			`sql_user` AS `t1`, `sql_database` AS `t2`
		WHERE
			`t2`.`user_id` = ?
		AND
			`t2`.`sqld_id` = `t1`.`sqld_id`
	";

	$stmt = exec_query($query, $user_id);
	$cnt = $stmt->recordCount();

	return $cnt;
}

/**
 * Returns information about number of mail account for a specific domain.
 *
 * @param  int $domain_id	 Domain unique identifier
 * @return array			  An array of values where the first item is the sum of
 *							all other items, and where each other item represents
 *							total number of a specific Mail account type
 */
function get_domain_running_mail_acc_cnt($domain_id){
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			COUNT(`mail_id`) AS `cnt`
		FROM
			`mail_users`
		WHERE
			`mail_type` NOT IN ('normal_catchall', 'subdom_catchall')
		AND
			`domain_id` = ?
	";

	if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES == 0) {
		$query .=
			"
			AND
				`mail_acc` NOT IN ('abuse', 'postmaster', 'webmaster')
		";
	}

	$stmt = exec_query($query, array($domain_id));
	$mail_acc = $stmt->fields['cnt'];

	return array($mail_acc);
}

/**
 * Returns total number of Ftp accounts that belong to a domain.
 *
 * @param  int $domain_id Domain unique identifier
 * @return int Number of Ftp accounts
 */
function get_domain_running_dmn_ftp_acc_cnt($domain_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`domain_name`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
	";

	$stmt = exec_query($query, $domain_id);

	$query = "
		SELECT
			COUNT(*) AS `cnt`
		FROM
			`ftp_users`
		WHERE
			`userid` LIKE ?
	";

	$stmt = exec_query($query, '%' . $cfg->FTP_USERNAME_SEPARATOR .
									  $stmt->fields['domain_name']);

	return $stmt->fields['cnt'];
}

/**
 * Returns total number of Ftp accounts that belong to subdomains of a specific
 * domain.
 *
 * @param  int $domain_id Domain unique identifier
 * @return int Total number of Ftp accounts
 */
function get_domain_running_sub_ftp_acc_cnt($domain_id){
	$query = "SELECT `domain_name` FROM `domain` WHERE `domain_id` = ?";
	$stmt1 = exec_query($query, $domain_id);

	$query = "
		SELECT
			`subdomain_name`
		FROM
			`subdomain`
		WHERE
			`domain_id` = ?
		ORDER BY
			`subdomain_id`
	";
	$stmt2 = exec_query($query, $domain_id);

	$sub_ftp_acc_cnt = 0;

	if ($stmt2->rowCount() != 0) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');
		$ftpSeparator = $cfg->FTP_USERNAME_SEPARATOR;

		while (!$stmt2->EOF) {
			$query = "
				SELECT
					COUNT(*) AS `cnt`
				FROM
					`ftp_users`
				WHERE
					`userid` LIKE ?
			";
			$stmt3 = exec_query($query,
								'%' . $ftpSeparator .
								$stmt2->fields['subdomain_name'] . '.' .
								$stmt1->fields['domain_name']);

			$sub_ftp_acc_cnt += $stmt3->fields['cnt'];
			$stmt2->moveNext();
		}
	}

	return $sub_ftp_acc_cnt;
}

/**
 * Translate mail type.
 *
 * @param  string $mail_type
 * @return string Translated mail type
 */
function user_trans_mail_type($mail_type)
{
	if ($mail_type === MT_NORMAL_MAIL) {
		return tr('Domain mail');
	} else if ($mail_type === MT_NORMAL_FORWARD) {
		return tr('Email forward');
	} else if ($mail_type === MT_SUBDOM_MAIL) {
		return tr('Subdomain mail');
	} else if ($mail_type === MT_SUBDOM_FORWARD) {
		return tr('Subdomain forward');
	} else if ($mail_type === MT_NORMAL_CATCHALL) {
		return tr('Domain mail');
	} else {
		return tr('Unknown type');
	}
}

/**
 * Count SQL user by name.
 *
 * @param string $sqlu_name SQL user name to match against
 * @return int
 */
function count_sql_user_by_name($sqlu_name)
{
	$query = "
		SELECT
			COUNT(*) AS `cnt`
		FROM
			`sql_user`
		WHERE
			`sqlu_name` = ?
	";
	$stmt = exec_query($query, $sqlu_name);

	return $stmt->fields['cnt'];
}

/**
 * Checks if an user has permissions on a specific SQL user.
 *
 * @param  int $db_user_id SQL user unique identifier.
 * @return bool TRUE if user have permission on SQL user, FALSE otherwise.
 */
function check_user_sql_perms($db_user_id){
	if (who_owns_this($db_user_id, 'sqlu_id') != $_SESSION['user_id']) {
		return false;
	}
	return true;
}

/**
 * Checks if an user has permissions on  specific SQL Database.
 *
 * @param  int $db_id Database unique identifier
 * @return bool TRUE if user have permission on SQL user, FALSE otherwise.
 */
function check_db_sql_perms($db_id){
	if (who_owns_this($db_id, 'sqld_id') != $_SESSION['user_id']) {
		return false;
	}
	return true;
}

/**
 * Checks if an user has permissions on a specific Ftp account.
 *
 * @param  int $ftp_acc Ftp account unique identifier
 * @return bool TRUE if user have permission on Ftp account, FALSE otherwise.
 */
function check_ftp_perms($ftp_acc){
	if (who_owns_this($ftp_acc, 'ftp_user') != $_SESSION['user_id']) {
		return false;
	}

	return true;
}

/**
 * Returns translated gender code.
 *
 * @param string $code Gender code to be returned
 * @param bool $nullOnBad Tells whether or not null must be returned on unknow $code
 * @return null|string Translated gender or null in some circonstances.
 */
function get_gender_by_code($code, $nullOnBad = false){
	switch (strtolower($code)) {
		case 'm':
		case 'M':
			return tr('Male');
		case 'f':
		case 'F':
			return tr('Female');
		default:
			return (!$nullOnBad) ? tr('Unknown') : null;
	}
}
