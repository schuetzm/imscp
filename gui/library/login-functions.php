<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright	 2001-2006 by moleSoftware GmbH
 * @copyright	 2006-2010 by ispCP | http://isp-control.net
 * @copyright	 2010-2011 by i-MSCP | http://i-mscp.net
 * @version	 SVN: $Id$
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
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
 * Should be documented.
 *
 * @return void
 */
function do_session_timeout(){
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$ttl = time() - $cfg->SESSION_TIMEOUT * 60;

	$query = "DELETE FROM `login` WHERE `lastaccess` < ?";

	exec_query($query, $ttl);

	if (!session_exists(session_id())) {
		unset($_SESSION['user_logged']);
		unset_user_login_data();
	}
}

/**
 * Checks if an session already exists and the IP address is matching.
 *
 * @param string $sessionId User session id from cookie
 * @return bool TRUE if session is valid
 */
function session_exists($sessionId){
	$ip = getipaddr();

	$query = "
		SELECT
			`session_id`, `ipaddr`
		FROM
			`login`
		WHERE
			`session_id` = ?
		AND
			`ipaddr` = ?
	 ";
	$stmt = exec_query($query, array($sessionId, $ip));

	return (bool) $stmt->recordCount();
}

/**
 * Returns the user's Ip address
 *
 * @return string User's Ip address
 * @todo adding proxy detection
 */
function getipaddr(){
	return $_SERVER['REMOTE_ADDR'];
}


/**
 * Checks if an user account name exists.
 *
 * @param	string $userName User account name
 * @return bool TRUE if the user account name exists, FALSE otherwise
 */
function username_exists($userName){

	$userName = encode_idna($userName);
	try{
		$user = iMSCP_Props_client::getInstanceByName($userName);
	}catch (Exception $e){
		return false;
	}
	return true;
}

/**
 * Authenticate an user and redirect it to his interface
 *
 * @throw iMSCP_Exception|iMSCP_Exception_Production
 * @param string $userName User name
 * @param string $userPassword User password
 * @return FALSE on error
 */
function register_user($userName, $userPassword){
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (!username_exists($userName)) {
		write_log(tr('Login error, <b><i>%s</i></b> unknown username', tohtml($userName)), E_USER_NOTICE);

		set_page_message(tr('You entered an incorrect username!'), 'error');
		return false;
	}

	$userData = iMSCP_Props_client::getInstanceByName($userName);

	if ((iMSCP_Update_Database::getInstance()->isAvailableUpdate() ||
		($cfg->MAINTENANCEMODE)) && $userData->user_type != 'admin'
	) {
		write_log(tr('Login error, <b><i>%s</i></b> system currently in maintenance mode', tohtml($userName)), E_USER_NOTICE);
		set_page_message(tr('System is currently under maintenance! Only administrators can login.'));
		return false;
	}

	if (crypt($userPassword, $userData->user_pass) == $userData->user_pass ||
		md5($userPassword) == $userData->user_pass
	) {

		if (isset($_SESSION['user_logged'])) {
			write_log(tr('%s user already logged or session sharing problem! Aborting...', $userName), E_USER_WARNING);
			throw new iMSCP_Exception(tr('User already logged or session sharing problem! Aborting...'));
		}

		$sessionId = session_id();
		$query = 'UPDATE `login` SET `user_name` = ?, `lastaccess` = ? WHERE `session_id` = ?';
		exec_query($query, array($userName, time(), $sessionId));

		$_SESSION['user_logged']		= $userData->user_name;
		$_SESSION['user_pass']			= $userData->user_pass;
		$_SESSION['user_type']			= $userData->user_type;
		$_SESSION['user_id']			= $userData->user_id;
		$_SESSION['user_email']			= $userData->user_email;
		$_SESSION['user_created_by']	= $userData->user_created_by;
		$_SESSION['user_login_time']	= time();

		write_log(tr('%s logged in.', tohtml($userName)), E_USER_NOTICE);
	} else {
		write_log(tr('%s entered incorrect password.', tohtml($userName)), E_USER_NOTICE);
		set_page_message('You entered an incorrect password!', 'error');
		return false;
	}

	// Redirect the user to his level interface
	redirect_to_level_page();
}

/**
 * Check user login.
 *
 * @return boolean
 */
