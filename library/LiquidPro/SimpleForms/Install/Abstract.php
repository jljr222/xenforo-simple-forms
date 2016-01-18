<?php

abstract class LiquidPro_SimpleForms_Install_Abstract
{
	/**
	 * Database object
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $_db = null;
	
	abstract public function install(&$db);
	
	/**
	 * Fetches results from the database with each row keyed according to preference.
	 * The 'key' parameter provides the column name with which to key the result.
	 * For example, calling fetchAllKeyed('SELECT item_id, title, date FROM table', 'item_id')
	 * would result in an array keyed by item_id:
	 * [$itemId] => array('item_id' => $itemId, 'title' => $title, 'date' => $date)
	 *
	 * Note that the specified key must exist in the query result, or it will be ignored.
	 *
	 * @param string SQL to execute
	 * @param string Column with which to key the results array
	 * @param mixed Parameters for the SQL
	 *
	 * @return array
	 */
	public function fetchAllKeyed($sql, $key, $bind = array())
	{
		$results = array();
		$i = 0;

		$stmt = $this->_getDb()->query($sql, $bind, Zend_Db::FETCH_ASSOC);
		while ($row = $stmt->fetch())
		{
			$i++;
			$results[(isset($row[$key]) ? $row[$key] : $i)] = $row;
		}

		return $results;
	}
	
	public function describeTable($tableName)
	{
		return $this->fetchAllKeyed('
			SELECT
				*
			FROM `INFORMATION_SCHEMA`.`COLUMNS`
			WHERE `TABLE_SCHEMA` = DATABASE()
				AND `TABLE_NAME` = ?
		', 'COLUMN_NAME', array($tableName));
	}
	
	/**
	* Helper method to get the database object.
	*
	* @return Zend_Db_Adapter_Abstract
	*/
	protected function _getDb()
	{
		if ($this->_db === null)
		{
			$this->_db = XenForo_Application::getDb();
		}

		return $this->_db;
	}
}