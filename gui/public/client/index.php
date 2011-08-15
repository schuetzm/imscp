<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 2001-2006 by moleSoftware GmbH
 * @copyright 2006-2010 by ispCP | http://isp-control.net
 * @copyright 2010-2011 by i-MSCP | http://i-mscp.net
 * @version	 SVN: $Id$
 * @link		http://i-mscp.net
 * @author	ispCP Team
 * @author	i-MSCP Team
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

// Include core libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);


/************************************************************************************
 * Script functions
 */

/**
 *
 * @param$num
 * @param$limit
 * @return string|Translated
 */
function gen_num_limit_msg($num, $limit){
	if ($limit == -1) {
		return tr('disabled');
	}
	if ($limit == 0) {
		return $num . '&nbsp;/&nbsp;' . tr('unlimited');
	}

	return $num . '&nbsp;/&nbsp;' . $limit;
}

/**
 * @paramiMSCP_pTemplate $tpl
 * @return void
 */
function gen_system_message($tpl){
	$user_id = $_SESSION['user_id'];

	$query = "
		SELECT
			COUNT(`ticket_id`) AS cnum
		FROM
			`tickets`
		WHERE
			(`ticket_to` = ? OR `ticket_from` = ?)
		AND
			`ticket_status` IN ('2')
		AND
			`ticket_reply` = 0
	";
	$stmt = exec_query($query, array($user_id, $user_id));

	if ($stmt->fields('cnum') == 0) {
		$tpl->assign(array('MSG_ENTRY'	=> ''));
	} else {
		$tpl->assign(array(
			'TR_NEW_MSGS'	=> tr('You have <b>%d</b> new answer to your support questions',
								$stmt->fields('cnum')),
			'TR_VIEW'		=> tr('View')
		));

		$tpl->parse('MSG_ENTRY', 'msg_entry');
	}
}

/**
 * @paramiMSCP_pTemplate $tpl
 * @param$usage
 * @param$max_usage
 * @param$bars_max
 * @return void
 */
function gen_traff_usage($tpl, $usage, $max_usage, $bars_max){
	list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);
	if ($max_usage != 0) {
		$traffic_usage_data = tr('%1$d%% [%2$s of %3$s]', $percent, sizeit($usage),
								 sizeit($max_usage));
	} else {
		$traffic_usage_data = tr('%1$d%% [%2$s of unlimited]', $percent, sizeit($usage));
	}

	$tpl->assign(array(
					'TRAFFIC_USAGE_DATA'	=> $traffic_usage_data,
					'TRAFFIC_BARS'			=> $bars,
					'TRAFFIC_PERCENT'		=> $percent > 100 ? 100 : $percent
	));

	if ($max_usage != 0 && $usage > $max_usage) {
		$tpl->assign('TR_TRAFFIC_WARNING', tr('You are exceeding your traffic limit!'));
	} else {
		$tpl->assign('TRAFF_WARN', '');
	}
}

/**
 * @paramiMSCP_pTemplate $tpl
 * @param$usage
 * @param$max_usage
 * @param$bars_max
 * @return void
 */
function gen_disk_usage($tpl, $usage, $max_usage, $bars_max){
	list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);

	if ($max_usage != 0) {
		$traffic_usage_data = tr('%1$s%% [%2$s of %3$s]', $percent, sizeit($usage),
								 sizeit($max_usage));
	} else {
		$traffic_usage_data = tr('%1$s%% [%2$s of unlimited]', $percent,
								 sizeit($usage));
	}

	$tpl->assign(array(
					'DISK_USAGE_DATA'	=> $traffic_usage_data,
					'DISK_BARS'			=> $bars,
					'DISK_PERCENT'		=> $percent > 100 ? 100 : $percent
	));

	if ($max_usage != 0 && $usage > $max_usage) {
		$tpl->assign('TR_DISK_WARNING', tr('You are exceeding your disk limit!'));
	} else {
		$tpl->assign('DISK_WARN', '');
	}
}

/**
 * @paramiMSCP_pTemplate $tpl Template engine
 * @param$dmn_sqld_limit
 * @param$dmn_sqlu_limit
 * @param$dmn_php
 * @param$dmn_cgi
 * @param$backup
 * @param$dns
 * @param$dmn_subd_limit
 * @param$als_cnt
 * @param$dmn_mailacc_limit
 * @param$dmn_software_allowed
 * @return void
 */
