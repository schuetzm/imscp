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
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/personal_change.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$tpl->assign(
	array(
		'TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE'	=> tr('i-MSCP - Client/Change Personal Data'),
		'THEME_COLOR_PATH'							=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'								=> tr('encoding'),
		'ISP_LOGO'									=> layout_getUserLogo()
	)
);

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_data') {
	update_user_personal_data($_SESSION['user_id']);
}

gen_user_personal_data($tpl, $_SESSION['user_id']);

function gen_user_personal_data($tpl, $user_id) {

	$cfg = iMSCP_Registry::get('config');

	$user = iMSCP_Props_client::getInstanceById($user_id);

	$tpl->assign(
		array(
			'FIRST_NAME'	=> is_null($user->user_fname) ? '' : tohtml($user->user_fname),
			'LAST_NAME'		=> is_null($user->user_lname) ? '' : tohtml($user->user_lname),
			'FIRM'			=> is_null($user->user_firm) ? '' : tohtml($user->user_firm),
			'ZIP'			=> is_null($user->user_zip) ? '' : tohtml($user->user_zip),
			'CITY'			=> is_null($user->user_city) ? '' : tohtml($user->user_city),
			'STATE'			=> is_null($user->user_state) ? '' : tohtml($user->user_state),
			'COUNTRY'		=> is_null($user->user_country) ? '' : tohtml($user->user_country),
			'STREET_1'		=> is_null($user->user_street1) ? '' : tohtml($user->user_street1),
			'STREET_2'		=> is_null($user->user_street2) ? '' : tohtml($user->user_street2),
			'EMAIL'			=> is_null($user->user_email) ? '' : tohtml($user->user_email),
			'PHONE'			=> is_null($user->user_phone) ? '' : tohtml($user->user_phone),
			'FAX'			=> is_null($user->user_fax) ? '' : tohtml($user->user_fax),
			'VL_MALE'		=> (($user->user_gender == 'M') ? $cfg->HTML_SELECTED : ''),
			'VL_FEMALE'		=> (($user->user_gender == 'F') ? $cfg->HTML_SELECTED : ''),
			'VL_UNKNOWN'	=> ((($user->user_gender == 'U') || (is_null($user->user_gender))) ? $cfg->HTML_SELECTED : '')
		)
	);
}

function update_user_personal_data($user_id) {

	$user = iMSCP_Props_client::getInstanceById($user_id);

	$user->user_fname	= clean_input($_POST['fname']);
	$user->user_lname	= clean_input($_POST['lname']);
	$user->user_gender	= $_POST['gender'];
	$user->user_firm	= clean_input($_POST['firm']);
	$user->user_zip		= clean_input($_POST['zip']);
	$user->user_city	= clean_input($_POST['city']);
	$user->user_state	= clean_input($_POST['state']);
	$user->user_country	= clean_input($_POST['country']);
	$user->user_street1	= clean_input($_POST['street1']);
	$user->user_street2	= clean_input($_POST['street2']);
	$user->user_email	= clean_input($_POST['email']);
	$user->user_phone	= clean_input($_POST['phone']);
	$user->user_fax		= clean_input($_POST['fax']);

	$user->save();

	write_log($_SESSION['user_logged'] . ': update personal data', E_USER_NOTICE);
	set_page_message(tr('Personal data updated successfully!'), 'success');
}

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_general_information.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_general_information.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_CHANGE_PERSONAL_DATA'	=> tr('Change personal data'),
		'TR_PERSONAL_DATA'			=> tr('Personal data'),
		'TR_FIRST_NAME'				=> tr('First name'),
		'TR_LAST_NAME'				=> tr('Last name'),
		'TR_COMPANY'				=> tr('Company'),
		'TR_ZIP_POSTAL_CODE'		=> tr('Zip/Postal code'),
		'TR_CITY'					=> tr('City'),
		'TR_STATE'					=> tr('State/Province'),
		'TR_COUNTRY'				=> tr('Country'),
		'TR_STREET_1'				=> tr('Street 1'),
		'TR_STREET_2'				=> tr('Street 2'),
		'TR_EMAIL'					=> tr('Email'),
		'TR_PHONE'					=> tr('Phone'),
		'TR_FAX'					=> tr('Fax'),
		'TR_GENDER'					=> tr('Gender'),
		'TR_MALE'					=> tr('Male'),
		'TR_FEMALE'					=> tr('Female'),
		'TR_UNKNOWN'				=> tr('Unknown'),
		'TR_UPDATE_DATA'			=> tr('Update data')
	)
);

generatePageMessage($tpl);
$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
