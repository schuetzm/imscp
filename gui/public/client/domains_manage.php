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
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/domains_manage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('dmn_message', 'page');
$tpl->define_dynamic('dmn_list', 'page');
$tpl->define_dynamic('dmn_item', 'dmn_list');
$tpl->define_dynamic('dmn_status_reload_true','dmn_item');
$tpl->define_dynamic('dmn_status_reload_false','dmn_item');
$tpl->define_dynamic('dmn_add', 'page');
$tpl->define_dynamic('als_message', 'page');
$tpl->define_dynamic('als_list', 'page');
$tpl->define_dynamic('als_item', 'als_list');
$tpl->define_dynamic('als_status_reload_true','als_item');
$tpl->define_dynamic('als_status_reload_false','als_item');
$tpl->define_dynamic('alias_add', 'page');
$tpl->define_dynamic('sub_message', 'page');
$tpl->define_dynamic('sub_list', 'page');
$tpl->define_dynamic('sub_item', 'sub_list');
$tpl->define_dynamic('status_reload_true','sub_item');
$tpl->define_dynamic('status_reload_false','sub_item');
$tpl->define_dynamic('subdomain_add', 'page');
$tpl->define_dynamic('isactive_dns', 'page');
$tpl->define_dynamic('dns_message', 'page');
$tpl->define_dynamic('dns_list', 'page');
$tpl->define_dynamic('dns_item', 'dns_list');

// page functions.

function gen_user_dns_list($tpl, $user_id) {
	$domain_id = get_user_domain_id($user_id);

	$query = "
		SELECT
			`domain_dns`.`domain_dns_id`,
			`domain_dns`.`domain_id`,
			`domain_dns`.`domain_dns`,
			`domain_dns`.`domain_class`,
			`domain_dns`.`domain_type`,
			`domain_dns`.`domain_text`,
			IFNULL(`domain_aliasses`.`alias_name`, `domain`.`domain_name`) AS 'domain_name',
			IFNULL(`domain_aliasses`.`alias_status`, `domain`.`domain_status`) AS 'domain_status',
			`domain_dns`.`protected`
		FROM
			`domain_dns`
			LEFT JOIN `domain_aliasses` USING (`alias_id`, `domain_id`),
			`domain`
		WHERE
			`domain_dns`.`domain_id` = ?
		AND
			`domain`.`domain_id` = `domain_dns`.`domain_id`
		ORDER BY
			`domain_id`,
			`alias_id`,
			`domain_dns`,
			`domain_type`
	";

	$rs = exec_query($query, $domain_id);
	if ($rs->recordCount() == 0) {
		$tpl->assign(array('DNS_MSG' => tr("Manual zone's records list is empty!"), 'DNS_LIST' => ''));
		$tpl->parse('DNS_MESSAGE', 'dns_message');
	} else {
		$counter = 0;

		while (!$rs->EOF) {
			if ($counter % 2 == 0) {
				$tpl->assign('ITEM_CLASS', 'content');
			} else {
				$tpl->assign('ITEM_CLASS', 'content2');
			}

			list($dns_action_delete, $dns_action_script_delete) = gen_user_dns_action(
				'Delete', $rs->fields['domain_dns_id'],
				($rs->fields['protected'] == 'no') ? $rs->fields['domain_status'] : 'PROTECTED'
			);

			list($dns_action_edit, $dns_action_script_edit) = gen_user_dns_action(
				'Edit', $rs->fields['domain_dns_id'],
				($rs->fields['protected'] == 'no') ? $rs->fields['domain_status'] : 'PROTECTED'
			);

			$domain_name = decode_idna($rs->fields['domain_name']);
			$sbd_name = $rs->fields['domain_dns'];
			$sbd_data = $rs->fields['domain_text'];
			$tpl->assign(
				array(
					'DNS_DOMAIN'				=> tohtml($domain_name),
					'DNS_NAME'					=> tohtml($sbd_name),
					'DNS_CLASS'					=> tohtml($rs->fields['domain_class']),
					'DNS_TYPE'					=> tohtml($rs->fields['domain_type']),
					'DNS_DATA'					=> tohtml($sbd_data),
//					'DNS_ACTION_SCRIPT_EDIT'	=> $sub_action,
					'DNS_ACTION_SCRIPT_DELETE'	=> tohtml($dns_action_script_delete),
					'DNS_ACTION_DELETE'			=> tohtml($dns_action_delete),
					'DNS_ACTION_SCRIPT_EDIT'	=> tohtml($dns_action_script_edit),
					'DNS_ACTION_EDIT'			=> tohtml($dns_action_edit),
					'DNS_TYPE_RECORD'			=> tr("%s record", $rs->fields['domain_type'])
				)
			);
			$tpl->parse('DNS_ITEM', '.dns_item');
			$rs->moveNext();
			$counter++;
		}

		$tpl->parse('DNS_LIST', 'dns_list');
		$tpl->assign('DNS_MESSAGE', '');
	}
}

