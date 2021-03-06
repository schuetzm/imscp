<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-msCP | http://i-mscp.net
 * @version     SVN: $Id: Common.php 4972 2011-07-19 06:00:56Z nuxwin $
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
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
 * Helper function to generates domain details.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $domain_id Domain unique identifier
 * @return
 */
function gen_domain_details($tpl, $domain_id)
{
    $tpl->assign('USER_DETAILS', '');

    if (isset($_SESSION['details']) && $_SESSION['details'] == 'hide') {
        $tpl->assign(array(
                          'TR_VIEW_DETAILS' => tr('View aliases'),
                          'SHOW_DETAILS' => "show"));

        return;
    } else if (isset($_SESSION['details']) && $_SESSION['details'] === 'show') {
        $tpl->assign(array(
                          'TR_VIEW_DETAILS' => tr('hide aliases'),
                          'SHOW_DETAILS' => "hide"));

        $alias_query = '
			SELECT
				`alias_id`, `alias_name`
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = ?
			ORDER BY
				`alias_id` DESC
		';
        $alias_rs = exec_query($alias_query, $domain_id);

        if ($alias_rs->recordCount() == 0) {
            $tpl->assign('USER_DETAILS', '');
        } else {
            while (!$alias_rs->EOF) {
                $alias_name = $alias_rs->fields['alias_name'];

                $tpl->assign('ALIAS_DOMAIN', tohtml(decode_idna($alias_name)));
                $tpl->parse('USER_DETAILS', '.user_details');

                $alias_rs->moveNext();
            }
        }
    } else {
        $tpl->assign(array(
                          'TR_VIEW_DETAILS' => tr('view aliases'),
                          'SHOW_DETAILS' => 'show'));

        return;
    }
}

/**
 * Helper function to generate logged from template part.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_logged_from($tpl)
{
    if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
        $tpl->assign(array(
                          'YOU_ARE_LOGGED_AS' => tr(
                              '%1$s you are now logged as %2$s',
                              $_SESSION['logged_from'],
                              decode_idna($_SESSION['user_logged'])
                          ),
                          'TR_GO_BACK' => tr('Go back')));

        $tpl->parse('LOGGED_FROM', '.logged_from');
    } else {
        $tpl->assign('LOGGED_FROM', '');
    }
}

/**
 * Helper function to generates an html list of available languages.
 *
 * This method generate a HTML list of available languages. The language used by the
 * user is pre-selected. If no language is found, a specific message is shown.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  $user_def_language
 * @return void
 */
function gen_def_language($tpl, $user_def_language)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlSelected = $cfg->HTML_SELECTED;
	$availableLanguages = i18n_getAvailableLanguages();

	if (!empty($availableLanguages)) {
		foreach ($availableLanguages as $language) {
			$tpl->assign(array(
							  'LANG_VALUE' => $language['locale'],
							  'LANG_SELECTED' => ($language['locale'] == $user_def_language)
								  ? $htmlSelected : '',
							  'LANG_NAME' => tohtml($language['language'])));

			$tpl->parse('DEF_LANGUAGE', '.def_language');
		}
	} else {
		$tpl->assign('LANGUAGES_AVAILABLE', '');
		set_page_message(tr('No languages found.'), 'warning');
	}
}

/**
 * Helper function to generate HTML list of months and years
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param  $user_month
 * @param  $user_year
 * @return void
 */
function gen_select_lists($tpl, $user_month, $user_year)
{
    global $crnt_month, $crnt_year;

     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if (!$user_month == '' || !$user_year == '') {
        $crnt_month = $user_month;
        $crnt_year = $user_year;
    } else {
        $crnt_month = date('m');
        $crnt_year = date('Y');
    }

    for ($i = 1; $i <= 12; $i++) {
        $selected = ($i == $crnt_month) ? $cfg->HTML_SELECTED : '';
        $tpl->assign(array('OPTION_SELECTED' => $selected, 'MONTH_VALUE' => $i));
        $tpl->parse('MONTH_LIST', '.month_list');
    }

    for ($i = $crnt_year - 1; $i <= $crnt_year + 1; $i++) {
        $selected = ($i == $crnt_year) ? $cfg->HTML_SELECTED : '';
        $tpl->assign(array('OPTION_SELECTED' => $selected, 'YEAR_VALUE' => $i));
        $tpl->parse('YEAR_LIST', '.year_list');
    }
}

/**
 * Helper function to generates header and footer for order panel pages.
 *
 * @param iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param int $userId User unique identifier
 * @param bool $encode Tell whether or not htmlentities() must applied on template
 * @return void
 */
function gen_purchase_haf($tpl, $userId, $encode = false)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if (isset($_SESSION['user_theme'])) {
        $theme = $_SESSION['user_theme'];
    } else {
        $theme = $cfg->USER_INITIAL_THEME;
    }

    $tpl->assign('THEME_COLOR_PATH', "../themes/$theme");

    $query = "SELECT `header`, `footer` FROM `orders_settings` WHERE `user_id` = ?";
    $stmt = exec_query($query, $userId);

    if ($stmt->recordCount() == 0) {
        $header = <<<RIC
<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ORDER_PANEL_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
    </head>
    <body style="background-image:none;">
        <div class="body" align="center" style="margin:20px 0 0 0;">
RIC;

        $footer = <<<RIC
        </div>
    </body>
</html>
RIC;
    } else {
        $header = $stmt->fields['header'];
        $footer = $stmt->fields['footer'];
        $header = str_replace('\\', '', $header);
        $footer = str_replace('\\', '', $footer);
    }

    if ($encode) {
        $header = htmlentities($header, ENT_COMPAT, 'UTF-8');
        $footer = htmlentities($footer, ENT_COMPAT, 'UTF-8');
    }

    $tpl->assign(array(
                      'PURCHASE_HEADER' => $header,
                      'PURCHASE_FOOTER' => $footer));
}
