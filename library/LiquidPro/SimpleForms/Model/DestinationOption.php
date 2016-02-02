<?php

class LiquidPro_SimpleForms_Model_DestinationOption extends XenForo_Model
{
	/**
	 * Gets a destination option by ID.
	 *
	 * @param string $optionId
	 *
	 * @return array|false
	 */
	public function getOptionById($optionId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM lpsf_destination_option
			WHERE option_id = ?
		', $optionId);
	}

	/**
	 * Gets destination options that match the specified criteria.
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array [option id] => info
	 */
	public function getDestinationOptions(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause = $this->prepareDestinationOptionConditions($conditions, $fetchOptions);
		$joinOptions = $this->prepareDestinationOptionFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT lpsf_destination_option.*
				' . $joinOptions['selectFields'] . '
			FROM lpsf_destination_option
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause . '
			ORDER BY lpsf_destination_option.display_order
		', 'option_id');
	}
	
	/**
	 * Gets destination options for a form destination.
	 * 
	 * @param integer $formDestinationId
	 * 
	 * @return array [option id] => info
	 */
	public function getDestinationOptionsByFormDestinationId($formDestinationId)
	{
		return $this->fetchAllKeyed('
			SELECT lpsf_destination_option.*, lpsf_form_destination_option.option_value
			FROM lpsf_form_destination_option
			JOIN lpsf_destination_option
			  ON lpsf_destination_option.option_id = lpsf_form_destination_option.option_id
			WHERE lpsf_form_destination_option.form_destination_id = ?
			ORDER BY lpsf_destination_option.display_order', 
		'option_id', $formDestinationId);
	}
	
	public function getAttachmentsDestinationHandlers($formId)
	{
	    $destinations = $this->_getDb()->fetchAll('
            SELECT option_value, lpsf_form_destination.form_destination_id, lpsf_destination.handler_class
            FROM lpsf_form_destination
            JOIN lpsf_form_destination_option
              ON lpsf_form_destination_option.form_destination_id = lpsf_form_destination.form_destination_id
			JOIN lpsf_destination on lpsf_destination.destination_id = lpsf_form_destination.destination_id
            WHERE option_id LIKE \'%_enable_attachments\' 
              AND form_id = ?
	    ', $formId);
		if (empty($destinations))
		{
			return false;
		}

		$contentTypes = array();

		foreach($destinations as $destination)
		{
			$result = @unserialize($destination['option_value']);
			if ($result !== array() && !isset($contentTypes[$destination['form_destination_id']]))
			{
				$contentTypes[$destination['form_destination_id']] = $destination;
			}
		}

	    return $contentTypes;
	}

	/**
	 * Prepares a set of conditions to select destination options against.
	 *
	 * @param array $conditions List of conditions.
	 * @param array $fetchOptions The fetch options that have been provided. May be edited if criteria requires.
	 *
	 * @return string Criteria as SQL for where clause
	 */
	public function prepareDestinationOptionConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		/*
		if (!empty($conditions['form_destination_id']))
		{
			$sqlConditions[] = 'lpsf_destination_option.form_destination_id = ' . $db->quote($conditions['form_destination_id']);
		}
		*/
		
		if (!empty($conditions['destination_id']))
		{
			$sqlConditions[] = 'lpsf_destination_option.destination_id = ' . $db->quote($conditions['destination_id']);
		}
		
		return $this->getConditionsForClause($sqlConditions);
	}

	/**
	 * Prepares join-related fetch options.
	 *
	 * @param array $fetchOptions
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys.
	 */
	public function prepareDestinationOptionFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		$db = $this->_getDb();

		if (!empty($fetchOptions['valueFormId']))
		{
			$selectFields .= ',
				lpsf_form_destination_option.option_value';
			$joinTables .= '
				LEFT JOIN lpsf_form_destination_option ON
					(lpsf_form_destination_option.option_id = lpsf_destination_option.option_id AND lpsf_form_destination_option.form_id = ' . $db->quote($fetchOptions['valueFormId']) . ')';
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}

