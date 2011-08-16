<?php
class iMSCP_Props_dnsMngr_propsValidators extends iMSCP_Validators_validator{

	protected $validators		= null;

	protected $errors	= array();


	function setValidator($group, $type, $null){}

	function validate($values){
		throw new Exception('TODO');
	}

	public function validate_DOMAIN_DNS($name) {throw new Exception('TODO');}
	public function validate_DOMAIN_CLASS($name) {throw new Exception('TODO');}
	public function validate_DOMAIN_TYPE($name) {throw new Exception('TODO');}
	public function validate_DOMAIN_TEXT($name) {throw new Exception('TODO');}

	public function getErrors(){
		return $this->errors;
	}

}
