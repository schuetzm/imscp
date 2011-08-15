<?php

class iMSCP_Props_client extends iMSCP_Props_abstract{

	const OBJECT_TYPE			= 'client';
	const UNIQUE_ID				= 'user_id';
	const UNIQUE_NAME			= 'user_name';

	protected static $byName	= array();
	protected static $byId		= array();

	protected static $all		= array();

	protected static $mainDatabase = 'user';

	protected $databases		= array(
										'user',
										'user_data',
										'user_gui_props',
										'user_system_props'
								);

	protected $init				= false;

	protected $canHave			= array(
									'domains'		=> array(),
									'sql_databases'	=> array(),
									'backup'		=> array(),
									'ips'			=> array(),
									'crons'			=> array(),
									'certificates'	=> array()
								);

	protected $translateProps	= array(
									'domains'	=> 'iMSCP_Props_domain',
								);

	public function translateObjectName(){
		return tr('user');
	}

	protected static function testTypeMatch($instance){
		if($instance->user_type != static::OBJECT_TYPE){
			//var_dump($instance);
			$user = 'iMSCP_Props_'.$instance->user_type;
			return $user::getInstanceById($instance->user_id);
		}
		return $instance;
	}

	public static function loadAll($byKey = null, $keyValue = null){
		if(is_null($byKey) ||is_null($keyValue)){
			return parent::loadAll('user_type', 'client');
		} else {
			return parent::loadAll($byKey, $keyValue);
		}
	}

}

