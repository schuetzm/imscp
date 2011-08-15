<?php

class iMSCP_Props_subdomain extends iMSCP_Props_abstract{

	const OBJECT_TYPE			= 'subdomain';
	const UNIQUE_ID				= 'subdomain_id';
	const UNIQUE_NAME			= 'subdomain_name';

	protected static $byName	= array();
	protected static $byId		= array();

	protected static $all		= array();

	protected static $mainDatabase = 'subdomain';

	protected $databases		= array(
									'subdomain'
								);

	protected $init				= false;

	protected $canHave			= array(
									'ftp'	=> array(),
									'dns'	=> array(),
									'mails'	=> array()
								);

	public function translateObjectName(){
		return tr('subdomain');
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

