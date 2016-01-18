<?php

class LiquidPro_SimpleForms_DataWriter_Response extends XenForo_DataWriter
{
	const DATA_FIELD_DEFINITIONS = 'fields';
	
	protected $_redirectUrl = '';
	
	/**
	 * The fields to be updated. Use setFields to manage this.
	 *
	 * @var array
	 */
	protected $_updateFields = array();		
	
	/**
	 * Indicator whether or not to execute destinations.
	 * 
	 * @var bool
	 */
	protected $_executeDestinations = true;
	
	/**
	 * Holds the temporary hash used to pull attachments and associate them with this message.
	 *
	 * @var string
	 */
	const DATA_ATTACHMENT_HASH = 'attachmentHash';	
	
	/**
	 * Returns all lpsf_response fields, plus form-specific fields
	 */
	protected function _getFields()
	{
		return array(
			'lpsf_response' => array(
				'response_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'form_id' => array('type' => self::TYPE_UINT),
				'user_id' => array('type' => self::TYPE_UINT),
				'response_date' => array('type' => self::TYPE_UINT),		
				'ip_address' => array('type' => self::TYPE_STRING, 'default' => '', 'required' => true, 'maxLength' => 45)
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
		$responseModel = $this->_getResponseModel();
		
		if (!$responseId = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		$response = $responseModel->getResponseById($responseId);
		if (!$response)
		{
			return false;
		}

		return $this->getTablesDataFromArray($response);
	}	
	
	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'response_id = ' . $this->_db->quote($this->getExisting('response_id'));
	}	
	
	public function preSave($checkAttachments = true)
	{
		if ($this->_preSaveCalled)
		{
			return;
		}
		
		if ($checkAttachments)
		{
			$formModel = $this->_getFormModel();
			$form = $formModel->getFormById($this->get('form_id'));
			
			$attachmentModel = $this->_getAttachmentModel();
			$attachments = $attachmentModel->getAttachmentsByTempHash($this->getExtraData(LiquidPro_SimpleForms_DataWriter_Response::DATA_ATTACHMENT_HASH));
			
			if ($form['require_attachment'] && $attachments === array())
			{
				$this->error(new XenForo_Phrase('lpsf_attachment_required'));
			}
		}
		
		$this->_preSaveCalled = true;
	}
	
	protected function _postSave()
	{
		$this->updateFields();
		
		if ($this->_executeDestinations)
		{
			$destinationModel = $this->_getDestinationModel();
			$destinationOptionModel = $this->_getDestinationOptionModel();
			
			// attachment handling
			$attachmentHash = $this->getExtraData(self::DATA_ATTACHMENT_HASH);
			if ($attachmentHash)
			{
				$attachmentModel = $this->_getAttachmentModel();
				$attachments = $attachmentModel->getAttachmentsByTempHash($attachmentHash);				
			}
			
			// redirect handling
			$form = $this->_getFormModel()->getFormById($this->get('form_id'));
			$redirectDestination = null;
			if ($form['redirect_method'] == 'destination')
			{
				$redirectDestination = $form['redirect_destination'];
			}

			// get the destinations for a form and loop through them to handle them if necessary
			$destinations = $destinationModel->getDestinationsByFormId($form['form_id']);
			foreach ($destinations AS $destination)
			{
				// only handle active destinations
				if ($destination['active'])
				{
					// get the destination options
					$destinationOptions = $destinationOptionModel->prepareDestinationOptions($destinationOptionModel->getDestinationOptionsByFormDestinationId($destination['form_destination_id']), true);
					
					// create the destination
					$formDestination = new $destination['handler_class']($form['form_id'], $this->_updateFields, $destinationOptions, null, $this->get('response_id'));

					// handle attachments
					if ($destinationOptionModel->getAttachmentsEnabled($form['form_id']) && $attachments)
					{
						// create new temporary hash
						$newAttachmentHash = md5(uniqid('', true));
							
						// duplicate the attachment so others can use it
						foreach ($attachments as $attachmentId => $attachment)
						{
							$duplicateAttachmentId = $attachmentModel->insertTemporaryAttachment($attachment['data_id'], $newAttachmentHash);
						}
							
						$formDestination->setAttachmentHash($attachmentId, $duplicateAttachmentId, $newAttachmentHash);
					}
					
					// save the destination
					$formDestination->save();
					
					// get the redirect url from the destination
					if (isset($redirectDestination) && $destination['form_destination_id'] == $redirectDestination)
					{
						$redirectMethod = $destination['redirect_method'];
						$this->_redirectUrl = $formDestination->$redirectMethod();
					}					
				}
			}	
			
			// delete the temporary attachment hash
			if ($attachmentHash)
			{
				foreach ($attachments as $attachmentId => $attachment)
				{
					$dw = XenForo_DataWriter::create('XenForo_DataWriter_Attachment');
					$dw->setExistingData($attachment);	
					$dw->delete();
				}
			}
		}
		
		parent::_postSave();
	}	
	
	protected function _preDelete()
	{
		$db = $this->_db;
		$responseId = $this->get('response_id');
		$responseIdQuoted = $db->quote($responseId);
		
		$db->delete('lpsf_response_field', "response_id = $responseIdQuoted");
		
		parent::_preDelete();
	}
	
	public function disableDestinations()
	{
		$this->_executeDestinations = false;
	}
	
	public function enableDestinations()
	{
		$this->_executeDestinations = true;
	}
	
	public function getRedirectUrl()
	{
		return $this->_redirectUrl;
	}
	
	public function setFields(array $fieldValues, array $fieldsShown = null)
	{
		$fieldModel = $this->_getFieldModel();
		$fields = $fieldModel->prepareFields($this->_getFieldDefinitions());
		
		$finalValues = array();
		
		if (!is_array($fieldsShown))
		{
			$fieldsShown = array_keys($fieldValues);
		}
		
		foreach ($fieldsShown AS $fieldId)
		{
			if (!isset($fields[$fieldId]))
			{
				continue;
			}
			
			$field = $fields[$fieldId];
			
			$multiChoice = ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect');
			if ($multiChoice)
			{
				// multi selection - array
				$value = (isset($fieldValues[$fieldId]) && is_array($fieldValues[$fieldId]))
					? $fieldValues[$fieldId] : array();
			}
			else
			{
				// single selection - string
				$value = (isset($fieldValues[$fieldId]) ? strval($fieldValues[$fieldId]) : '');
			}
			
			$valid = $fieldModel->verifyFieldValue($field, $value, $error);
			if (!$valid)
			{
				$this->error($error, "field[$fieldId]");
				continue;
			}

			$finalValues[$fieldId] = $field;
			$finalValues[$fieldId]['field_value'] = $value;
		}
		
		$this->_updateFields = $finalValues;
	}	
	
	public function updateFields()
	{
		if ($this->_updateFields)
		{
			foreach ($this->_updateFields AS $fieldId => $field)
			{
				$value = $field['field_value']; 
				
				if (is_array($value))
				{
					$value = serialize($value);
				}
				
				$this->_db->query('
					INSERT INTO lpsf_response_field
						(field_id, response_id, field_value)
					VALUES
						(?, ?, ?)
					ON DUPLICATE KEY UPDATE
						field_value = VALUES(field_value)
				', array($fieldId, $this->get('response_id'), $value));
			}
		}
	}	
	
	/**
	 * Fetch (and cache) field definitions
	 *
	 * @return array
	 */
	protected function _getFieldDefinitions()
	{
		$formId = $this->get('form_id');
		$fields = $this->getExtraData(self::DATA_FIELD_DEFINITIONS);

		if (is_null($fields))
		{
			$fields = $this->_getFieldModel()->getFields(array('form_id' => $formId));

			$this->setExtraData(self::DATA_FIELD_DEFINITIONS, $fields);
		}

		return $fields;
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Field
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Field');
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
	 * @return LiquidPro_SimpleForms_Model_Response
	 */
	protected function _getResponseModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Response');
	}	
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected function _getFormModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Form');
	}		
	
	/**
	 * @return XenForo_Model_Attachment
	 */
	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}		
}