	/**
	 * Prepares a destination option for display.
	 *
	 * @param array $destinationOption
	 * @param boolean $getDestinationOptionChoices If true, gets the choice options for this destination option (as phrases)
	 * @param mixed $destinationOptionValue If not null, the value for the destination option; if null, pulled from option_value
	 *
	 * @return array Prepared destination option
	 */
	public function prepareDestinationOption(array $destinationOption, $getDestinationOptionChoices = false, $destinationOptionValue = null)
	{
		$destinationOption['isMultiChoice'] = ($destinationOption['field_type'] == 'checkbox' || $destinationOption['field_type'] == 'multiselect' || $destinationOption['field_type'] == 'array');

		if ($destinationOption['format_params'] !== '')
		{
			$destinationOption['format_params'] = unserialize($destinationOption['format_params']);
		}
		
		if ($destinationOptionValue === null && isset($destinationOption['option_value']))
		{
			$destinationOptionValue = $destinationOption['option_value'];
		}
		if ($destinationOption['isMultiChoice'])
		{
			if (is_string($destinationOptionValue))
			{
				$destinationOptionValue = @unserialize($destinationOptionValue);
			}
			else if (!is_array($destinationOptionValue))
			{
				$destinationOptionValue = array();
			}
		}
		
		if (is_string($destinationOptionValue))
		{
			$unserialize = @unserialize($destinationOptionValue);
			if ($unserialize)
			{
				$destinationOptionValue = $unserialize;
			}			
		}
		
		$destinationOption['option_value'] = $destinationOptionValue;
		
		$destinationOption['title'] = new XenForo_Phrase($this->getDestinationOptionTitlePhraseName($destinationOption['option_id']));
		$destinationOption['description'] = new XenForo_Phrase($this->getDestinationOptionDescriptionPhraseName($destinationOption['option_id']));

		$destinationOption['hasValue'] = ((is_string($destinationOptionValue) && $destinationOptionValue !== '') || (!is_string($destinationOptionValue) && $destinationOptionValue));

		if ($getDestinationOptionChoices)
		{
			$destinationOption['destinationOptionChoices'] = array();
			if (!$destinationOption['required'] && $destinationOption['field_type'] == 'radio')
			{
				$destinationOption['destinationOptionChoices'] = array(
					'' => new XenForo_Phrase('no_selection')
				);
			}
			$destinationOption['destinationOptionChoices'] += $this->getDestinationOptionChoices($destinationOption['option_id'], $destinationOption['field_choices']);
		}

		return $destinationOption;
	}

	/**
	 * Prepares a list of destination options for display.
	 *
	 * @param array $destinationOptions
	 * @param boolean $getDestinationOptionChoices If true, gets the choice options for these destination options (as phrases)
	 * @param array $destinationOptionValues List of values for the specified destination options; if skipped, pulled from option_value in array
	 *
	 * @return array
	 */
	public function prepareDestinationOptions(array $destinationOptions, $getDestinationOptionChoices = false, array $destinationOptionValues = array())
	{
		foreach ($destinationOptions AS &$destinationOption)
		{
			$value = isset($destinationOptionValues[$destinationOption['option_id']]) ? $destinationOptionValues[$destinationOption['option_id']] : null;
			$destinationOption = $this->prepareDestinationOption($destinationOption, $getDestinationOptionChoices, $value);
		}

		return $destinationOptions;
	}
	
	/**
	 * Groups destination options by their destination.
	 *
	 * @param array $destinationOptions
	 *
	 * @return array [destination id][key] => info
	 */
	public function groupDestinationOptions(array $destinationOptions)
	{
		$return = array();

		foreach ($destinationOptions AS $optionId => $destinationOption)
		{
			$return[$destinationOption['destination_id']][$optionId] = $destinationOption;
		}

		return $return;
	}

	/**
	 * Gets the destination option choices for the given destination option.
	 *
	 * @param string $optionId
	 * @param string|array $choices Serialized string or array of choices; key is choice ID
	 * @param boolean $master If true, gets the master phrase values; otherwise, phrases
	 *
	 * @return array Choices
	 */
	public function getDestinationOptionChoices($optionId, $choices, $master = false)
	{
		if (!is_array($choices))
		{
			$choices = ($choices ? @unserialize($choices) : array());
		}

		if (!$master)
		{
			foreach ($choices AS $value => &$text)
			{
				$text = new XenForo_Phrase($this->getDestinationOptionChoicePhraseName($optionId, $value));
			}
		}

		return $choices;
	}

