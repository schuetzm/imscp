<?php
class iMSCP_Props_client_propsValidators extends iMSCP_Validators_validator{

	protected $validators		= null;

	protected $errors			= array();


	function setValidator($group, $type, $null){
		$this->validators[$group][] = array($type, $null);
	}

	function validate($values){
		//echo "validate\n";
		foreach ($this->validators as $field => $value){
			//echo "\nvalidate $field\n";
			foreach ($value as $calback){
				$null		= $calback[1];
				$validator	= $calback[0];
				if($calback[1] == 'NO'){
					//echo "$field: \$this->validateNotNUL(".(is_null($values[$field])?"NULL":$values[$field]).")";
					if(!$this->validateNotNUL($values[$field])){
						$this->errors[] = sprintf($this->getError(), strtoupper('TR_'.$field));
					}
				}
				if(!is_null($values[$field])){
					$mode = strtoupper(strstr($calback[0], '(', true));
					$function = 'validate' . ($mode ? $mode : strtoupper($calback[0]));
					$argumets = str_replace('(', '', strstr(strstr($calback[0], '(', false), ')', true));
					$unsigned = strpos($calback[0], 'unsigned') ? true : false;
					//echo "$field: \$this->$function(".(is_null($values[$field])?"NULL":$values[$field]).", $argumets,".($unsigned ? 'true' : 'false').")";
					if(method_exists($this, $function)){
						if(!call_user_func_array (array($this, $function), array($values[$field],$argumets, $unsigned))){
							$this->errors[] = sprintf($this->getError(), strtoupper('TR_'.$field));
						}
					}
					$function = 'validate_'  .strtoupper($field);
					if(method_exists($this, $function)){
						if(!call_user_func_array (array($this, $function), array($values[$field]))){
							$this->errors[] = $this->getError();
						}
					}
				}
			}
		}
		//echo "is valid: ".($this->errors==array() ? "yes" : "no")."\n";
		return $this->errors == array();
	}

}
