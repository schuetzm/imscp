<?php

class iMSCP_Props_admin extends iMSCP_Props_client{

	const OBJECT_TYPE			= 'admin';

	protected static $byName	= array();
	protected static $byId		= array();

	protected static $all		= array();

	protected $databases		= array(
									'user',
									'user_data',
									'user_gui_props'
								);
	protected $canHave			= array(
									'hosting_plan'	=> array(),
									'admin'			=> array(),
									'reseller'		=> array(),
									'client'		=> array()
								);
	protected $translateProps	= array(
									'hosting_plan'	=> '',
									'admin'			=> 'iMSCP_Props_admin',
									'reseller'		=> 'iMSCP_Props_reseller',
									'client'		=> 'iMSCP_Props_client'
								);


	public static function loadAll($byKey = null, $keyValue = null){
		return parent::loadAll('user_type', 'admin');
	}
}