function check_user_login(){
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$sessionId = session_id();

	// kill timed out sessions
	do_session_timeout();

	$userLogged = isset($_SESSION['user_logged']) ? $_SESSION['user_logged'] : false;

	if (!$userLogged) return false;

	$userPassword = $_SESSION['user_pass'];
	$userType = $_SESSION['user_type'];
	$userId = $_SESSION['user_id'];

	// verify session data with database
	$query = "
		SELECT
			*
		FROM
			`user`, `login`
		WHERE
			user.`user_name` = ?
		AND
			user.`user_pass` = ?
		AND
			user.`user_type` = ?
		AND
			user.`user_id` = ?
		AND
			login.`session_id` = ?
	";

	$rs = exec_query($query, array($userLogged, $userPassword, $userType, $userId, $sessionId));

	if ($rs->recordCount() != 1) {
		write_log("Detected session manipulation on " . $userLogged . "'s session!", E_USER_WARNING);
		unset_user_login_data();

		return false;
	}

	if ((iMSCP_Update_Database::getInstance()->isAvailableUpdate() || ($cfg->MAINTENANCEMODE)) &&
		$userType != 'admin'
	) {
		unset_user_login_data(true);
		write_log("System is currently in maintenance mode. Logging out <b><i>" . $userLogged . "</i></b>", E_USER_NOTICE);
		redirectTo('/index.php');
	}

	// If user login data correct - update session and lastaccess
	$_SESSION['user_login_time'] = time();

	$query = "UPDATE `login` SET `lastaccess` = ? WHERE `session_id` = ?";

	exec_query($query, array(time(), $sessionId));

	return true;
}

/**
 * check for valid user login and valid file request/call
 *
 * @param string $fileName Full file path (ie. the magic __FILE__ constant value)
 * @param boolean $preventExternalLogin Check HTTP Referer for valid
 * request/call (ie. to prevent login from external websites)
 */
function check_login($fileName = null, $preventExternalLogin = true){
	if (!check_user_login()) {
		if (is_xhr()) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}

		redirectTo('/index.php');
	}

	// Check user level
	if (!is_null($fileName)) {

		$levels = explode('/', realpath(dirname($fileName)));
		$level = $levels[count($levels) - 1];

		$userType = ($_SESSION['user_type'] == 'user') ? 'client'
			: $_SESSION['user_type'];

		if ($userType != $level) {
			if ($userType != 'admin' &&
				(!isset($_SESSION['logged_from']) ||
				 $_SESSION['logged_from'] != 'admin')
			) {

				$userLoggued = isset($_SESSION['logged_from'])
					? $_SESSION['logged_from'] : $_SESSION['user_logged'];

				write_log('Warning! user |' . $userLoggued . '| requested |' .
							tohtml($_SERVER['REQUEST_URI']) .
							'| with REQUEST_METHOD |' . $_SERVER['REQUEST_METHOD'] . '|', E_USER_WARNING);
			}

			redirectTo('/index.php');
		}
	}

	// prevent external login / check for referer
	if ($preventExternalLogin) {

		// An user try to access the panel from another url ?
		if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {

			// Extracting the URL scheme
			$info = parse_url($_SERVER['HTTP_REFERER']);

			// The URL does contains the host element ?
			if (isset($info['host']) && !empty($info['host'])) {
				$http_host = $_SERVER['HTTP_HOST'];

				// The referer doesn't match the panel hostname ?
				if ($info['host'] != substr($http_host, 0, (int)(strlen($http_host) - strlen(strrchr($http_host, ':'))))
					|| $info['host'] != $_SERVER['SERVER_NAME']
				) {

					set_page_message(tr('Request from foreign host was blocked.'));

					# Quick fix for #96 (will be rewritten ASAP)
					isset($_SERVER['REDIRECT_URL']) ?: $_SERVER['REDIRECT_URL'] = '';
					if (!(substr($_SERVER['SCRIPT_FILENAME'], (int)-strlen($_SERVER['REDIRECT_URL']),
								 strlen($_SERVER['REDIRECT_URL'])) == $_SERVER['REDIRECT_URL'])
					) {
						redirect_to_level_page();
					}
				}
			}
		}
	}
}

/**
 * Switch between user's interfaces
 *
 * This function allows to switch between user's interfaces for admin and
 * reseller user accounts.
 *
 * @param	$fromId User's id that want switch to an other user's interface
 * @param	$toId User identifier that represents the destination interface
 * @return void
 */
