<?php
class iMSCP_Props_user_view{

	protected static $SformsElement = array();

	static function setElement($element){
		self::$SformsElement[] = $element;
	}
	function __construct(){
		$this->formsElement = self::$SformsElement;
	}
}

