<?php

class LiquidPro_SimpleForms_DataWriter_FormDestination extends XenForo_DataWriter
{
	const DATA_DESTINATION_OPTION_DEFINITIONS = 'destinationOptions';	
	
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
	protected $_existingDataErrorPhrase = 'requested_form_destination_not_found';	
	
	/**
	 * The destination options to be updated. Use setDestinationOptions to manage this.
	 *
	 * @var array
	 */
	protected $_updateDestinationOptions = array();	
	
	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'lpsf_form_destination'		=> array(
				'form_destination_id'	=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'form_id'				=> array('type' => self::TYPE_UINT, 'required' => true),
				'destination_id'		=> array('type' => self::TYPE_UINT, 'required' => true),
				'name'					=> array('type' => self::TYPE_STRING, 'maxLength' => 75, 'required' => true),
				'active'				=> array('type' => self::TYPE_BOOLEAN, 'default' => 1, 'required' => true),
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
		if (!$id = $this->_getExistingPrimaryKey($data, 'form_destination_id'))
		{
			return false;
		}

		return array('lpsf_form_destination' => $this->_getFormModel()->getFormDestinationById($id));
	}
	
	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'form_destination_id = ' . $this->_db->quote($this->getExisting('form_destination_id'));
	}
	
	protected function _preDelete()
	{
		$db = $this->_db;
		$formDestinationId = $this->get('form_destination_id');
		$formDestinationIdQuoted = $db->quote($formDestinationId);
		
		$db->delete('lpsf_form_destination_option', "form_destination_id = $formDestinationIdQuoted");
			
		parent::_preDelete();
	}	
	
	protected function _postSave()
	{
		$this->updateDestinationOptions();
	
		$this->getModelFromCache('XenForo_Model_Permission')->rebuildPermissionCache();
	
		parent::_postSave();
	}	
	
	public function setDestinationOptions(array $destinationOptionValues, array $optionsShown = null)
	{
		$destinationModel = $this->_getDestinationModel();
		$optionModel = $this->_getDestinationOptionModel();
	
		// get the option definitions
		$groupedOptions = $optionModel->groupDestinationOptions($this->_getDestinationOptionDefinitions());
	
		if ($optionsShown === null)
		{
			$optionsShown = array();
			foreach ($destinationOptionValues as $destination)
			{
				$optionsShown = array_keys($destination);
			}
		}
		$finalValues = array();
		
		$destination = $destinationModel->getDestinationById($this->get('destination_id'));
		
		foreach ($optionsShown as $optionId)
		{
			if (!isset($groupedOptions[$destination['destination_id']][$optionId]))
			{
				continue;
			}

			$option = $groupedOptions[$destination['destination_id']][$optionId];
			
			$multiChoice = ($option['field_type'] == 'checkbox' || $option['field_type'] == 'multiselect');
			if ($multiChoice)
			{
				// multi selection - array
				$value = (isset($destinationOptionValues[$optionId]) && is_array($destinationOptionValues[$optionId]))
				? $destinationOptionValues[$optionId] : array();
			}
			else if ($option['field_type'] == 'array')
			{
				// remove blank values from an array
				foreach ($destinationOptionValues[$optionId] as $key => $choice)
				{
					if ($choice == '')
					{
						unset($destinationOptionValues[$optionId][$key]);
					}
				}

				$value = (isset($destinationOptionValues[$optionId]) && is_array($destinationOptionValues[$optionId]))
				? $destinationOptionValues[$optionId] : array();
			}
			else
			{
				// if an array, serialize it
				if (is_array($destinationOptionValues[$optionId]))
				{
					$value = serialize($destinationOptionValues[$optionId]);
				}
				else
				{
					// single selection - string
					$value = (isset($destinationOptionValues[$optionId]) ? strval($destinationOptionValues[$optionId]) : '');					
				}
			}

			$valid = $optionModel->verifyDestinationOptionValue($option, $value, $error);
			if (!$valid)
			{
				$this->error($error, "destination_options[$optionId]");
				continue;
			}

			$finalValues[$optionId] = $value;
		}
		
		// validate the destination as a whole
		if (method_exists($destination['handler_class'], 'VerifyDestination'))
		{
			$errors = call_user_func_array(array($destination['handler_class'], 'VerifyDestination'), array(&$groupedOptions[$destination['destination_id']], &$destinationOptionValues));
			if ($errors)
			{
				foreach ($errors AS $fieldName => $error)
				{
					$this->error($error, $fieldName);
					continue;
				}
			}
		}
		
		$this->_updateDestinationOptions = $finalValues + $this->_updateDestinationOptions;
	}	
	
	public function updateDestinationOptions()
	{
		if ($this->_updateDestinationOptions)
		{
			foreach ($this->_updateDestinationOptions as $optionId => $value)
			{
				if (is_array($value))
				{
					$value = serialize($value);
				}

				$this->_db->query('
						INSERT INTO lpsf_form_destination_option
						(form_destination_id, option_id, option_value)
						VALUES
						(?, ?, ?)
						ON DUPLICATE KEY UPDATE
						option_value = VALUES(option_value)
						', array($this->get('form_destination_id'), $optionId, $value));
			}
		}
	}	
	
	public function setFormDestinationXml($formDestination, $formId)
	{
		$formDestinationData = array(
			'destination_id' => (int)$formDestination['destination_id'],
			'form_id' => $formId,
			'name' => (string)$formDestination['name'],
			'active' => (int)$formDestination['active']
		);
		
		$this->bulkSet($formDestinationData);
		$this->save();
		
		// get the form id
		$formDestinationId = $this->get('form_destination_id');
		
		foreach ($formDestination->children() as $child)
		{
			switch ($child->getName())
			{
				case 'option':
					$destinationOptions = $child;
					break;
			}
		}
		
		// import destinations
		$this->setDestinationOptionsXml($destinationOptions, $formDestinationId);
		
		return $formDestinationId;
	}
	
	public function setDestinationOptionsXml($xml, $formDestinationId)
	{
		$finalValues = array();
		foreach ($xml->children() as $destinationOption)
		{
			$finalValues[(string)$destinationOption['option_id']] = (string)$destinationOption['option_value'];
		}
		
		$threadPoll = array();
		if (array_key_exists('thread_poll_question', $finalValues))
		{
		    $threadPoll['question'] = $finalValues['thread_poll_question'];
		    unset($finalValues['thread_poll_question']);
		}
		if (array_key_exists('thread_poll_choices', $finalValues))
		{
		    $threadPoll['responses'] = unserialize($finalValues['thread_poll_choices']);
		    unset($finalValues['thread_poll_choices']);
		}
		if (array_key_exists('thread_poll_multiple', $finalValues))
		{
		    $threadPoll['max_votes'] = (unserialize($finalValues['thread_poll_multiple']) !== array()) ? 0 : 1;
		    $threadPoll['max_votes_type'] = ($threadPoll['max_votes'] == 1) ? 'single' : 'unlimited';
		    unset($finalValues['thread_poll_multiple']);
		}
		if (array_key_exists('thread_poll_public_votes', $finalValues))
		{
		    $publicVotes = unserialize($finalValues['thread_poll_public_votes']);
		    $threadPoll['public_votes'] = ($publicVotes !== array()) ? 1 : 0;
		    unset($finalValues['thread_poll_public_votes']);
		}
		if ($threadPoll !== array())
		{
		    $finalValues['thread_poll'] = serialize($threadPoll);
		}
			
		$this->_updateDestinationOptions = $finalValues;
	
		$this->updateDestinationOptions($formDestinationId);
	}
	
	/**
	 * Fetch (and cache) destination option definitions
	 *
	 * @return array
	 */
	protected function _getDestinationOptionDefinitions()
	{
		$options = $this->getExtraData(self::DATA_DESTINATION_OPTION_DEFINITIONS);
	
		if (is_null($options))
		{
			$options = $this->_getDestinationOptionModel()->prepareDestinationOptions($this->_getDestinationOptionModel()->getDestinationOptions());
	
			$this->setExtraData(self::DATA_DESTINATION_OPTION_DEFINITIONS, $options);
		}
	
		return $options;
	}	
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected function _getFormModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Form');
	}	
	
	/**
	 * @return LiquidPro_SimpleForms_Model_DestinationOption
	 */
	protected function _getDestinationOptionModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_DestinationOption');
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Destination
	 */
	protected function _getDestinationModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Destination');
	}	
}