function change_user_interface($fromId, $toId) {
	$index = null;

	while (1) {
		$query = "
			SELECT
				`admin_id`, `admin_name`, `admin_pass`, `admin_type`, `email`,
				`created_by`
			FROM
				`admin`
			WHERE
				binary `admin_id` = ?
		";

		$rsFrom = exec_query($query, $fromId);
		$rsTo = exec_query($query, $toId);

		if (($rsFrom->recordCount()) != 1 || ($rsTo->recordCount()) != 1) {
			set_page_message(tr('User does not exist or you do not have permission to access this interface!'), 'warning');
			break;
		}

		$fromUserData = $rsFrom->fetchRow();
		$toUserData = $rsTo->fetchRow();

		if (!is_userdomain_ok($toUserData['admin_name'])) {
			set_page_message(tr("%s's account status is not ok!",
								decode_idna($toUserData['admin_name'])),
							 'warning');
			break;
		}

		$toAdminType = strtolower($toUserData['admin_type']);
		$fromAdminType = strtolower($fromUserData['admin_type']);

		$allowedChanges = array();

		$allowedChanges['admin']['admin'] = 'manage_users.php';
		$allowedChanges['admin']['BACK'] = 'manage_users.php';
		$allowedChanges['admin']['reseller'] = 'index.php';
		$allowedChanges['admin']['user'] = 'index.php';
		$allowedChanges['reseller']['user'] = 'index.php';
		$allowedChanges['reseller']['BACK'] = 'users.php?psi=last';

		if (!isset($allowedChanges[$fromAdminType][$toAdminType]) ||
			($toAdminType == $fromAdminType && $fromAdminType != 'admin')
		) {

			if (isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] == $toId) {
				$index = $allowedChanges[$toAdminType]['BACK'];
				//$restore = true;
			} else {
				set_page_message(tr('You do not have permission to access this interface!'), 'error');
				break;
			}
		}

		$index = $index ? $index : $allowedChanges[$fromAdminType][$toAdminType];

		//unset_user_login_data(false, $restore);
		unset_user_login_data(false, true);

		if (($toAdminType != 'admin' && ((isset($_SESSION['logged_from_id']) &&
											$_SESSION['logged_from_id'] != $toId)
										 || !isset($_SESSION['logged_from_id'])))
			|| ($fromAdminType == 'admin' && $toAdminType == 'admin')
		) {

			$_SESSION['logged_from'] = $fromUserData['admin_name'];
			$_SESSION['logged_from_id'] = $fromUserData['admin_id'];

		}

		if ($fromAdminType == 'user') {
			unset($_SESSION['logged_from'], $_SESSION['logged_from_id']);
		}

		unset($_SESSION['admin_name'], $_SESSION['admin_id'], $GLOBALS['admin_name'], $GLOBALS['admin_id']);

		$_SESSION['user_logged'] = $toUserData['admin_name'];
		$_SESSION['user_pass'] = $toUserData['admin_pass'];
		$_SESSION['user_type'] = $toUserData['admin_type'];
		$_SESSION['user_id'] = $toUserData['admin_id'];
		$_SESSION['user_email'] = $toUserData['email'];
		$_SESSION['user_created_by'] = $toUserData['created_by'];
		$_SESSION['user_login_time'] = time();

		$query = "
			REPLACE INTO
				`login` (
					`session_id`, `ipaddr`, `user_name`, `lastaccess`
				) VALUES (
					?, ?, ?, ?
				)
			";

		exec_query($query, array(session_id(), getipaddr(), $toUserData['admin_name'], $_SESSION['user_login_time']));

		write_log(tr("%s changes into %s's interface",
							decode_idna($fromUserData['admin_name']),
							decode_idna($toUserData['admin_name'])), E_USER_NOTICE);

		break;
	}

	redirect_to_level_page($index);
}

/**
 * Unset user login data.
 *
 * @param bool $ignorePreserve
 * @param bool $restore restore rembered user data
 * @return void
 */
function unset_user_login_data($ignorePreserve = false, $restore = false)
{

	if (isset($_SESSION['user_logged'])) {

		$sessionId = session_id();
		$adminName = $_SESSION['user_logged'];

		$query = "DELETE FROM `login` WHERE `session_id` = ? AND `user_name` = ?";
		exec_query($query, array($sessionId, $adminName));
	}

	$preserveList = array(
		'user_def_lang', 'user_theme', 'uistack', 'user_page_message', 'user_page_message_cls'
	);

	$preserveVals = array();

	if (!$ignorePreserve) {
		foreach ($preserveList as $p) {
			if (isset($_SESSION[$p])) {
				$preserveVals[$p] = $_SESSION[$p];
			}
		}
	}

	$_SESSION = array();

	foreach ($preserveList as $p) {
		if (isset($preserveVals[$p])) {
			$_SESSION[$p] = $preserveVals[$p];
		}
	}

	if ($restore && isset($_SESSION['uistack'])) {
		foreach ($_SESSION['uistack'] as $key => $value) {
			$_SESSION[$key] = $value;
		}

		unset($_SESSION['uistack']);
	}
}

/**
 * Redirects to user level page
 *
 * @param $file
 * @param bool $force
 * @return bool
 */
function redirect_to_level_page($file = null, $force = false)
{

	if (!isset($_SESSION['user_type']) && !$force)
		return false;

	if (!$file) {
		$file = 'index.php';
	}

	$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

	switch ($userType) {
		case 'client':
		case 'admin':
		case 'reseller':
			redirectTo('/' . $userType . '/' . $file);
			break;
		default:
			redirectTo('/index.php');
	}
}