function check_user_permissions($tpl){

	$user= iMSCP_Props_client::getInstanceById($_SESSION['user_id']);

	// check if mail accouts available are available for this user
	if ($user->user_mailacc_limit == -1) {
		$_SESSION['email_support'] = "no";
		$tpl->assign('T_MAILS_SUPPORT', '');
	} else {
		$tpl->parse('T_MAILS_SUPPORT', '.t_mails_support');
	}

	// check if alias are available for this user
	if ($user->user_alias_limit == -1) {
		$_SESSION['alias_support'] = "no";
		$tpl->assign('T_ALIAS_SUPPORT', '');
	} else {
		$tpl->parse('T_ALIAS_SUPPORT', '.t_alias_support');
	}

	// check if subdomains are available for this user
	if ($user->user_subd_limit == -1) {
		$_SESSION['subdomain_support'] = "no";
		$tpl->assign('T_SDM_SUPPORT', '');
	} else {
		$tpl->parse('T_SDM_SUPPORT', '.t_sdm_support');
	}

	// check if SQL Support is available for this user
	if ($user->user_sqld_limit == -1 || $user->user_sqlu_limit == -1) {
		$_SESSION['sql_support'] = "no";
		$tpl->assign(array(
						'SQL_SUPPORT'		=> '',
						'T_SQL1_SUPPORT'	=> '',
						'T_SQL2_SUPPORT'	=> ''
		));
	} else {
		$tpl->parse('T_SQL1_SUPPORT', '.t_sql1_support');
		$tpl->parse('T_SQL2_SUPPORT', '.t_sql2_support');
	}

	// check if PHP Support is available for this user
	if ($user->user_php == 'no') {
		$tpl->assign('T_PHP_SUPPORT', '');
	} else {
		$tpl->assign('PHP_SUPPORT', tr('yes'));
		$tpl->parse('T_PHP_SUPPORT', '.t_php_support');
	}

	// check if CGI Support is available for this user
	if ($user->user_cgi == 'no') {
		$tpl->assign('T_CGI_SUPPORT', '');
	} else {
		$tpl->assign('CGI_SUPPORT', tr('yes'));
		$tpl->parse('T_CGI_SUPPORT', '.t_cgi_support');
	}
	// check if SSH Support is available for this user
	if ($user->user_ssh == 'no') {
		$tpl->assign('T_SSH_SUPPORT', '');
	} else {
		$tpl->assign('SSH_SUPPORT', tr('yes'));
		$tpl->parse('T_SSH_SUPPORT', '.t_ssh_support');
	}

	// check if CRON Support is available for this user
	if ($user->user_cron == 'no') {
		$tpl->assign('T_CRON_SUPPORT', '');
	} else {
		$tpl->assign('CRON_SUPPORT', tr('yes'));
		$tpl->parse('T_CRON_SUPPORT', '.t_cron_support');
	}

	// check if SSL Support is available for this user
	if ($user->user_ssl == 'no') {
		$tpl->assign('T_SSL_SUPPORT', '');
	} else {
		$tpl->assign('SSL_SUPPORT', tr('yes'));
		$tpl->parse('T_SSL_SUPPORT', '.t_ssl_support');
	}

	// check if apps installer is available for this user
	if ($user->user_software_allowed == 'no') {
		$tpl->assign('T_SOFTWARE_SUPPORT', '');
	} else {
		$tpl->assign('SOFTWARE_SUPPORT', tr('yes'));
		$tpl->parse('T_SOFTWARE_SUPPORT', '.t_software_support');
	}

	// Check if Backup support is available for this user
	switch ($user->user_backups) {
		case "full":
			$tpl->assign('BACKUP_SUPPORT', tr('Full'));
			break;
		case "sql":
			$tpl->assign('BACKUP_SUPPORT', tr('SQL'));
			break;
		case "domain":
			$tpl->assign('BACKUP_SUPPORT', tr('Domain'));
			break;
		default:
			$tpl->assign('T_BACKUP_SUPPORT', '');
	}
	if ($tpl->is_namespace('BACKUP_SUPPORT')) {
		$tpl->parse('T_BACKUP_SUPPORT', '.t_backup_support');
	}

	// Check if Manual DNS support is available for this user
	if ($user->user_dns == 'no') {
		$tpl->assign('T_DNS_SUPPORT', '');
	} else {
		$tpl->assign('DNS_SUPPORT',tr('yes'));
		$tpl->parse('T_DNS_SUPPORT', '.t_dns_support');
	}
}

/**
 * Calculate the usage traffic/ return array (persent/value)
 *
 * @paramint $domain_id Domain unique identifier
 * @return array An where that contain traffic information
 */
