<?php
class iMSCP_Props_alias_propsValidators extends iMSCP_Validators_validator{

	protected $validators		= null;

	protected $errors	= array();


	function setValidator($group, $type, $null){
	}

	function validate($values){
		throw new Exception('TODO');
	}

	public function validate_ALIAS_NAME($name) {
		throw new Exception('TODO');
	}
	public function getErrors(){
		return $this->errors;
	}

}
