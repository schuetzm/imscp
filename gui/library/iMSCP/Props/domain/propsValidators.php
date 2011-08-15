<?php
class iMSCP_Props_domain_propsValidators extends iMSCP_Validators_validator{

	protected $validators		= null;

	protected $errors	= array();


	function setValidator($group, $type, $null){
	}

	function validate($values){
		throw new Exception('TODO');
	}

	public function validate_DOMAIN_NAME($user_name) {
		throw new Exception('TODO');
	}
	public function getErrors(){
		return $this->errors;
	}

}
