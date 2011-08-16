<?php

class iMSCP_Props_domain extends iMSCP_Props_abstract{

	const OBJECT_TYPE			= 'domain';
	const UNIQUE_ID				= 'domain_id';
	const UNIQUE_NAME			= 'domain_name';


	protected static $byName	= array();
	protected static $byId		= array();

	protected static $all		= array();

	protected static $mainDatabase = 'domain';

	protected $databases		= array(
										'domain'
								);

	protected $init				= false;
	protected $canHave			= array(
									'subdomains'	=> array(),
									'aliases'		=> array(),
									'dns'			=> array(),
									'ftp'			=> array(),
									'mails'			=> array()
								);

	protected $translateProps	= array(
									'subdomains'	=> 'iMSCP_Props_subdomain',
									'aliases'		=> 'iMSCP_Props_alias',
									'dns'			=> 'iMSCP_Props_dnsMngr',
									'mails'			=> 'iMSCP_Props_mail',
								);

	public function translateObjectName(){
		return tr('domain');
	}

	protected static function testTypeMatch($instance){
		return $instance;
	}

/*
	public static function loadAll($byKey = null, $keyValue = null){
		if(is_null($byKey) ||is_null($keyValue)){
			return parent::loadAll('domain_id', '*');
		} else {
			return parent::loadAll($byKey, $keyValue);
		}
	}
*/

}

