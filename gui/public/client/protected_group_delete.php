<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
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
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2011 by i-MSCP | http://i-mscp.net
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('protected_areas')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$dmn_id = get_user_domain_id($_SESSION['user_id']);


if (isset($_GET['gname']) && $_GET['gname'] !== '' && is_numeric($_GET['gname'])) {
	$group_id = $_GET['gname'];
} else {
	redirectTo('protected_areas.php');
}

$change_status = $cfg->ITEM_DELETE_STATUS;
$awstats_auth = $cfg->AWSTATS_GROUP_AUTH;

$query = "
	UPDATE
		`htaccess_groups`
	SET
		`status` = ?
	WHERE
		`id` = ?
	AND
		`dmn_id` = ?
	AND
		`ugroup` != ?
";
$rs = exec_query($query, array($change_status, $group_id, $dmn_id, $awstats_auth));

$query = "SELECT *  FROM `htaccess` WHERE `dmn_id` = ?";
$rs = exec_query($query, $dmn_id);

while (!$rs->EOF) {
	$ht_id = $rs->fields['id'];
	$grp_id = $rs->fields['group_id'];

	$grp_id_splited = explode(',', $grp_id);

	$key = array_search($group_id,$grp_id_splited);
	if ($key !== false) {
		unset($grp_id_splited[$key]);
		if (count($grp_id_splited) == 0) {
			$status = $cfg->ITEM_DELETE_STATUS;
		} else {
			$grp_id = implode(",", $grp_id_splited);
			$status = $cfg->ITEM_CHANGE_STATUS;
		}
		$update_query = "
			UPDATE
				`htaccess`
			SET
				`group_id` = ?, `status` = ?
			WHERE
				`id` = ?
		";
		$rs_update = exec_query($update_query, array($grp_id, $status, $ht_id));
	}

	$rs->moveNext();
}

set_page_message(tr('Group scheduled for deletion.'), 'success');

send_request();

write_log($_SESSION['user_logged'].": deletes group ID (protected areas): $group_id", E_USER_NOTICE);
redirectTo('protected_user_manage.php');