function make_traff_usage() {

	$user_id = $_SESSION['user_id'];
	$user = iMSCP_Props_client::getInstanceById($user_id);

	$fdofmnth = mktime(0, 0, 0, date('m'), 1, date('Y'));
	$ldofmnth = mktime(1, 0, 0, date('m') + 1, 0, date('Y'));

	$ids = get_user_domains_id();
	if($ids == array()){ return array("0%", 0); }

	$query = "
		SELECT
			IFNULL(SUM(`dtraff_web`) + SUM(`dtraff_ftp`) + SUM(`dtraff_mail`) +
			SUM(`dtraff_pop`), 0) AS traffic
		FROM
			`domain_traffic`
		WHERE
			`domain_id` IN (".implode(', ', $ids).")
		AND
			`dtraff_time` > ?
		AND
			`dtraff_time` < ?
	";
	$stmt = exec_query($query, array($fdofmnth, $ldofmnth));

	$traffic = ($stmt->fields['traffic'] / 1024) / 1024;

	if ($user->user_traffic_limit == 0) {
		$percent = 0;
	} else {
		$percent = ($traffic / $user->user_traffic_limit) * 100;
		$percent = sprintf("%.2f", $percent);
	}

	return array($percent, $traffic);
}

/**
 * @paramiMSCP_pTemplate $tpl Template engine
 * @param$user_id User unique identifier
 * @return void
 */
function gen_user_messages_label($tpl, &$user_id){
	$query = "
		SELECT
			COUNT(`ticket_id`) AS cnum
		FROM
			`tickets`
		WHERE
			`ticket_from` = ?
		AND
			`ticket_status` = '2'
	";

	$stmt = exec_query($query, $user_id);
	$num_question = $stmt->fields('cnum');

	if ($num_question == 0) {
		$tpl->assign(array(
			'TR_NO_NEW_MESSAGES'	=> tr('You have no new support questions!'),
			'MSG_ENTRY'				=> ''
		));
	} else {
		$tpl->assign(array(
			'NO_MESSAGES'	=> '',
			'TR_NEW_MSGS'	=> tr('You have <b>%d</b> new support questions',
								$num_question),
			'TR_VIEW'		=> tr('View')
		));

		$tpl->parse('MSG_ENTRY', '.msg_entry');
	}
}

/**
 * @param$dbtime
 * @return array
 */
function gen_remain_time($dbtime){

	// needed for calculation
	$mi = 60;
	$h = $mi * $mi;
	$d = $h * 24;
	$mo = $d * 30;
	$y = $d * 365;

	// calculation of: years, month, days, hours, minutes, seconds
	$difftime = $dbtime - time();
	$years = floor($difftime / $y);
	$difftime = $difftime % $y;
	$month = floor($difftime / $mo);
	$difftime = $difftime % $mo;
	$days = floor($difftime / $d);
	$difftime = $difftime % $d;
	$hours = floor($difftime / $h);
	$difftime = $difftime % $h;
	$minutes = floor($difftime / $mi);
	$difftime = $difftime % $mi;
	$seconds = $difftime;

	// put into array and return
	return array($years, $month, $days, $hours, $minutes, $seconds);
}

/************************************************************************************
 * Main script
 */

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login(__FILE__, $cfg->PREVENT_EXTERNAL_LOGIN_CLIENT);

$tpl = new iMSCP_pTemplate();

$tpl->define_dynamic(array(
			'page'						=> $cfg->CLIENT_TEMPLATE_PATH . '/index.tpl',
			'def_language'				=> 'page',
			'def_layout'				=> 'page',
			'no_messages'				=> 'page',
			'msg_entry'					=> 'page',
			'sql_support'				=> 'page',
			't_sql1_support'			=> 'page',
			't_sql2_support'			=> 'page',
			't_php_support'				=> 'page',
			't_cgi_support'				=> 'page',
			't_cron_support'			=> 'page',
			't_ssh_support'				=> 'page',
			't_ssl_support'				=> 'page',
			't_dns_support'				=> 'page',
			't_backup_support'			=> 'page',
			't_dmn_support'				=> 'page',
			't_sdm_support'				=> 'page',
			't_alias_support'			=> 'page',
			't_mails_support'			=> 'page',
			'logged_from'				=> 'page',
			'traff_warn'				=> 'page',
			'disk_warn'					=> 'page',
			'dmn_mngmnt'				=> 'page',
			't_software_support'		=> 'page'
));

$theme_color = $cfg->USER_INITIAL_THEME;

$user= iMSCP_Props_client::getInstanceById($_SESSION['user_id']);

if (isset($_POST['uaction']) && $_POST['uaction'] === 'save_layout') {
	$user_layout = $_POST['def_layout'];

	$user->layout = $user_layout;
	$user->save();

	$theme_color = $user_layout;
}

