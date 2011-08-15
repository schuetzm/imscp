<?php

class iMSCP_Props_alias extends iMSCP_Props_abstract{

	const OBJECT_TYPE			= 'alias';
	const UNIQUE_ID				= 'alias_id';
	const UNIQUE_NAME			= 'alias_name';


	protected static $byName	= array();
	protected static $byId		= array();

	protected static $all		= array();

	protected static $mainDatabase = 'domain_aliasses';

	protected $databases		= array(
									'domain_aliasses'
								);

	protected $init				= false;

	public function translateObjectName(){
		return tr('alias');
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