function gen_user_dns_action($action, $dns_id, $status) {

	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(tr($action), 'dns_'.strtolower($action).'.php?edit_id='.$dns_id);
	} elseif($action != 'Edit' && $status == 'PROTECTED') {
		return array(tr('N/A'), 'protected');
	}

	return array(tr('N/A'), '#');
}

function gen_user_sub_action($sub_id, $sub_status) {

	$cfg = iMSCP_Registry::get('config');

	if ($sub_status === $cfg->ITEM_OK_STATUS) {
		return array(tr('Delete'), "subdomain_delete.php?id=$sub_id",true);
	} else {
		return array(tr('N/A'), '#',false);
	}
}

function gen_user_alssub_action($sub_id, $sub_status) {

	$cfg = iMSCP_Registry::get('config');

	if ($sub_status === $cfg->ITEM_OK_STATUS) {
		return array(tr('Delete'), "alssub_delete.php?id=$sub_id",true);
	} else {
		return array(tr('N/A'), '#',false);
	}
}

function gen_user_sub_forward($sub_id, $sub_status, $url_forward, $dmn_type) {

	$cfg = iMSCP_Registry::get('config');

	if ($sub_status === $cfg->ITEM_OK_STATUS) {
		return array(
			$url_forward === 'no' || $url_forward === NULL
			?
				'-'
			:
				$url_forward,
			'subdomain_edit.php?edit_id='.$sub_id.'&amp;dmn_type='.$dmn_type, tr('Edit')
		);
	} else if ($sub_status === $cfg->ITEM_ORDERED_STATUS) {
		return array(
			$url_forward === 'no' || $url_forward === NULL
			?
				'-'
			:
				$url_forward, '#', tr('N/A')
			);
	} else {
		return array(tr('N/A'), '#', tr('N/A'));
	}
}

function gen_action($type, $id, $status) {

	$cfg = iMSCP_Registry::get('config');

	if ($status === $cfg->ITEM_OK_STATUS) {
		return array(tr('Delete'), "${type}_delete.php?id=$id", true);
	} else if ($status === $cfg->ITEM_ORDERED_STATUS) {
		return array(tr('Delete order'), "${type}_order_delete.php?del_id=$id", false);
	} else {
		return array(tr('N/A'), '#',false);
	}
}

function gen_forward($type, $id, $status, $url_forward) {

	if ($url_forward === 'no') {
		if ($status === 'ok') {
			return array("-", "${type}_edit.php?edit_id=" . $id, tr("Edit"));
		} else if ($status === 'ordered') {
			return array("-", "#", tr("N/A"));
		} else {
			return array(tr("N/A"), "#", tr("N/A"));
		}
	} else {
		if ($status === 'ok') {
			return array($url_forward, "${type}_edit.php?edit_id=" . $id, tr("Edit"));
		} else if ($status === 'ordered') {
			return array($url_forward, "#", tr("N/A"));
		} else {
			return array(tr("N/A"), "#", tr("N/A"));
		}
	}
}

