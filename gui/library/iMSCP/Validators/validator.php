<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP Team
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
 * @category    iMSCP
 * @copyright   2010-2011 i-MSCP Team
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

class iMSCP_Validators_validator{

	public $error	='';

	public function validateNotNUL($value){
		if(is_null($value)){
			$this->error = tr('{%s} can not be empty!');
			//echo "...fail\n";
			return false;
		}
		//echo "...pass\n";
		return true;
	}

	public function validateINT($value, $size, $unsigned = false){
		//echo "validateINT";
		if(!is_int($value) && (int)$value != $value) {
			$this->error = tr('{%s} is not a number!');
			//echo "...fail\n";
			return false;
		}
		if($unsigned && $value < 0){
			$this->error = tr('{%s} allow only values bigger then 0!');
			//echo "...fail\n";
			return false;
		}
		if(strlen($value) > $size){
			$this->error = tr('{%s} is bigger then %d!', '%s', $size);
			//echo "...fail\n";
			return false;
		}
		//echo "...pass\n";
		return true;
	}

	public function validateBIGINT($value, $size, $unsigned = false){
		return $this->validateint($value, $size, $unsigned);
	}

	public function validateVARCHAR($value, $size){
		if(!is_string($value) && (string)$value != $value) {
			$this->error = tr('{%s} is not a string!');
			//echo "...fail\n";
			return false;
		}
		if(strlen($value) > $size){
			$this->error = tr('{%s} is bigger then %d!', '%s', $size);
			//echo "...fail\n";
			return false;
		}
		//echo "...pass\n";
		return true;
	}

	public function validateTIMESTAMP($value){
		$date = @date(DATE_ATOM, $value);
		if($date === false){
			$this->error = tr('{%s} is incorect timestamp!');
			//echo "...fail\n";
			return false;
		}
		//echo "...pass\n";
		return true;
	}

	public function validateENUM($value, $values){
		if(is_string($values)){
			$values = explode(',', $values);
		}
		if(!in_array('\''.(string)$value.'\'', $values, true)){
			$this->error = tr('{%s} is not in allowed values: %s!', '%s', implode(',', $values));
			 //echo "...fail\n";
			 return false;
		 }
		 //echo "...pass\n";
		 return true;
	}

	public function getError(){
		return $this->error;
	}

	public function getErrors(){
		return $this->errors;
	}
}
