<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * @category		iMSCP
 * @package		iMSCP_Core
 * @subpackage		MailAutoRespond
 * @copyright		2001-2011 by i-MSCP team
 * @author		Hannes Koschier <hannes@cheat.at>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 * @Version		$Id
 */

/**
 * Class to manage Mail Autorespond settings files.
 *
 * @category		iMSCP
 * @package		iMSCP_Core
 * @subpackage		MailAutoRespond
 * @author		Hannes Koschier <hannes@cheat.at>
 * @version		0.0.1
 */

// CLASS STILL IN CHANGE - NOT STABLE - WORKING ON IT

class iMSCP_MailAutoRespond
{

	/**
	 * Var for the response message.
	 *
	 * @var string
	 */
	protected $_message;

	/**
	 * Var for the Start Date/Time.
	 *
	 * @var string
	 */
	protected $_startDate;

	/**
	 * Var for the Stop Date/Time.
	 *
	 * @var string
	 */
	protected $_stopDate;

        /**
         * Var for mailId.
         *
         * @var int
         */
        protected $_mailId;

        /**
         * Var for Response Status (enabled/disabled).
         *
         * @var int
         */
        protected $_responserStatus;

	/**
	 * Constructor
	 *
	 */
	public function __construct($mailId)
	{
		//Set Mail ID
		$this->_mailId = $mailId;
		
		// Check Permission - if no permission drop out with false
		if (!$this->_checkperm($_SESSION['user_id'])) {
			return false;
		}

		//Load Autoresponser from DB (need it for new Responser too)
		$this->_loadAutoRespondfromDb();

		return true;
		
	}
	
	/**
	* check if user has permission to set a responser for this mailid
	*
	* @return bool
	**/
	protected function _checkperm($userId)
	{
		$query = "
			SELECT 
				t1.`mail_idi` , t1.`domain_id`, t2.`domain_id`, t2.`domain_admin_id`
			FROM
				`mail_users` AS t1, `domain` AS t2
			WHERE
				t1.`mail_id` = ?
                	AND
                        	t2.`domain_id` = t1.`domain_id`
			AND
                                t2.`domain_admin_id` = ?
		";
		$stmt = exec_query($query, array($this->mailId, $userId));
	
	        if ($stmt->recordCount() == 0) { // if no Data than user has not permission to edit this autoresponser
			return false;
		}
		return true;	
		
	}	
	
	/**
        * Load needed Vals from Table mail_users into class vars
        * @param bool $domainId Domain unique identifier
        * @return void
        **/
        protected function _loadAutoRespondfromDb()
        {
                $query = "
                        SELECT
                                `mail_auto_respond`,`mail_auto_respond_text`,
                                `mail_auto_respond_start`, `mail_auto_respond_stop`
                        FROM
                                `mail_user`
                        WHERE
                                `domain_id` = ?
                ";
                $stmt = exec_query($query, $this->_mailId);

		$this->_responserStatus = $stmt->fields('mail_auto_respond');
		$this->_message = $stmt->fields('mail_auto_respond_text');
		$this->_startDate = $stmt->fields('mail_auto_respond_start');
                $this->_stopDate = $stmt->fields('mail_auto_respond_stop');
        }

	/**
	* Get Message
	* @return string
	**/
	public function getMessage() {
		return $this->_message;
	}

        /**
        * Get ON/OFF Status of Responder (table field mail_auto_respond)
        * @return int
        **/
        public function getStatus() {
                return $this->_responserStatus;
        }

        /**
        * Get Start Date/Time of Responder
        * @return DateTime
        **/
        public function getStartDate() {
                return $this->_StartDate;
        }

        /**
        * Get Stop Date/Time of Responder
        * @return DateTime
        **/
        public function getStopDate() {
                return $this->_StopDate;
        }

}
