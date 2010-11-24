<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-msCP | http://i-mscp.net
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

require '../include/imscp-lib.php';

$cfg = iMSCP_Registry::get('Config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->PURCHASE_TEMPLATE_PATH . '/addon.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('purchase_header', 'page');
$tpl->define_dynamic('purchase_footer', 'page');
$tpl->define_dynamic('op_tld_list', 'page');

/**
 * functions start
 */

function addon_domain($dmn_name) {

	if (!validates_dname($dmn_name)) {
		global $validation_err_msg;
		set_page_message(tr($validation_err_msg));
		return;
	}

	// Should be performed after domain name validation now
	$dmn_name = encode_idna(strtolower($dmn_name));

	if (imscp_domain_exists($dmn_name, 0) || $dmn_name == iMSCP_Registry::get('Config')->BASE_SERVER_VHOST) {
		set_page_message(tr('Domain already exists on the system!'));
		return;
	}

    $_SESSION['new_kk'] = $_POST['new_kk'];
	$_SESSION['domainname'] = $dmn_name;
    if($_POST['new_kk'] == "kk") {
	    user_goto('address.php');
        } else if($_POST['new_kk'] == "new") {
	        user_goto('checkwhois.php');
        } else if ($_POST['new_kk'] == "hosting_only") {
            user_goto('address.php');
    }
}

function is_plan_available(&$sql, $plan_id, $user_id) {

	$cfg = iMSCP_Registry::get('Config');

	if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`id` = ?
			";

		$rs = exec_query($sql, $query, $plan_id);
	} else {
		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`reseller_id` = ?
			AND
				`id` = ?
		";

		$rs = exec_query($sql, $query, array($user_id, $plan_id));
	}

	return $rs->recordCount() > 0 && $rs->fields['status'] != 0;
}

/**
 * functions end
 */

/**
 * static page messages.
 */

if(isset($_SESSION['already_registered'])) {
    set_page_message(tr('You choose a already registered Domain'));
    unset($_SESSION['already_registered']);
}

if (isset($_SESSION['user_id'])) {
	$user_id = $_SESSION['user_id'];

	if (isset($_SESSION['plan_id'])) {
		$plan_id = $_SESSION['plan_id'];
	} else if (isset($_GET['id'])) {
		$plan_id = $_GET['id'];
		if (is_plan_available($sql, $plan_id, $user_id)) {
			$_SESSION['plan_id'] = $plan_id;
		} else {
			throw new iMSCP_Exception_Production(
				tr('This hosting plan is not available for purchase')
			);
		}
	} else {
		throw new iMSCP_Exception_Production(
			tr('You do not have permission to access this interface!')
		);
	}
} else {
	throw new iMSCP_Exception_Production(
		tr('You do not have permission to access this interface!')
	);
}

if (isset($_SESSION['domainname'])&& $_SESSION['new_kk'] == 'new') {
    $_SESSION['new_kk'] = $_POST['new_kk'];
	user_goto('checkwhois.php');
}

if (isset($_SESSION['domainname'])&& $_SESSION['new_kk'] == 'kk') {
    $_SESSION['new_kk'] = $_POST['new_kk'];
	user_goto('address.php');
}

if (isset($_SESSION['domainname'])&& $_SESSION['new_kk'] == 'hosting_only') {
    $_SESSION['new_kk'] = $_POST['new_kk'];
	user_goto('address.php');
}

if (isset($_POST['domainname']) && $_POST['domainname'] != '') {
	if (isset($_POST['tld']) && $_POST['tld'] != 'select') {
        $_SESSION['new_kk'] = $_POST['new_kk'];
	    addon_domain($_POST['domainname']."".$_POST['tld']);
    } else {
        set_page_message(tr('You have to select a TLD !'));
    }
} else {
    if (isset($_POST['tld']) && $_POST['tld'] != 'select') {
            set_page_message(tr('You can not set an order without a domainname !'));
    }
}

gen_purchase_haf($tpl, $sql, $user_id);
gen_page_message($tpl);
$tld = $_SESSION['tld'];
$tpl->assign(
	array(
		'DOMAIN_ADDON'		=> tr('Add On A Domain'),
		'TR_DOMAIN_NAME'	=> tr('Domain name'),
		'TR_CONTINUE'		=> tr('Continue'),
		'TR_EXAMPLE'		=> tr('(e.g. domain-of-your-choice ) and select a TLD'),
		'THEME_CHARSET'		=> tr('encoding'),
		'THEME_CHARSET'		=> tr('encoding'),
        'TR_RADIO_NEW_KK'   => 'New order,<br>Transfer<br>or Hosting only',
        'VL_NEW'		    => 'New',
        'VL_KK'		        => 'Transfer',
        'ONLY_HOSTING'	    => 'Hosting only',
	)
);

gen_tld_list($tpl, $sql, $plan_id, $user_id);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