	/**
	 * Verifies that the value for the specified destination option is valid.
	 *
	 * @param array $destinationOption
	 * @param mixed $value
	 * @param mixed $error Returned error message
	 *
	 * @return boolean
	 */
	public function verifyDestinationOptionValue(array $destinationOption, &$value, &$error = '')
	{
		$error = false;

		switch ($destinationOption['field_type'])
		{
			case 'textbox':
				$value = preg_replace('/\r?\n/', ' ', strval($value));
				// break missing intentionally

			case 'textarea':
				$value = trim(strval($value));

				if ($destinationOption['max_length'] && utf8_strlen($value) > $destinationOption['max_length'])
				{
					$error = new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', array('count' => $destinationOption['max_length']));
					return false;
				}

				$matched = true;

				if ($value !== '')
				{
					switch ($destinationOption['match_type'])
					{
						case 'number':
							$matched = preg_match('/^[0-9]+(\.[0-9]+)?/', $value);
							break;

						case 'alphanumeric':
							$matched = preg_match('/^[a-z0-9_]+$/i', $value);
							break;

						case 'email':
							$matched = Zend_Validate::is($value, 'EmailAddress');
							break;

						case 'url':
							if ($value === 'http://')
							{
								$value = '';
								break;
							}
							if (substr(strtolower($value), 0, 4) == 'www.')
							{
								$value = 'http://' . $value;
							}
							$matched = Zend_Uri::check($value);
							break;

						case 'regex':
							$matched = preg_match('#' . str_replace('#', '\#', $destinationOption['match_regex']) . '#sU', $value);
							break;

						case 'callback':
							$matched = call_user_func_array(
								array($destinationOption['match_callback_class'], $destinationOption['match_callback_method']),
								array($destinationOption, &$value, &$error)
							);

						default:
							// no matching
					}
				}

				if (!$matched)
				{
					if (!$error)
					{
						$error = new XenForo_Phrase('please_enter_value_that_matches_required_format');
					}
					return false;
				}
				break;

			case 'radio':
			case 'select':
				$choices = unserialize($destinationOption['field_choices']);
				$value = strval($value);

				if (!isset($choices[$value]))
				{
					$value = '';
				}
				break;
			case 'array':
				break;
			case 'checkbox':
			case 'multiselect':
				$choices = unserialize($destinationOption['field_choices']);
				if (!is_array($value))
				{
					$value = array();
				}

				$newValue = array();

				foreach ($value AS $key => $choice)
				{
					$choice = strval($choice);
					if (isset($choices[$choice]))
					{
						$newValue[$choice] = $choice;
					}
				}

				$value = $newValue;
				break;
			case 'callback':
				if ($destinationOption['match_type'] == 'callback')
				{
					$matched = call_user_func_array(
						array($destinationOption['match_callback_class'], $destinationOption['match_callback_method']),
						array($destinationOption, &$value, &$error)
					);	
	
					if (!$matched)
						return false;
				}
				break;
		}

		return true;
	}

	/**
	 * Gets the possible destination option types.
	 *
	 * @return array [type] => keys: value, label, hint (optional)
	 */
	public function getDestinationOptionTypes()
	{
		return array(
			'textbox' => array(
				'value' => 'textbox',
				'label' => new XenForo_Phrase('single_line_text_box')
			),
			'textarea' => array(
				'value' => 'textarea',
				'label' => new XenForo_Phrase('multi_line_text_box')
			),
			'select' => array(
				'value' => 'select',
				'label' => new XenForo_Phrase('drop_down_selection')
			),
			'radio' => array(
				'value' => 'radio',
				'label' => new XenForo_Phrase('radio_buttons')
			),
			'checkbox' => array(
				'value' => 'checkbox',
				'label' => new XenForo_Phrase('check_boxes')
			),
			'multiselect' => array(
				'value' => 'multiselect',
				'label' => new XenForo_Phrase('multiple_choice_drop_down_selection')
			),
			'array' => array(
				'value' => 'array',
				'label' => new XenForo_Phrase('array')
			)
		);
	}

	/**
	 * Maps destination options to their high level type "group". Field types can be changed only
	 * within the group.
	 *
	 * @return array [field type] => type group
	 */
	public function getUserFieldTypeMap()
	{
		return array(
			'textbox' => 'text',
			'textarea' => 'text',
			'radio' => 'single',
			'select' => 'single',
			'checkbox' => 'multiple',
			'multiselect' => 'multiple',
			'array' => 'multiple'
		);
	}

	/**
	 * Gets the destination option's title phrase name.
	 *
	 * @param string $optionId
	 *
	 * @return string
	 */
	public function getDestinationOptionTitlePhraseName($optionId)
	{
		return 'destination_option_' . $optionId;
	}

