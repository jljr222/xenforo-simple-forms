<?php

class LiquidPro_SimpleForms_Model_Response extends XenForo_Model
{
	/**
	 * Fetches the response record for the specified response id
	 *
	 * @param integer $id Response ID
	 *
	 * @return array
	 */
	public function getResponseById($id)
	{
		$fetchOptions = array('username' => true);
		$joinOptions = $this->prepareResponseJoinOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT response.*
			' . $joinOptions['selectFields'] . '
			FROM lpsf_response AS response
			' . $joinOptions['joinTables'] . '
			WHERE response.response_id = ?
		', $id);
	}
	
	public function getNumFormResponsesByUserId($userId)
	{
		return $this->_getDb()->fetchAssoc('
			SELECT `form_id`, `user_id`, COUNT(*)
			FROM `lpsf_response` AS `response`
			WHERE `user_id` = ?
			GROUP BY `form_id`, `user_id`
		', $userId);
	}
	
	/**
	 * Fetches all combined response records
	 * 
	 * @param array $conditions
	 * @param array $fetchOptions
	 * 
	 * @return array
	 */
	public function getResponses(array $conditions = array(), array $fetchOptions = array())
	{
		$joinOptions = $this->prepareResponseJoinOptions($fetchOptions);
		$whereConditions = $this->prepareResponseConditions($conditions, $fetchOptions);
		$orderClause = $this->prepareResponseOrderOptions($fetchOptions, 'response.response_id');
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT response.*
				' . $joinOptions['selectFields'] . '
				FROM lpsf_response AS response
				' . $joinOptions['joinTables'] . '
				WHERE ' . $whereConditions . '	
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'response_id');
	}	

	/**
	 * Checks the 'join' key of the incoming array for the presence of the FETCH_x bitfields in this class
	 * and returns SQL snippets to join the specified tables if required
	 *
	 * @param array $fetchOptions Array containing a 'join' integer key build from this class's FETCH_x bitfields and other keys
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys. Example: selectFields = ', user.*, foo.title'; joinTables = ' INNER JOIN foo ON (foo.id = other.id) '
	 */
	public function prepareResponseJoinOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		$db = $this->_getDb();

		if (isset($fetchOptions['username']) && $fetchOptions['username'])
		{
			$selectFields .= ", CASE WHEN user.username IS NULL THEN 'Guest' ELSE user.username END AS username";
			$joinTables .= "LEFT JOIN xf_user AS user ON (user.user_id = response.user_id)";
		}
		
		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	/**
	 * Construct 'ORDER BY' clause
	 *
	 * @param array $fetchOptions (uses 'order' key)
	 * @param string $defaultOrderSql Default order SQL
	 *
	 * @return string
	 */
	public function prepareResponseOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'response_id' => 'response.response_id',
			'response_date' => 'response.response_date'
		);
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}
	
	/**
	 * Prepares a collection of form fetching related conditions into an SQL clause
	 *
	 * @param array $conditions List of conditions
	 * @param array $fetchOptions Modifiable set of fetch options (may have joins pushed on to it)
	 *
	 * @return string SQL clause (at least 1=1)
	 */
	public function prepareResponseConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();
		
		if (!empty($conditions['form_id']))
		{
			$formIdQuoted = $db->quote($conditions['form_id'], XenForo_Input::STRING);
			$sqlConditions[] = "response.form_id = $formIdQuoted";
		} 
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function deleteUserResponses($userId)
	{
		$db = $this->_getDb();
		
		// delete response fields
		$db->query('
			DELETE `lpsf_response_field` FROM `lpsf_response_field` 
			NATURAL JOIN `lpsf_response`
			WHERE `user_id` = ?
		', $userId);
		
		// delete responses
		$db->query('
			DELETE FROM `lpsf_response` 
			WHERE `user_id` = ?
		', $userId);
	}
	
	public function reassignUserResponses($userId)
	{
		$db = $this->_getDb();
		
		// update responses
		$db->query('
			UPDATE `lpsf_response`
			SET `user_id` = NULL
			WHERE `user_id` = ?
		', $userId);		
	}
	
	public function massDeleteResponses($formId)
	{
	    $this->_getDb()->query('
		    DELETE FROM `lpsf_response_field`
		    WHERE `response_id` IN (
    			SELECT
		          `response_id`
		        FROM `lpsf_response`
    			WHERE `form_id` = ?
		    )
		', $formId);
	    
	    $this->_getDb()->query('
			DELETE FROM `lpsf_response` 
			WHERE `form_id` = ?
		', $formId);
	}
}
