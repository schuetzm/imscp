<?php
class  iMSCP_Props_abstract_storage{

	const PRIMARYKEY			= 'UID';

	public function getContainerStructure($table){
		$query = "SHOW FULL COLUMNS FROM `$table`";
		$rs = execute_query($query);
		return $rs->fetchAll();
	}

	public function buildLoadQuery($table, $values, $field = null){
		//if(is_null($field))$field = static::PRIMARYKEY;
		$func = create_function('$value', 'return "`$value`";');
		$tableFields = implode(',', array_map($func, array_keys($values)));
		$query = '
			SELECT
				' . $tableFields . '
			FROM
				`' . $table .'`
		';
		if(!is_null($field)){
			$query .=  '
				WHERE
					`' . $field . '` = ?
			';
		}
		$query = str_replace('`*`', '*', $query);
		return $query;
	}

	public function buildSaveQuery($table, $values){
		$func = create_function('$value', 'return "`$value`";');
		$tableValues = implode(',', array_pad(array(), count($values), '?'));
		$tableFields = implode(',', array_map($func,array_keys($values)));
		return '
			REPLACE INTO
				`' . $table . '`
			(
				' . $tableFields . '
			)
			VALUES
			(
				' . $tableValues . '
			)
		';
	}

	public function delete($database, $uid){
		exec_query("DELETE FROM `$database` WHERE `".static::PRIMARYKEY."` = ?", $uid);
	}

	public function execute($query, $params = array()){
		$this->rs = exec_query($query, $params);
	}

	public function getRow(){
		return $this->rs->fetchRow();
	}
	public function getRows(){
		return $this->rs->fetchAll();
	}
	public function startTransaction(){
		static $db = null;

		if(null === $db) {
			/** @var $db iMSCP_Database */
			$db = iMSCP_Registry::get('db');
		}
		//echo "startTransaction\n";
		$db->beginTransaction();
	}
	public function commitTransaction(){
		static $db = null;

		if(null === $db) {
			/** @var $db iMSCP_Database */
			$db = iMSCP_Registry::get('db');
		}
		//echo "commitTransaction\n";
		$db->commit();
	}
	public function rollbackTransaction(){
		static $db = null;

		if(null === $db) {
			/** @var $db iMSCP_Database */
			$db = iMSCP_Registry::get('db');
		}
		//echo "rollbackTransaction\n";
		$db->rollback();
	}

}
