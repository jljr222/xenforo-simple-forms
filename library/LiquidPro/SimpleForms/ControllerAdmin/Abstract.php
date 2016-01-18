<?php

class LiquidPro_SimpleForms_ControllerAdmin_Abstract extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('form');
	}	
	
	/**
	 * Gets the specified destination or throws an exception.
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function _getDestinationOrError($id)
	{
		$destination = $this->getRecordOrError(
				$id, $this->_getDestinationModel(), 'getDestinationById',
				'requested_destination_not_found'
		);
	
		return $this->_getDestinationModel()->prepareDestination($destination);
	}	
	
	/**
	 * Gets the specified field or throws an exception.
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function _getFieldOrError($id)
	{
		$field = $this->getRecordOrError(
			$id, 
			$this->_getFieldModel(), 
			'getFieldById',
			'requested_field_not_found'
		);
		
		// get the parent field
		if ($field['parent_field_id'])
		{
			$parentField = $this->getRecordOrError(
				$field['parent_field_id'], 
				$this->_getFieldModel(), 
				'getFieldById',
				'requested_field_not_found'	
			); 

			// defines a list of keys to overwrite with global field values
			$overwrittenKeys = array(
				'field_type',
				'field_choices',
				'match_type',
				'match_regex',
				'match_callback_class',
				'match_callback_method',
				'max_length',
				'min_length',
				'placeholder'
			);
			
			foreach (array_keys($parentField) as $key)
			{
				if (in_array($key, $overwrittenKeys))
				{
					$field[$key] = $parentField[$key];
				}
			}
		}
	
		return $this->_getFieldModel()->prepareField($field);
	}	
	
	/**
	 * Gets the specified form or throws an exception.
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function _getFormOrError($id)
	{
		return $this->getRecordOrError(
				$id, $this->_getFormModel(), 'getFormById',
				'requested_form_not_found'
		);
	}	
	
	/**
	 * Gets the specified field or throws an exception.
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function _getFormDestinationOrError($id)
	{
		return $this->getRecordOrError(
				$id, $this->_getFormModel(), 'getFormDestinationById',
				'requested_form_destination_not_found'
		);
	}	
	
	/**
	 * Gets the specified response or throws an exception.
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function _getResponseOrError($id)
	{
		return $this->getRecordOrError(
				$id, $this->_getResponseModel(), 'getResponseById',
				'requested_response_not_found'
		);
	}	
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Destination
	 */
	protected function _getDestinationModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Destination');
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_DestinationOption
	 */
	protected function _getDestinationOptionModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_DestinationOption');
	}	
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Field
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Field');
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected function _getFormModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Form');
	}	
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Response
	 */
	protected function _getResponseModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Response');
	}	
	
	/**
	 * @return LiquidPro_SimpleForms_DataWriter_FormDestination
	 */
	protected function _getDataWriter()
	{
		return XenForo_DataWriter::create($this->_dataWriterName);
	}	
}