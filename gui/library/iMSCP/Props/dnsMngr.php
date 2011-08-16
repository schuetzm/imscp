<?php

class iMSCP_Props_dnsMngr extends iMSCP_Props_abstract{

	const OBJECT_TYPE			= 'dns';
	const UNIQUE_ID				= 'domain_dns_id';
	const UNIQUE_NAME			= 'domain_dns_id';

	protected static $byId		= array();

	protected static $all		= array();

	protected static $mainDatabase = 'domain_dns';

	protected $databases		= array(
									'domain_dns'
								);

	protected $init				= false;

	public function translateObjectName(){
		return tr('domain dns');
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

