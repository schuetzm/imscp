<?php
abstract class iMSCP_Props_abstract_props{

	const OBJECT_TYPE			= 'object';

	protected $structure		= array();
	protected $database			= null;

	protected $loadedProps		= false;
	protected $modifiedProps	= false;
	protected $UID				= null;
	protected $byKey			= null;
	protected $objectPrimaryKey	= null;

	protected $validators		= null;

	protected $errors	= array();

	abstract public function translateObjectName();

	function __construct($database, $uid = null, $key = null){
		$this->database	= $database;

		$storageClass = get_called_class().'Storage';
		$storage = new $storageClass();

		$rows = $storage->getContainerStructure($database);

		$validatorClass = get_called_class().'Validators';
		$this->validators = new $validatorClass();
		//var_dump($rows);
		foreach($rows as $row ){
			$this->structure[$row['Field']]	= $row['Default'];
			if($row['Key'] == 'PRI') $this->objectPrimaryKey = $row['Field'];
			//iMSCP_Props_user_userView::setElement($row['Field']);
			$this->validators->setValidator($row['Field'], $row['Type'], ($row['Extra'] == 'auto_increment' ? 'YES' : $row['Null']));
		}
		$this->UID		= $uid;
		$this->byKey 	= $key;
	}

	protected function loadObjectProps(){

		$storageClass = get_called_class().'Storage';
		$storage = new $storageClass();

		if(!is_null($this->UID)){
			$key = is_null($this->byKey) ? $this->objectPrimaryKey : $this->byKey;
			$query = $storage->buildLoadQuery($this->database, $this->structure, $key);
			$storage->execute($query, $this->UID);
			$row = $storage->getRow();
			if($row){
				$this->structure = $row;
			} else {
				$this->structure[$key] = $this->UID;
			}
		}
		$this->loadedProps = true;
	}

	public function  __get($var){
		if(array_key_exists($var, $this->structure)){
			if(!$this->loadedProps){
				$this->loadObjectProps();
			}
			return $this->structure[$var];
		} else {
			throw new Exception(
				tr(
					'%s property %s do not exits in %s!',
					ucfirst($this->translateObjectName()),
					$var,
					$this->database
				)
			);
		}
	}
	public function __set($var, $value){
		//echo "__set($var, $value)\n";
		if(array_key_exists($var, $this->structure)){
			if(!$this->loadedProps){
				$this->loadObjectProps();
			}
			if($this->structure[$var] != $value){
				$this->structure[$var] = $value;
				$this->modifiedProps = true;
			}
		} else {
			throw new Exception(
				tr(
					'%s property %s do not exits in %s!',
					ucfirst($this->translateObjectName()),
					$var,
					$this->database
				)
			);
		}
	}
	public function save(){
		if($this->modifiedProps){
			//echo "$this->database save\n";
			if($this->validators->validate($this->structure)){
				//echo "validate\n";
				$this->saveProp();
			} else {
				//echo "not validate\n";
				throw new Exception(implode("\n", $this->validators->getErrors()));
			}
		}
	}
	public function delete(){
		//echo "$this->database delete\n";
		$storageClass	= get_called_class().'Storage';
		$storage		= new $storageClass();
		$object_id		= static::UNIQUE_ID;
		$storage->delete($this->database, $this->$object_id);
	}

	protected function saveProp(){

		$storageClass	= get_called_class().'Storage';
		$storage		= new $storageClass();

		$query = $storage->buildSaveQuery($this->database, $this->structure);
		$storage->execute($query, array_values($this->structure));
		$this->modifiedProps = false;
	}

	public function getData(){
		if(!$this->loadedProps){
			$this->loadObjectProps();
		}
		return $this->structure;
	}
}
