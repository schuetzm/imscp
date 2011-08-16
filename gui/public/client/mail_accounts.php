<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page',$cfg->CLIENT_TEMPLATE_PATH . '/mail_accounts.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('mail_message', 'page');
$tpl->define_dynamic('mail_item', 'page');
$tpl->define_dynamic('mail_auto_respond', 'mail_item');
$tpl->define_dynamic('default_mails_form', 'page');
$tpl->define_dynamic('mails_total', 'page');
$tpl->define_dynamic('no_mails', 'page');
$tpl->define_dynamic('table_list', 'page');

$tpl->assign(
	array(
		'TR_CLIENT_MANAGE_USERS_PAGE_TITLE'	=> tr('i-MSCP - Client/Manage Users'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);

// page functions.

/**
 * Must be documented
 *
 * @param int $mail_id mail id
 * @param string $mail_status mail status
 * @return array
 */
function gen_user_mail_action($mail_id, $mail_status) {

	$cfg = iMSCP_Registry::get('config');

	if ($mail_status === $cfg->ITEM_OK_STATUS) {
		return array(
			tr('Delete'),
			"mail_delete.php?id=$mail_id",
			tr('Edit'),
			"mail_edit.php?id=$mail_id"
		);
	} else {
		return array(tr('N/A'), '#', tr('N/A'), '#');
	}
}

/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl pTemplate instance
 * @param int $mail_id
 * @param string $mail_type
 * @param string $mail_status
 * @param int $mail_auto_respond
 * @return void
 */
function gen_user_mail_auto_respond($mail_id, $mail_type, $mail_status, $mail_auto_respond) {

	$cfg = iMSCP_Registry::get('config');

	if ($mail_status === $cfg->ITEM_OK_STATUS) {
		if ($mail_auto_respond == false) {
			return array(
				'AUTO_RESPOND_DISABLE'			=> tr('Enable'),
				'AUTO_RESPOND_DISABLE_SCRIPT'	=> "mail_autoresponder_enable.php?id=$mail_id",
				'AUTO_RESPOND_EDIT'				=> '',
				'AUTO_RESPOND_EDIT_SCRIPT'		=> '',
				'AUTO_RESPOND_VIS'				=> 'inline'
			);
		} else {
			return array(
				'AUTO_RESPOND_DISABLE'			=> tr('Disable'),
				'AUTO_RESPOND_DISABLE_SCRIPT'	=> "mail_autoresponder_disable.php?id=$mail_id",
				'AUTO_RESPOND_EDIT'				=> tr('Edit'),
				'AUTO_RESPOND_EDIT_SCRIPT'		=> "mail_autoresponder_edit.php?id=$mail_id",
				'AUTO_RESPOND_VIS'				=> 'inline'
			);
		}
	} else {
		return array(
			'AUTO_RESPOND_DISABLE'			=> tr('Please wait for update'),
			'AUTO_RESPOND_DISABLE_SCRIPT'	=> '',
			'AUTO_RESPOND_EDIT'				=> '',
			'AUTO_RESPOND_EDIT_SCRIPT'		=> '',
			'AUTO_RESPOND_VIS'				=> 'inline'
		);
	}
}

/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl reference to pTemplate object
 * @return array number of domain mails adresses
 */
function gen_page_mail_list($tpl) {

	$user = iMSCP_Props_client::getInstanceById($_SESSION['user_id']);
	$domains = $user->domains;

	$dmnMails = array();
	$dmnMailsDetails = array();

	$default = 0;
	$total = 0;

	foreach ( $domains as $domain ) { $dmnMails = $dmnMails + $domain->mails; }

	$hide = !isset($_POST['uaction']) || $_POST['uaction'] == 'hide';

	foreach($dmnMails as $mail){
		if(
			strpos($mail->mail_type, MT_NORMAL_CATCHALL) !== false
			||
			strpos($mail->mail_type, MT_SUBDOM_CATCHALL) !== false
		){ continue; }

		$total++;

		if(in_array($mail->mail_acc, array('abuse', 'postmaster', 'webmaster' ))){
			$default++;
			if ($hide){ continue; }
		}

		$mail_types = explode(',', $mail->mail_type);
		$mail_type = '';

		foreach ($mail_types as $type) {
			$mail_type .= user_trans_mail_type($type);

			if (strpos($type, '_forward') !== false) {
				$mail_type .= ': ' .
					str_replace(
						array("\r\n", "\n", "\r"), ", ",
						$mail->mail_forward
					);
			}

			$mail_type .= '<br />';
		}

		list(
			$mail_delete, $mail_delete_script, $mail_edit, $mail_edit_script
		) = gen_user_mail_action($mail->mail_id, $mail->status);

		$mail_acc = decode_idna($mail->mail_acc);
		$dmn_name = decode_idna(iMSCP_Props_domain::getInstanceById($mail->domain_id)->domain_name);

		$auto = gen_user_mail_auto_respond(
			$mail->mail_id, $mail->mail_type,
			$mail->status, $mail->mail_auto_respond
		);

		$dmnMailsDetails{$mail->mail_type}[] = array(
			'MAIL_ACC'				=> tohtml("$mail_acc@$dmn_name"),
			'MAIL_TYPE'				=> $mail_type,
			'MAIL_STATUS'			=> translate_dmn_status($mail->status),
			'MAIL_DELETE'			=> $mail_delete,
			'MAIL_DELETE_SCRIPT'	=> $mail_delete_script,
			'MAIL_EDIT'				=> $mail_edit,
			'MAIL_EDIT_SCRIPT'		=> $mail_edit_script
		) + $auto;
	}
	arsort($dmnMailsDetails);

	foreach ($dmnMailsDetails as $mailType) {
		foreach ($mailType as $mailStruct) {
			foreach( $mailStruct as $key => $value){
				$tpl->assign(array($key => $value));
			}
			$tpl->parse('MAIL_ITEM', '.mail_item');
		}
	}

	return array($default, $total);
} // end gen_page_dmn_mail_list()

/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl Reference to the pTemplate object
 * @param int $user_id Customer id
 * @return void
 */
function gen_page_lists($tpl) {

	$cfg = iMSCP_Registry::get('config');

	$user = iMSCP_Props_client::getInstanceById($_SESSION['user_id']);

	list($default_mails, $total_mails) = gen_page_mail_list($tpl);

	if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES == 0) {
		$counted_mails = $total_mails;
	} else {
		$counted_mails = $total_mails - $default_mails;
	}

	if ($total_mails > 0) {
		$tpl->assign(array(
			'MAIL_MESSAGE'			=> '',
			'DEFAULT_MAIL_ACCOUNTS'	=> $default_mails,
			'COUNTED_MAIL_ACCOUNTS'	=> $counted_mails,
			'TOTAL_MAIL_ACCOUNTS'	=> $total_mails,
			'ALLOWED_MAIL_ACCOUNTS'	=> ($user->user_mailacc_limit != 0)
										? $user->user_mailacc_limit : tr('unlimited')
		));
	} else {
		if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
			$tpl->assign(array('TABLE_LIST' => ''));
		}

		$tpl->assign(
			array(
				'MAIL_MSG'	=> tr('Mail accounts list is empty!'),
				'MAIL_ITEM'	=> '', 'MAILS_TOTAL' => ''
			)
		);

		$tpl->parse('MAIL_MESSAGE', 'mail_message');
	}

	if ($default_mails > 0) {

		$tpl->assign(
			array(
				'TR_DEFAULT_EMAILS_BUTTON' =>
				(!isset($_POST['uaction']) || $_POST['uaction'] != 'show') ?
					tr('Show default E-Mail addresses') :
					tr('Hide default E-Mail Addresses'),
				'VL_DEFAULT_EMAILS_BUTTON' =>
				(isset($_POST['uaction']) && $_POST['uaction'] == 'show') ?
					'hide' :'show'
			)
		);

	} else {
		$tpl->assign(array('DEFAULT_MAILS_FORM' => ''));
	}

} // end gen_page_lists()

