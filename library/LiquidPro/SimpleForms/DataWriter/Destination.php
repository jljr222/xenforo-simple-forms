<?php

class LiquidPro_SimpleForms_DataWriter_Destination extends XenForo_DataWriter
{
	/**
	 * Constant for extra data that holds the value for the phrase
	 * that is the title of this destination.
	 *
	 * This value is required on inserts.
	 *
	 * @var string
	 */
	const DATA_NAME = 'phraseName';
	
	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'requested_destination_not_found';	
	
	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'lpsf_destination' => array(
				'destination_id'        => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'name'					=> array('type' => self::TYPE_STRING, 'maxLength' => 50),
				'display_order'         => array('type' => self::TYPE_UINT, 'default' => 1),
				'handler_class'			=> array('type' => self::TYPE_STRING, 'maxLength' => 75),
			)
		);
	}	
	
	/**
	* Gets the actual existing data out of data that was passed in. See parent for explanation.
	*
	* @param mixed
	*
	* @return array|false
	*/
	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'destination_id'))
		{
			return false;
		}

		return array('lpsf_destination' => $this->_getDestinationModel()->getDestinationById($id));
	}
	
	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'destination_id = ' . $this->_db->quote($this->getExisting('destination_id'));
	}
	
	/**
	 * Pre-save behaviors.
	 */
	protected function _preSave()
	{
		if ($this->isChanged('handler_class'))
		{
			$class = $this->get('handler_class');

			if (!$class)
			{
				$this->set('handler_class', '');
			}
			else if (!XenForo_Application::autoload($class))
			{
				$this->error(new XenForo_Phrase('please_enter_valid_handler_class'), 'handler_class');
			}
		}

		$namePhrase = $this->getExtraData(self::DATA_NAME);
		if ($namePhrase !== null && strlen($namePhrase) == 0)
		{
			$this->error(new XenForo_Phrase('please_enter_valid_name'), 'name');
		}
	}	
	
	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		$destinationId = $this->get('destination_id');

		if ($this->isUpdate() && $this->isChanged('destination_id'))
		{
			$this->_renameMasterPhrase(
				$this->_getNamePhraseName($this->getExisting('destinationId')),
				$this->_getNamePhraseName($destinationId)
			);
		}

		$namePhrase = $this->getExtraData(self::DATA_NAME);
		if ($namePhrase !== null)
		{
			$this->_insertOrUpdateMasterPhrase(
				$this->_getNamePhraseName($destinationId), $namePhrase,
				'', array('global_cache' => 1)
			);
		}

		$this->_rebuildDestinationCache();
	}	
	
	/**
	 * Post-delete behaviors.
	 */
	protected function _postDelete()
	{
		$destinationId = $this->get('destination_id');

		$this->_deleteMasterPhrase($this->_getNamePhraseName($destinationId));
		
		$this->_db->delete('lpsf_form_destination_option', 'option_id IN (SELECT option_id FROM lpsf_destination_option WHERE destination_id = ' . $this->_db->quote($destinationId) . ')');
		$this->_db->delete('lpsf_destination_option', 'destination_id = ' . $this->_db->quote($destinationId));
	}	
	
	/**
	 * Gets the name of the name phrase for this destination.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function _getNamePhraseName($id)
	{
		return $this->_getDestinationModel()->getDestinationNamePhraseName($id);
	}

	protected function _rebuildDestinationCache()
	{
		return $this->_getDestinationModel()->rebuildDestinationCache();
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Destination
	 */
	protected function _getDestinationModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Destination');
	}	
}