	/**
	 * Gets the destination option's description phrase name.
	 *
	 * @param string $optionId
	 *
	 * @return string
	 */
	public function getDestinationOptionDescriptionPhraseName($optionId)
	{
		return 'destination_option_' . $optionId . '_desc';
	}

	/**
	 * Gets a destination option choices's phrase name.
	 *
	 * @param string $optionId
	 * @param string $choice
	 *
	 * @return string
	 */
	public function getDestinationOptionChoicePhraseName($optionId, $choice)
	{
		return 'destination_option_' . $optionId . '_choice_' . $choice;
	}

	/**
	 * Gets a destination option's master title phrase text.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getDestinationOptionMasterTitlePhraseValue($id)
	{
		$phraseName = $this->getDestinationOptionTitlePhraseName($id);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	/**
	 * Gets a destination option's master description phrase text.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getDestinationOptionMasterDescriptionPhraseValue($id)
	{
		$phraseName = $this->getDestinationOptionDescriptionPhraseName($id);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	/**
	 * Gets the destination option values for the given form.
	 *
	 * @param integer $formId
	 *
	 * @return array [option id] => value (may be string or array)
	 */
	public function getDestinationOptionValues($formId)
	{
		$destinationOptions = $this->_getDb()->fetchAll('
			SELECT lpsf_form_destination_option.*, lpsf_destination_option.field_type
			FROM lpsf_form_destination_option
			INNER JOIN lpsf_destination_option ON (lpsf_destination_option.option_id = lpsf_form_destination_option.option_id)
			WHERE lpsf_form_destination_option.form_id = ?
		', $formId);

		$values = array();
		foreach ($destinationOptions AS $destinationOption)
		{
			if ($destinationOption['field_type'] == 'checkbox' || $destinationOption['field_type'] == 'multiselect')
			{
				$values[$destinationOption['field_id']] = @unserialize($destinationOption['field_value']);
			}
			else
			{
				$values[$destinationOption['field_id']] = $destinationOption['field_value'];
			}
		}

		return $values;
	}
	
	/**
	 * Gets all destination option entries in the specified form.
	 *
	 * @param string $destinationOptionId
	 * @param array Fetch options
	 *
	 * @return array [destination option id] => info
	 */
	public function getDestinationOptionsInForm($formId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareDestinationOptionFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed('
			SELECT lpsf_form_destination_option.*
				' . $joinOptions['selectFields'] . '
			FROM lpsf_form_destination_option
				' . $joinOptions['joinTables'] . '
			WHERE
				lpsf_form_destination_option.form_id = ?
		', 'option_id', $formId);
	}		
	
	/**
	 * Appends the destination option XML to a given DOM element.
	 *
	 * @param DOMElement $rootNode Node to append all form elements to
	 * @param string $formId Form ID to be exported
	 */
	public function appendDestinationOptionFormXml(DOMElement $rootNode, $formDestinationId)
	{
		$destinationOptions = $this->getDestinationOptionsByFormDestinationId($formDestinationId);
		ksort($destinationOptions);
		$document = $rootNode->ownerDocument;
		
		foreach ($destinationOptions as $destinationOption)
		{
			$destinationOptionNode = $document->createElement('option');
			$destinationOptionNode->setAttribute('option_id', $destinationOption['option_id']);
			$destinationOptionNode->setAttribute('option_value', $destinationOption['option_value']);
			
			$rootNode->appendChild($destinationOptionNode);
		}
	}		

	/**
	 * Rebuilds the cache of destination option info for front-end display
	 *
	 * @return array
	 */
	public function rebuildDestinationOptionCache()
	{
		$cache = array();
		foreach ($this->getDestinationOptions() AS $optionId => $destinationOption)
		{
			$cache[$optionId] = XenForo_Application::arrayFilterKeys($destinationOption, array(
				'option_id',
				'field_type'
			));

			foreach (array('display_template') AS $optionalDestinationOption)
			{
				if (!empty($destinationOption[$optionalDestinationOption]))
				{
					$cache[$optionId][$optionalDestinationOption] = $destinationOption[$optionalDestinationOption];
				}
			}
		}

		$this->_getDataRegistryModel()->set('destinationOptionsInfo', $cache);
		return $cache;
	}

	/**
	 * @return XenForo_Model_Phrase
	 */
	protected function _getPhraseModel()
	{
		return $this->getModelFromCache('XenForo_Model_Phrase');
	}
}