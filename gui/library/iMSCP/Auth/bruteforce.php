<?php

/**
 * 4isp server administrative tools
 *
 * @copyright	Copyright (C) 2009 by Daniel Andreca (4isp.ro)
 * @version		SVN: $Id$
 * @link		http://4isp.ro
 * @author		Daniel Andreca <sci2tech@gmail.com>
 *
 * @license
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class iMSCP_Auth_bruteforce{

	protected static $type	= 'login';

	protected $loginCount	= 0;
	protected $captchaCount	= 0;
	protected $blockingTime	= 0;		//Calculated blocking time
	protected $waitingTime	= 0;		//Calculated waintinging time
	protected $recordExists	= false;

	protected $blockTime	= 0;		//Time to be blocked on fails
	protected $waitTime		= 0;		//Time to wait on fails
	protected $MaxCount		= 0;

	public function __construct($type = 'login'){

		$cfg = iMSCP_Registry::get('config');
		self::$type = $type;
		$this->MaxCount		= self::$type == 'login' ? $cfg->BRUTEFORCE_MAX_LOGIN : $cfg->BRUTEFORCE_MAX_CAPTCHA;
		$this->blockTime	= $cfg->BRUTEFORCE_BLOCK_TIME;
		$this->waitTime		= $cfg->BRUTEFORCE_BETWEEN_TIME;

		$this->unblock();
		$this->init();
	}

	protected function init(){

		$sql = 'SELECT * FROM `login` WHERE `ipaddr` = ? AND `user_name` is NULL';
		$rs = exec_query($sql, $_SERVER['REMOTE_ADDR']);

		if ($rs->recordCount() == 0){
			$this->recordExists = false;
		} else {
			$this->recordExists = true;
			if($rs->fields(self::$type.'_count') >= $this->MaxCount){
				$this->blockingTime = $rs->fields('lastaccess') + $this->blockTime * 60;
				$this->waitingTime = 0;
				$this->message[] = tr('Found 1 record. Ip %s is blocked for another %s minutes!', $_SERVER['REMOTE_ADDR'], $this->isBlockedFor());
			} else {
				$this->message[] = tr('Found records for ip %s', $_SERVER['REMOTE_ADDR']);
				$this->blockingTime = 0;
				$this->waitingTime = $rs->fields('lastaccess') + $this->waitTime;
			}
		}
	}

	public function recordAttempt(){
		$type = self::$type.'Count';
		if(!$this->recordExists){
			$this->message[] = tr('No records found for ip %s with username not set!', $_SERVER['REMOTE_ADDR']);
			$this->createRecord();
		} else {
			$this->updateRecord($this->$type);
		}
	}

	protected function updateRecord($count){
		if($count < $this->MaxCount){
			$this->message[] = tr('Increasing login attempts by 1 for ip %s!', $_SERVER['REMOTE_ADDR']);
			$sql = 'UPDATE `login` SET `lastaccess` = ?, `'.self::$type.'_count` = `'.self::$type.'_count` + 1 WHERE `ipaddr` = ? AND `user_name` IS NULL';
			exec_query($sql, array(time(), $_SERVER['REMOTE_ADDR']));
		}
	}

	protected function createRecord(){
		$this->message[] = tr('Creating record for ip %s!', $_SERVER['REMOTE_ADDR']);
		$sql = '
			REPLACE INTO
				`login`
			(
				`session_id`,
				`ipaddr`,
				`user_name`,
				`lastaccess`
			)
			VALUES
			(
				?,
				?,
				NULL,
				?
			)
		';
		$result = exec_query($sql, array(session_id(), $_SERVER['REMOTE_ADDR'], time()));
	}

	protected function unblock($type = 'bruteforce') {
		$this->message[] = tr('Unblocking expired sessions!');
		$timeout = time() - ($this->blockTime * 60);
		$sql = 'UPDATE `login` SET `'.self::$type.'_count` = 0 WHERE `lastaccess` < ? AND `user_name` IS NULL';
		exec_query($sql, $timeout);
	}

	public function isBlocked(){
		if(  $this->blockingTime - time() > 0 ){
			$this->message[] = tr('Ip %s is blocked for %s minutes!', $_SERVER['REMOTE_ADDR'], $this->isBlockedFor());
			return true;
		}
		return false ;
	}

	public function isWaiting(){
		if($this->waitingTime - time() > 0){
			$this->message[] = tr('Ip %s is waiting %s seconds!', $_SERVER['REMOTE_ADDR'], $this-> isWaitingFor());
			return true;
		}
		return false ;
	}

	public function isBlockedFor(){
		return strftime("%M:%S", ($this->blockingTime - time() > 0) ? $this->blockingTime - time() : 0 );
	}

	public function isWaitingFor(){
		return strftime("%M:%S", ($this->waitingTime - time() > 0 ) ?  $this->waitingTime - time()  : 0 ) ;
	}

	public function getLastMessage(){
		return array_key_exists(count($this->message)-1, $this->message) ? $this->message[count($this->message)-1] : '';
	}

	public function getMessages(){
		return array_key_exists(count($this->message)-1, $this->message) ? $this->message[count($this->message)-1] : '';
	}
}
?>
