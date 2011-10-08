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
 * @subpackage	 	Arsp	
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
 * @subpackage		Arsp
 * @author		Hannes Koschier <hannes@cheat.at>
 * @version		0.0.1
 */

// CLASS STILL IN CHANGE - NOT STABLE - WORKING ON IT

class iMSCP_Arsp
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
	* Flag if a Error 
	*
	* @var string
	**/
	protected $_errorFlag;

	/**
	 * Constructor
	 *
	 */
	public function __construct($mailId)
	{
		//Set Mail ID
		$this->_mailId = $mailId;
		
		 // Check Permission (should never happen without user doing something nasty like ?id=xxx)
		if (!$this->_checkperm($_SESSION['user_id'])) {
			throw new Exception('Security violation at AutoResponser');		
		}
	
		// Check if Email is enabled (should never happen without user doing something nasty)
		if (!$this->_checkEmailFeature($_SESSION['user_id'])) {
                        throw new Exception('Emails not enabled for this Domain');
                }
		
		$this->_errorFlag = false;

		//Load Autoresponser from DB (need it for new Responser too)
		$this->_loadAutoRespondfromDb();
		
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
				t1.`mail_id` , t1.`domain_id`, t2.`domain_id`, t2.`domain_admin_id`
			FROM
				`mail_users` AS t1, `domain` AS t2
			WHERE
				t1.`mail_id` = ?
                	AND
                        	t2.`domain_id` = t1.`domain_id`
			AND
                                t2.`domain_admin_id` = ?
		";
		$stmt = exec_query($query, array($this->_mailId, $userId));
	
	        if ($stmt->recordCount() == 0) { // if no Data than user has not permission to edit this autoresponser
			return false;
		}
		return true;	
		
	}	
	
	/**
        * Load needed Vals from Table mail_users into class vars
        * @return void
        **/
        protected function _loadAutoRespondfromDb()
        {
                $query = "
                        SELECT
                                `mail_auto_respond`,`mail_auto_respond_text`,
				DATE_FORMAT(`mail_auto_respond_start`,'%Y-%m-%d %H:%i') AS mail_auto_respond_start,
				DATE_FORMAT(`mail_auto_respond_stop`,'%Y-%m-%d %H:%i') AS mail_auto_respond_stop
                        FROM
                                `mail_users`
                        WHERE
                                `mail_id` = ?
                ";
                $stmt = exec_query($query, $this->_mailId);

		$this->_responserStatus = $stmt->fields('mail_auto_respond');
		$this->_message = $stmt->fields('mail_auto_respond_text');
		$this->_startDate = $stmt->fields('mail_auto_respond_start');
                $this->_stopDate = $stmt->fields('mail_auto_respond_stop');
        }

	/**
        * Is Email Feature enabled
        * @return bool 
        **/
        protected function _checkEmailFeature($userId) {
		$domainProperties = get_domain_default_props($userId, true);
			if ($domainProperties['domain_mailacc_limit'] == -1) {
			return false;
		}
		return true;
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
                return $this->_startDate;
        }

        /**
        * Get Stop Date/Time of Responder
        * @return DateTime
        **/
        public function getStopDate() {
                return $this->_stopDate;
        }

        /**
        * Get Error Flag
        * @return DateTime
        **/
        public function getErrorFlag() {
                return $this->_errorFlag;
        }

        /**
        * Set Message
        * @return bool 
        **/
        public function setMessage($mes) {
		if (empty($mes)) { 
			$this->_errorFlag = true;
			return false;
		}
                $this->_message = $mes;
		return true;
        }

        /**
        * SET ON/OFF Status of Responder (table field mail_auto_respond)
        * 
        **/
        public function setStatus($stat) {
                $this->_responserStatus = $stat;
        }

        /**
        * Set Start Date/Time of Responder
        * @return bool
        **/
        public function setStartDate($startDate) {
		if (!$this->_validateDateTime($startDate)) {
			$this->_errorFlag = true;
			return false;
		}
                $this->_startDate = $startDate;
		return true;
        }

        /**
        * Set Stop Date/Time of Responder
        * @return bool
        **/
        public function setStopDate($stopDate) {
                if (!$this->_validateDateTime($stopDate)) {
			$this->_errorFlag = true;
                        return false;
                }
                $this->_stopDate = $stopDate;
                return true;
        }

	/**
	* Save into to DB
        * @return void
        **/
        public function saveDB($changeStatus)
        {
                $query = "
                        UPDATE
                                `mail_users`
                        SET
                                `status` = ?, `mail_auto_respond` = 1, `mail_auto_respond_text` = ?,
				`mail_auto_respond_start` = ?, `mail_auto_respond_stop` = ?
                        WHERE
                                `mail_id` = ?
                ";
		$stmt = exec_query($query,
					array(	
						$changeStatus,
						$this->_message,
						$this->_startDate,
						$this->_stopDate,
						$this->_mailId
						)
					);
        }

	/**
	* helper method to validate date/time
	*
	* @return bool
	**/
	protected function _validateDateTime($dateTime) {

		if (!isset($dateTime)) {
			return false;
		}

		$DTArr = str_split($dateTime); //convert chars into array eg 2010-12-01 14:00
		$y = $DTArr[0].$DTArr[1].$DTArr[2].$DTArr[3]; //year
		$m = $DTArr[5].$DTArr[6]; //Month
		$d = $DTArr[8].$DTArr[9]; //Day
		$hour = $DTArr[11].$DTArr[12]; //Hour
		$min = $DTArr[14].$DTArr[15]; //Min

		if (!checkdate($m, $d, $y)) {
			return false;
		}

		if ($hour > 24 || $hour < 0) {
			return false;
		}

                if ($min > 60 || $hour < 0) {
                        return false;
                }
		return true;
	}

}