/**
 * Count the number of email addresses created by default
 *
 * Return the number of default mail adresses according
 * the state of 'uaction''. If no 'uaction' is set or if the
 * 'uaction' is set to 'hide', 0 will be returned.
 *
 * Note: 'uaction' = user action -> ($_POST['uaction'])
 *
 * For performances reasons, the query is performed only once
 * and the result is cached.
 *
 * @author Laurent declercq <l.declercq@nuxwin.com>
 * @since r2513
 * @param int Domain name id
 * @return int Number of default mails adresses
 */
function count_default_mails($dmn_id) {

	static $count_default_mails;

	if (!is_int($count_default_mails)) {

		$query = "
			SELECT COUNT(`mail_id`) AS cnt
			FROM
				`mail_users`
			WHERE
				`domain_id` = ?
			AND
				(
				 	`mail_acc` = 'abuse'
				OR
					`mail_acc` = 'postmaster'
				OR
					`mail_acc` = 'webmaster'
				)
		";

		$rs = exec_query($query, $dmn_id);
		$count_default_mails = (int) $rs->fields['cnt'];
	}

	return $count_default_mails;
}

// dynamic page data.

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == 'no') {
	$tpl->assign('NO_MAILS', '');
}

gen_page_lists($tpl, $_SESSION['user_id']);

// static page messages.

gen_client_mainmenu(
	$tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_email_accounts.tpl'
);

gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_email_accounts.tpl');
gen_logged_from($tpl);
check_permissions($tpl);

$tpl->assign(
	array(
		'TR_MANAGE_USERS'			=> tr('Manage users'),
		'TR_MAIL_USERS'				=> tr('Mail users'),
		'TR_MAIL'					=> tr('Mail'),
		'TR_TYPE'					=> tr('Type'),
		'TR_STATUS'					=> tr('Status'),
		'TR_ACTION'					=> tr('Action'),
		'TR_AUTORESPOND'			=> tr('Auto respond'),
		'TR_DMN_MAILS'				=> tr('Domain mails'),
		'TR_SUB_MAILS'				=> tr('Subdomain mails'),
		'TR_TOTAL_MAIL_ACCOUNTS'	=> tr('Mails total'),
		'TR_DEFAULT_MAIL_ACCOUNTS'	=> tr('Default Mails'),
		'TR_COUNTED_MAIL_ACCOUNTS'	=> tr('Counted Mails'),
		'TR_ALLOWED_MAIL_ACCOUNTS'	=> tr('Maximum Mails'),
		'TR_DELETE'					=> tr('Delete'),
		'TR_MESSAGE_DELETE'			=>
								tr('Are you sure you want to delete %s?', true, '%s'),
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();


unsetMessages();