function gen_user_alias_list($tpl, $aliasses) {

	if (count($aliasses) == 0) {
		$tpl->assign(array('ALS_MSG' => tr('Alias list is empty!'), 'ALS_LIST' => ''));
		$tpl->parse('ALS_MESSAGE', 'als_message');
	} else {

		foreach($aliasses as $alias) {

			list($action, $action_script, $status_bool) = gen_action('alias', $alias->alias_id, $alias->alias_status);
			list($forward, $edit_link, $edit) = gen_forward('alias', $alias->alias_id, $alias->alias_status, 'no');

			$alias_name = decode_idna($alias->alias_name);

			if($status_bool == false) { // reload
				$tpl->assign('ALS_STATUS_RELOAD_TRUE', '');
				$tpl->assign('ALS_NAME', tohtml($alias_name));
				$tpl->parse('ALS_STATUS_RELOAD_FALSE', 'als_status_reload_false');
			} else {
				$tpl->assign('ALS_STATUS_RELOAD_FALSE', '');
				$tpl->assign('ALS_NAME', tohtml($alias_name));
				$tpl->parse('ALS_STATUS_RELOAD_TRUE', 'als_status_reload_true');
			}

			$tpl->assign(
				array(
					'ALS_NAME'			=> tohtml($alias_name),
					'ALS_STATUS'		=> translate_dmn_status($alias->alias_status),
					'ALS_ALIAS_OF'		=> iMSCP_Props_domain::getInstanceById($alias->domain_id)->domain_name,
					'ALS_EDIT_LINK'		=> $edit_link,
					'ALS_EDIT'			=> $edit,
					'ALS_ACTION'		=> $action,
					'ALS_ACTION_SCRIPT'	=> $action_script
				)
			);
			$tpl->parse('ALS_ITEM', '.als_item');
		}

		$tpl->parse('ALS_LIST', 'als_list');
		$tpl->assign('ALS_MESSAGE', '');
	}
}

function gen_user_sub_list($tpl, $subdomains) {

	if (count($subdomains) == 0) {
		$tpl->assign(array('SUB_MSG' => tr('Subdomain list is empty!'), 'SUB_LIST' => ''));
		$tpl->parse('SUB_MESSAGE', 'sub_message');
	} else {
		foreach ($subdomains as $subdomain) {

			list($action, $action_script, $status_bool) = gen_action('subdomain', $subdomain->subdomain_id, $subdomain->subdomain_status);
			list($forward, $edit_link, $edit) = gen_forward('subdomain', $subdomain->subdomain_id, $subdomain->subdomain_status, $subdomain->subdomain_url_forward);

			$sbd_name = decode_idna($subdomain->subdomain_name);
			$forward = decode_idna($forward);

			if($status_bool == false) { // reload
				$tpl->assign('STATUS_RELOAD_TRUE', '');
				$tpl->assign('SUB_NAME', tohtml($sbd_name));
				$tpl->parse('STATUS_RELOAD_FALSE', 'status_reload_false');
			} else {
				$tpl->assign('STATUS_RELOAD_FALSE', '');
				$tpl->assign('SUB_NAME', tohtml($sbd_name));
				$tpl->parse('STATUS_RELOAD_TRUE', 'status_reload_true');
			}
			$tpl->assign(
				array(
					'SUB_NAME'			=> tohtml($sbd_name),
					'SUB_MOUNT'			=> tohtml($subdomain->subdomain_mount),
					'SUB_FORWARD'		=> $forward,
					'SUB_STATUS'		=> translate_dmn_status($subdomain->subdomain_status),
					'SUB_EDIT_LINK'		=> $edit_link,
					'SUB_EDIT'			=> $edit,
					'SUB_ACTION'		=> $action,
					'SUB_ACTION_SCRIPT'	=> $action_script,
				)
			);
			$tpl->parse('SUB_ITEM', '.sub_item');
		}

		$tpl->parse('SUB_LIST', 'sub_list');
		$tpl->assign('SUB_MESSAGE', '');
	}
}

