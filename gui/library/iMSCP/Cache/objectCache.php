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

 class iMSCP_Cache_objectCache{

	protected static $objects = array();

	static function set(&$obj, $uid){
		$type = get_class($obj);
		self::$objects[$type][$uid] = $obj;
	}

	static function &get($type, $uid){
		if(
			array_key_exists($type, self::$objects)
			&&
			array_key_exists($uid, self::$objects[$type])
			&&
			is_object(self::$objects[$type][$uid])
			&&
			self::$objects[$type][$uid] instanceOf $type
		){
			return self::$objects[$type][$uid];
		} else {
			throw new Exception(tr('Can not retrive %s object id %s!', $type, $uid));
		}
	}
	static function delete($type, $uid){
		if(
			array_key_exists($type, self::$objects)
			&&
			array_key_exists($uid, self::$objects[$type])
		) unset(self::$objects[$type][$uid]);
	}
}
