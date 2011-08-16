<?php

class iMSCP_Props_mail extends iMSCP_Props_abstract{

	const OBJECT_TYPE			= 'mail';
	const UNIQUE_ID				= 'mail_id';
	const UNIQUE_NAME			= 'mail_addr';


	protected static $byName	= array();
	protected static $byId		= array();

	protected static $all		= array();

	protected static $mainDatabase = 'mail_users';

	protected $databases		= array(
									'mail_users'
								);

	protected $init				= false;

	public function translateObjectName(){
		return tr('mail');
	}

	protected static function testTypeMatch($instance){
		return $instance;
	}

/*
	public static function loadAll($byKey = null, $keyValue = null){
		if(is_null($byKey) ||is_null($keyValue)){
			return parent::loadAll('subdomain_id', '*');
		} else {
			return parent::loadAll($byKey, $keyValue);
		}
	}
*/


}