function gen_user_dmn_list($tpl) {

	$user		= iMSCP_Props_client::getInstanceById($_SESSION['user_id']);
	$domains	= $user->domains;

	$cfg = iMSCP_Registry::get('config');
	$subdomains = array();
	$aliasses = array();

	if (count($user->domains) == 0) {
		$tpl->assign(array(
			'DMN_MSG' => tr('Domain list is empty!'),
			'DMN_LIST' => '',
			'SUB_MSG' => tr('Subdomain list is empty!'),
			'SUB_LIST' => '',
			'ALS_MSG' => tr('Alias list is empty!'),
			'ALS_LIST' => ''
		));
		$tpl->parse('DMN_MESSAGE', 'dmn_message');
	} else {
		foreach ($domains as $domain) {

			$subdomains = $subdomains + $domain->subdomains;
			$aliasses = $aliasses + $domain->aliases;

			list($action, $action_script, $status_bool) = gen_action('domain', $domain->domain_id, $domain->domain_status);
			list($forward, $edit_link, $edit) = gen_forward('domain', $domain->domain_id, $domain->domain_status, $domain->url_forward);

			$dmn_name = decode_idna($domain->domain_name);
			$forward = decode_idna($forward);

			if($status_bool == false) { // reload
				$tpl->assign('DMN_STATUS_RELOAD_TRUE', '');
				$tpl->assign('DMN_NAME', tohtml($dmn_name));
				$tpl->parse('DMN_STATUS_RELOAD_FALSE', 'dmn_status_reload_false');
			} else {
				$tpl->assign('DMN_STATUS_RELOAD_FALSE', '');
				$tpl->assign('DMN_NAME', tohtml($dmn_name));
				$tpl->parse('DMN_STATUS_RELOAD_TRUE', 'dmn_status_reload_true');
			}

			$tpl->assign(
				array(
					'DMN_NAME'			=> tohtml($dmn_name),
					'DMN_MOUNT'			=> tohtml($domain->domain_mount_point),
					'DMN_STATUS'		=> translate_dmn_status($domain->domain_status),
					'DMN_FORWARD'		=> tohtml($forward),
					'DMN_EDIT_LINK'		=> $edit_link,
					'DMN_EDIT'			=> $edit,
					'DMN_ACTION'		=> $action,
					'DMN_ACTION_SCRIPT'	=> $action_script,
					'ALTERNATIVE_URL'	=> "{$cfg->BASE_SERVER_VHOST_PREFIX}$dmn_name.{$cfg->BASE_SERVER_VHOST}"
				)
			);
			$tpl->parse('DMN_ITEM', '.dmn_item');
		}

		$tpl->parse('DMN_LIST', 'dmn_list');
		$tpl->assign('DMN_MESSAGE', '');
		gen_user_sub_list($tpl, $subdomains);
		gen_user_alias_list($tpl, $aliasses);
	}
}


// common page data.

$tpl->assign(
	array(
		'TR_CLIENT_MANAGE_DOMAINS_PAGE_TITLE'	=> tr('i-MSCP - Client/Manage Domains'),
		'THEME_COLOR_PATH'						=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'							=> tr('encoding'),
		'ISP_LOGO'								=> layout_getUserLogo()
	)
);

// dynamic page data.

gen_user_dmn_list($tpl);

/*
gen_user_dns_list($tpl, $_SESSION['user_id']);
*/
gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_manage_domains.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_manage_domains.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_DOMAINS'			=> tr('Domains'),
		'TR_NAME'				=> tr('Name'),
		'TR_MOUNT'				=> tr('Mount point'),
		'TR_FORWARD'			=> tr('Forward'),
		'TR_STATUS'				=> tr('Status'),
		'TR_ACTION'				=> tr('Actions'),
		'TR_DOMAIN_ALIASES'		=> tr('Domain aliases'),
		'TR_ALIAS_OF'			=> tr('Alias for domain'),
		'TR_SUBDOMAINS'			=> tr('Subdomains'),
		'TR_MESSAGE_DELETE'		=> tr('Are you sure you want to delete %s?', true, '%s'),
		'TR_DNS'				=> tr('DNS zone\'s records'),
		'TR_DNS_CLASS'			=> tr('Class'),
		'TR_DNS_TYPE'			=> tr('Type'),
		'TR_DNS_DATA'			=> tr('Record data'),
		'TR_DOMAIN_NAME'		=> tr('Domain'),
		'TR_ALTERNATIVE_URL'	=> tr('Alternative URL to reach your website')
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
	iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
