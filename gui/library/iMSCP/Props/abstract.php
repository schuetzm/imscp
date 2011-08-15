<?php

abstract class iMSCP_Props_abstract{

	const OBJECT_TYPE				= 'object';

	protected static $byName		= array();
	protected static $byId			= array();

	protected static $mainDatabase	= 'db';

	protected $databases			= array();
	protected $init					= false;
	protected $canHave				= array();

	abstract public function translateObjectName();

	protected static function testTypeMatch($instance){}

	public static function getInstanceById($id){
		if(is_null($id)|| !is_numeric($id)){
			throw new Exception(tr('Invalid id: %s', $id));
		}
		$class = get_called_class();
		try{
			return iMSCP_Cache_objectCache::get($class, $id);
		} catch (Exception $e){
			$instance = new $class($id);
			$object_name	= static::UNIQUE_NAME;
			$object_id		= static::UNIQUE_ID;
			if(is_null($instance->$object_name) || is_null($instance->$object_id)){
				throw new Exception(tr('No %s has this id: %s', $instance->translateObjectName(), $id));
			}
			$instance = $class::testTypeMatch($instance);
			iMSCP_Cache_objectCache::set($instance, $instance->$object_name);
			iMSCP_Cache_objectCache::set($instance, $instance->$object_id);
		}
		return $instance;
	}

	public static function getInstanceByName($name){
		if(is_null($name)){
			throw new Exception(tr('Invalid name!', $name));
		}
		$class = get_called_class();
		try{
			return iMSCP_Cache_objectCache::get($class, $name);
		} catch (Exception $e){
			$object_name	= static::UNIQUE_NAME;
			$object_id		= static::UNIQUE_ID;

			$instance = new $class($name, $object_name);
			if(is_null($instance->$object_name) || is_null($instance->$object_id)){
				throw new Exception(tr('No such %s %s', $instance->translateObjectName(), $name));
			}
			$instance = $class::testTypeMatch($instance);
			iMSCP_Cache_objectCache::set($instance, $instance->$object_name);
			iMSCP_Cache_objectCache::set($instance, $instance->$object_id);
		}
		return $instance;
	}

	public static function getNewInstance($name){
		$class = get_called_class();
		if(is_null($name)){
			throw new Exception(tr('Invalid name!', $name));
		}
		try{
			$class::getInstanceByName($name);
		} catch (Exception $e){
			$object_name	= static::UNIQUE_NAME;
			$object_id		= static::UNIQUE_ID;
			$instance = new $class($name, $object_name);
			$instance->$object_name = $name;
			//echo "add type instance match";
			return $instance;
		}
		throw New Exception(tr('%s %s already exists', ucfirst($instance->translateObjectName()), $name));
	}

	protected function __construct($id, $byKey = null){
		$propsClass	= get_called_class().'_props';
		$object_id	= static::UNIQUE_ID;

		foreach ($this->databases as $database){
			//echo "\$database:$database|\$id:$id|\$byKey:$byKey\n";
			$this->$database = new $propsClass($database, $id, $byKey);
			if(!is_null($byKey)){
				$id = $this->$database->$object_id;
				$byKey = null;
			}
		}
		$this->init	= true;
	}

	public function  __get($var){
		if(!$this->init){
			return $this->$var;
		}
		$error = '';
		foreach($this->databases as $database) {
			try{
				return $this->$database->$var;
			} catch (Exception $e){$error[] = $e->getMessage();}
		}
		try {
			return $this->tryCanHave($var);
		} catch (Exception $e){$error[] = $e->getMessage();}
		throw new Exception(join("\n",$error));
	}

	protected function tryCanHave($var){
		if(array_key_exists($var, $this->canHave)){
			$class = $this->translateProps[$var];
			return $class::loadAll();
		}
		throw new Exception(
			tr(
				'%s property %s do not exits in %s!',
				ucfirst($this->translateObjectName()),
				$var,
				join(', ', array_keys( $this->canHave)))
		);
	}

	public function __set($var, $value){
		if(!$this->init){
			$this->$var = $value;
			return;
		}
		$error = array();
		foreach($this->databases as $database) {
			try{
				$this->$database->$var = $value;
				return;
			} catch (Exception $e){$error[] =$e->getMessage();}
		}
		if(array_key_exists($var, $this->canHave)){
			throw new Exception(tr('Property %s can be modified only via object interface', $var));
		}
		throw new Exception(join("\n", $error));
	}

	public function delete(){
		$storageClass = get_called_class().'_propsStorage';
		$storage = new $storageClass();
		$storage->startTransaction();
		foreach ($this->databases as $database){
			$this->$database->delete();
		}
		$storage->commitTransaction();
	}

	public function save(){
		$storageClass = get_called_class().'_propsStorage';
		$storage = new $storageClass();
		$storage->startTransaction();
		$this->{static::$mainDatabase}->save();
		if(is_null($this->{static::$mainDatabase}->user_id)){
			$this->create();
		} else {
			foreach ($this->databases as $database){
				if($database != static::$mainDatabase){
					$this->$database->save();
				}
			}
		}
		$storage->commitTransaction();
	}

	protected function create(){
		$class = get_called_class();
		$object_name	= static::UNIQUE_NAME;
		$object_id		= static::UNIQUE_ID;
		$newObject = new $class($this->$object_name, $object_name);
		foreach ($this->databases as $database){
			if($database != static::$mainDatabase){
				$this->$database->$object_id = $newObject->$object_id;
				$this->$database->save();
			}
		}
	}

	public function getData(){
		return $this->{static::$mainDatabase}->getData();
	}

	public static function loadAll($byKey = null, $keyValue = null){
		if(static::$all == array()){
			$className = get_called_class();
			$storageClass = get_called_class()."_propsStorage";
			$storage = new $storageClass();
			$values = array(static::UNIQUE_ID => '');
			if(!is_null($byKey))$values[$byKey] = '';
			$query = $storage->buildLoadQuery(static::$mainDatabase, $values, $byKey);
			$storage->execute($query, $keyValue);
			$objects = $storage->getRows();
			foreach( $objects as $object){
				static::$all[] = $className::getInstanceById($object[static::UNIQUE_ID]);
			}
		}
		return static::$all;
	}


}


