<?php

/**
 * i-MSCP a internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

 *
 * @copyright   2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id:
 * @link                http://i-mscp.net
 * @author              i-MSCP Team
 * @license             http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */


require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);


$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/mail_autoresponder_enable.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$tpl->assign(
        array(
                'TR_CLIENT_ENABLE_AUTORESPOND_PAGE_TITLE' => tr('i-MSCP - Client/Enable Mail Auto Responder'),
                'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
                'THEME_CHARSET' => tr('encoding'),
                'ISP_LOGO' => layout_getUserLogo()
        )
);


//Get Mail ID - if no Mail ID than exit to mail_accounts.php
if (isset($_GET['id'])) {
        $mailId = $_GET['id'];
} else if (isset($_POST['id'])) {
        $mailId = $_POST['id'];
} else {
        redirectTo('mail_accounts.php');
}

$arsp = new iMSCP_Arsp($mailId);

// If save request
if (isset($_POST['uaction']) && $_POST['uaction'] === 'enable_arsp') {

	if (!$arsp->setMessage(clean_input($_POST['arsp_message'], false))) { 
		$errMsg[] = tr('Please type your mail autorespond message.');
	}

	if (!$arsp->setStartDate($_POST['arsp_start'])) {
		$errMsg[] = tr('Please type a valid Start Date/Time');
	}

        if (!$arsp->setStopDate($_POST['arsp_stop'])) { 
                $errMsg[] = tr('Please type a valid Stop Date/Time');  
        }
	
	if ($arsp->getErrorFlag()) { // if an error 
		foreach ($errMsg as $msg) {
			set_page_message($msg, 'error');	
		}
	} else { // else save into DB and return to mail_accounts.php
		$arsp->saveDB($cfg->ITEM_CHANGE_STATUS);
		send_request(); // Send a request to the daemon for backend process
		set_page_message(tr('Mail account scheduled for update.'), 'success');
                redirectTo('mail_accounts.php');
	}
}	


$tpl->assign(
        array(
		'ARSP_MESSAGE' => tohtml($arsp->getMessage()),
		'ARSP_START' => $arsp->getStartDate(),
		'ARSP_STOP' => $arsp->getStopDate(),
		'ARSP_ID' => $mailId
	)
);

// static page messages.
gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_email_accounts.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_email_accounts.tpl');

gen_logged_from($tpl);
check_permissions($tpl);

$tpl->assign(
        array(
                'TR_ENABLE_MAIL_AUTORESPONDER'  => tr('Enable mail auto responder'),
                'TR_ARSP_MESSAGE' => tr('Your message'),
		'TR_ENABLE' => tr('Save'),
                'TR_CANCEL' => tr('Cancel'),
                'TR_ARSP_TIME' => tr('Timeframe'),
		'TR_ARSP_START' => tr('Start Date/Time'),
                'TR_ARSP_STOP' => tr('Stop Date/Time')
        )
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();