$dmn_cnt		= count(get_user_domains_id());
$sub_cnt		= get_user_running_sub_cnt();
$als_cnt		= get_user_running_als_cnt();
$mail_acc_cnt	= get_user_running_mail_acc_cnt();
$ftp_acc_cnt	= get_user_running_ftp_acc_cnt();
$sqld_acc_cnt	= get_user_running_sqld_acc_cnt();
$sqlu_acc_cnt	= get_user_running_sqlu_acc_cnt();

$dtraff_pr = 0;
$user_traff_usage = 0;
$user_traffic_limit = $user->user_traffic_limit * 1024 * 1024;

list($dtraff_pr, $user_traff_usage) = make_traff_usage();

$user_disk_limit = $user->user_disk_limit * 1024 * 1024;

gen_traff_usage($tpl, $user_traff_usage * 1024 * 1024, $user_traffic_limit, 400);
gen_disk_usage($tpl, $user->user_disk_usage, $user_disk_limit, 400);
gen_user_messages_label($tpl, $_SESSION['user_id']);

check_user_permissions($tpl);

$account_name = decode_idna($_SESSION['user_logged']);

$tpl->assign(array(
	'ACCOUNT_NAME'		=> tohtml($account_name),
	'MYSQL_SUPPORT'		=> ($user->user_sqld_limit != -1 && $user->user_sqlu_limit != -1)
							? tr('yes') : tr('no'),
	'DOMAINS'			=> gen_num_limit_msg($dmn_cnt, $user->user_domain_limit),
	'SUBDOMAINS'		=> gen_num_limit_msg($sub_cnt, $user->user_subd_limit),
	'DOMAIN_ALIASES'	=> gen_num_limit_msg($als_cnt, $user->user_alias_limit),
	'MAIL_ACCOUNTS'		=> gen_num_limit_msg($mail_acc_cnt, $user->user_mailacc_limit),
	'FTP_ACCOUNTS'		=> gen_num_limit_msg($ftp_acc_cnt, $user->user_ftpacc_limit),
	'SQL_DATABASES'		=> gen_num_limit_msg($sqld_acc_cnt, $user->user_sqld_limit),
	'SQL_USERS'			=> gen_num_limit_msg($sqlu_acc_cnt, $user->user_sqlu_limit)
));


gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_general_information.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_general_information.tpl');
gen_logged_from($tpl);
/*
get_client_software_permission($tpl, $_SESSION['user_id']);
*/
/*
gen_system_message($tpl);
*/
/*
check_permissions($tpl);
*/

$tpl->assign(array(
	'TR_CLIENT_MAIN_INDEX_PAGE_TITLE'	=> tr('i-MSCP - Client/Main Index'),
	'THEME_COLOR_PATH'					=> "../themes/$theme_color",
	'THEME_CHARSET'						=> tr('encoding'),
	'ISP_LOGO'							=> layout_getUserLogo(),
	'TR_GENERAL_INFORMATION'			=> tr('General information'),
	'TR_ACCOUNT_NAME'					=> tr('Account name'),
	'TR_PHP_SUPPORT'					=> tr('PHP support'),
	'TR_CGI_SUPPORT'					=> tr('CGI support'),
	'TR_DNS_SUPPORT'					=> tr('Manual DNS support'),
	'TR_BACKUP_SUPPORT'					=> tr('Backup support'),
	'TR_MYSQL_SUPPORT'					=> tr('SQL support'),
	'TR_SSH_SUPPORT'					=> tr('SSH support'),
	'TR_CRON_SUPPORT'					=> tr('SSL support'),
	'TR_SSL_SUPPORT'					=> tr('Cron support'),
	'TR_DOMAINS'						=> tr('Domains'),
	'TR_SUBDOMAINS'						=> tr('Subdomains'),
	'TR_DOMAIN_ALIASES'					=> tr('Domain aliases'),
	'TR_MAIL_ACCOUNTS'					=> tr('Mail accounts'),
	'TR_FTP_ACCOUNTS'					=> tr('FTP accounts'),
	'TR_SQL_DATABASES'					=> tr('SQL databases'),
	'TR_SQL_USERS'						=> tr('SQL users'),
	'TR_MESSAGES'						=> tr('Support system'),
	'TR_LANGUAGE'						=> tr('Language'),
	'TR_CHOOSE_DEFAULT_LANGUAGE'		=> tr('Choose default language'),
	'TR_SAVE'							=> tr('Save'),
	'TR_LAYOUT'							=> tr('Layout'),
	'TR_CHOOSE_DEFAULT_LAYOUT'			=> tr('Choose default layout'),
	'TR_TRAFFIC_USAGE'					=> tr('Traffic usage'),
	'TR_SOFTWARE_SUPPORT'				=> tr('Software installer'),
	'TR_DISK_USAGE'						=> tr('Disk usage')
));

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
	iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
