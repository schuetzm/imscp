<?php

class iMSCP_Props_reseller extends iMSCP_Props_client{

	const OBJECT_TYPE			= 'reseller';

	protected static $byName	= array();
	protected static $byId		= array();

	protected static $all		= array();

	protected $databases		= array(
									'user',
									'user_data',
									'user_gui_props',
									'reseller_props'
								);
	protected $canHave			= array(
									'hosting_plans'		=> array(),
									'client'			=> array()
								);

	public static function loadAll($byKey = null, $keyValue = null){
		if(is_null($byKey) ||is_null($keyValue)){
			return parent::loadAll('user_type', 'reseller');
		} else {
			return parent::loadAll($byKey, $keyValue);
		}
	}

}

