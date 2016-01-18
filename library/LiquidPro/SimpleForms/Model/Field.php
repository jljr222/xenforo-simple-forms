<?php

class LiquidPro_SimpleForms_Model_Field extends XenForo_Model
{
	/**
	 * Gets a field by ID.
	 *
	 * @param string $fieldId
	 *
	 * @return array|false
	 */
	public function getFieldById($fieldId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM lpsf_field
			WHERE field_id = ?
		', $fieldId);
	}

	public function getFieldByTypeAndName($type, $name)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM `lpsf_field`
			WHERE `type` = ? AND `field_name` = ?
		', array($type, $name));
	}
	
	public function getFieldByFormIdAndName($formId, $name)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM `lpsf_field`
			WHERE `form_id` = ? AND `field_name` = ?
		', array($formId, $name));
	}
	
	public function getGreatestDisplayOrderByFormId($formId)
	{
		return $this->_getDb()->fetchOne('
			SELECT COALESCE(MAX(display_order), 0)
			FROM `lpsf_field`
			WHERE `form_id` = ?
		', $formId);
	}

	/**
	 * Gets fields that match the specified criteria.
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array [field id] => info
	 */
	public function getFields(array $conditions = array(), array $fetchOptions = array(), array $limitOptions = array())
	{
		$whereClause = $this->prepareFieldConditions($conditions, $fetchOptions);
		$joinOptions = $this->prepareFieldFetchOptions($fetchOptions);
		$limit = $this->prepareFieldLimitOptions($limitOptions);
		
		return $this->fetchAllKeyed('
			SELECT lpsf_field.*
				' . $joinOptions['selectFields'] . '
			FROM lpsf_field
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause . '
			ORDER BY lpsf_field.display_order
			' . $limit . '
		', 'field_id');
	}
	
	public function moveFieldDisplayOrder($fieldId, $direction)
	{
		switch ($direction)
		{
			case 'up':
			{
				$displayOrder = '`field`.`display_order` - 1';
				$where = '`field`.`display_order` < `current`.`display_order`';
				$orderBy = '`field`.`display_order` DESC';
				break;
			}
			case 'down':
			{
				$displayOrder = '`field`.`display_order` + 1';
				$where = '`field`.`display_order` > `current`.`display_order`';
				$orderBy = '`field`.`display_order` ASC';					
				break;
			}
		}
		
		$sql = '
			UPDATE `lpsf_field` SET `display_order` = IFNULL((
				SELECT * FROM (
					SELECT
						(SELECT
							CASE WHEN (' . $displayOrder . ') <= 0
								THEN 1
								ELSE (' . $displayOrder . ')
							END
						FROM `lpsf_field` `field`
						WHERE ' . $where . '
							AND `field`.`form_id` = `current`.`form_id`
						ORDER BY ' . $orderBy . '
						LIMIT 1)
					FROM `lpsf_field` `current`
					WHERE `current`.`field_id` = ?
				) x
			), display_order)
			WHERE `field_id` = ?				
		';		
		
		$this->_getDb()->query($sql, array($fieldId, $fieldId));
	}
	
	/**
	 * Gets the number of each type of field.
	 * 
	 * @return [type] => count
	 */
	public function getCountByType()
	{
		return $this->fetchAllKeyed('
			SELECT `type`, COUNT(*) AS count
			FROM `lpsf_field`
			GROUP BY `type`
		', 'type');
	}
	
	public function prepareFieldLimitOptions($limitOptions = array())
	{
		$limitStr = '';
		
		if (array_key_exists('limit', $limitOptions))
		{
			$limitStr = 'LIMIT ' . $limitOptions['limit'];
		}
		
		return $limitStr;
	}
	
	/**
	 * Gets fields and values for a specific response
	 * 
	 * @param int $responseId
	 * 
	 * @return array [field id] => info
	 */
	public function getFieldsByResponseId($responseId)
	{
		return $this->fetchAllKeyed('
			SELECT field.*, response_field.field_value
			FROM lpsf_response_field response_field
			JOIN
				lpsf_field field ON field.field_id = response_field.field_id
			WHERE response_field.response_id = ?
			ORDER BY field.display_order
		', 'field_id', $responseId);
	}

	/**
	 * Gets the field values for the given form.
	 *
	 * @param integer $formId
	 *
	 * @return array [field id] => value (may be string or array)
	 */
	public function getFieldValues($formId)
	{
		$fields = $this->_getDb()->fetchAll('
			SELECT response_field.*, field.field_type
			FROM lpsf_response_field AS response_field
			JOIN lpsf_field AS field ON (field.field_id = response_field.field_id)
			WHERE field.form_id = ?
		', $formId);
		
		foreach ($fields AS &$field)
		{
			if ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect')
			{
				$field['field_value'] = @unserialize($field['field_value']);
			}
		}
		
		return $fields;
	}
	
	/**
	 * Checks to see if a field name already exists.
	 * 
	 * @param string $fieldName
	 * @param integer $formId
	 * 
	 * @return boolean true if exists, false if does not exist
	 */
	public function checkFieldNameExists($fieldName, $type, $formId)
	{
		$bind = array();
		$sql = "SELECT COUNT(*) FROM lpsf_field WHERE field_name = ? AND type = ?";
		$bind[] = $fieldName;
		$bind[] = $type;
		
		if ($type == 'user')
		{
			$sql .= " AND form_id = ?";
			$bind[] = $formId;
		}
		
		if ($this->_getDb()->fetchOne($sql, $bind) == 0)
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Updates global field's child records
	 * 
	 * @param integer $fieldId
	 * 
	 * @return void
	 */
	public function updateGlobalFields($fieldId)
	{
		$this->_getDb()->query("
			UPDATE `lpsf_field` `field`
			JOIN `lpsf_field` `global`
				ON `global`.`field_id` = `field`.`parent_field_id`
			SET
				`field`.`field_type` = `global`.`field_type`
				,`field`.`field_choices` = `global`.`field_choices`
				,`field`.`match_type` = `global`.`match_type`
				,`field`.`match_regex` = `global`.`match_regex`
				,`field`.`match_callback_class` = `global`.`match_callback_class`
				,`field`.`match_callback_method` = `global`.`match_callback_method`
				,`field`.`min_length` = `global`.`min_length`
				,`field`.`max_length` = `global`.`max_length`
				,`field`.`placeholder` = `global`.`placeholder`
			WHERE `global`.`field_id` = ? 
		", array($fieldId));
	}
	
	/**
	 * Prepares a set of conditions to select fields against.
	 *
	 * @param array $conditions List of conditions.
	 * @param array $fetchOptions The fetch options that have been provided. May be edited if criteria requires.
	 *
	 * @return string Criteria as SQL for where clause
	 */
	public function prepareFieldConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		if (!empty($conditions['form_id']))
		{
			$sqlConditions[] = 'lpsf_field.form_id = ' . $db->quote($conditions['form_id']);
		}
		
		if (!empty($conditions['parent_field_id']))
		{
			$sqlConditions[] = 'lpsf_field.parent_field_id = ' . $db->quote($conditions['parent_field_id']);
		}
		
		if (array_key_exists('type', $conditions))
		{
			if (is_array($conditions['type']))
			{
				$sqlConditions[] = "lpsf_field.type IN (" . $db->quote($conditions['type']) . ")";
			}
			else
			{
				$sqlConditions[] = "lpsf_field.type = " . $db->quote($conditions['type']);
			}
		}
		
		if (array_key_exists('display_order', $conditions))
		{
			if (is_array($conditions['display_order']))
			{
				$sqlConditions[] = '`lpsf_field`.`display_order` ' . $conditions['display_order'][0] . ' ' . $conditions['display_order'][1];
			}
		}
		
		if ($conditions === array())
		{
			$sqlConditions[] = "type = 'user'";
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
	public function prepareFieldFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		$db = $this->_getDb();

		if (!empty($fetchOptions['valueUserId']))
		{
			$selectFields .= ',
				field_value.field_value';
			$joinTables .= '
				LEFT JOIN xf_user_field_value AS field_value ON
					(field_value.field_id = user_field.field_id AND field_value.user_id = ' . $db->quote($fetchOptions['valueUserId']) . ')';
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}

	/**
	 * Prepares a field for display.
	 *
	 * @param array $field
	 * @param boolean $getFieldChoices If true, gets the choice options for this field (as phrases)
	 * @param mixed $fieldValue If not null, the value for the field; if null, pulled from field_value
	 *
	 * @return array Prepared field
	 */
	public function prepareField(array $field, $getFieldChoices = false, $fieldValue = null)
	{
		$field['isMultiChoice'] = ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect');

		if ($fieldValue === null && isset($field['field_value']))
		{
			$fieldValue = $field['field_value'];
		}
		if ($field['isMultiChoice'])
		{
			if (is_string($fieldValue))
			{
				$fieldValue = @unserialize($fieldValue);
			}
			else if (!is_array($fieldValue))
			{
				$fieldValue = array();
			}
		}
		$field['field_value'] = $fieldValue;

		if ($field['type'] != 'global')
		{
			$field['title'] = new XenForo_Phrase($this->getFieldTitlePhraseName($field['field_id']));
			$field['description'] = new XenForo_Phrase($this->getFieldDescriptionPhraseName($field['field_id']));
		}
		else
		{
			$field['title'] = $field['field_name'];
			$field['description'] = '';
		}

		$field['hasValue'] = ((is_string($fieldValue) && $fieldValue !== '') || (!is_string($fieldValue) && $fieldValue));

		if ($getFieldChoices)
		{
			$field['fieldChoices'] = $this->getFieldChoices($field['field_id'], $field['field_choices']);
		}

		return $field;
	}

	/**
	 * Prepares a list of fields for display.
	 *
	 * @param array $fields
	 * @param boolean $getFieldChoices If true, gets the choice options for these fields (as phrases)
	 * @param array $fieldValues List of values for the specified fields; if skipped, pulled from field_value in array
	 *
	 * @return array
	 */
	public function prepareFields(array $fields, $getFieldChoices = false, array $fieldValues = array())
	{
		foreach ($fields AS &$field)
		{
			$value = isset($fieldValues[$field['field_id']]) ? $fieldValues[$field['field_id']] : null;
			$field = $this->prepareField($field, $getFieldChoices, $value);
		}

		return $fields;
	}

	/**
	 * Gets the field choices for the given field.
	 *
	 * @param string $fieldId
	 * @param string|array $choices Serialized string or array of choices; key is choide ID
	 * @param boolean $master If true, gets the master phrase values; otherwise, phrases
	 *
	 * @return array Choices
	 */
	public function getFieldChoices($fieldId, $choices, $master = false)
	{
		if (!is_array($choices))
		{
			$choices = ($choices ? @unserialize($choices) : array());
		}

		if (!$master)
		{
			foreach ($choices AS $value => &$text)
			{
				$text = new XenForo_Phrase($this->getFieldChoicePhraseName($fieldId, $value));
			}
		}

		return $choices;
	}

	/**
	 * Verifies that the value for the specified field is valid.
	 *
	 * @param array $field
	 * @param mixed $value
	 * @param mixed $error Returned error message
	 *
	 * @return boolean
	 */
	public function verifyFieldValue(array $field, &$value, &$error = '')
	{
		$error = false;

		switch ($field['field_type'])
		{
			case 'textbox':
				$value = preg_replace('/\r?\n/', ' ', strval($value));
				// break missing intentionally
			
			case 'wysiwyg':
					// break missing intentionally
				
			case 'textarea':
				$value = trim(strval($value));

				if ($field['max_length'] && utf8_strlen($value) > $field['max_length'])
				{
					$error = new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', array('count' => $field['max_length']));
					return false;
				}
				
				if ($field['min_length'] && utf8_strlen($value) < $field['min_length'])
				{
					$error = new XenForo_Phrase('please_enter_Value_using_x_characters_or_more', array('count' => $field['min_length']));
					return false;
				}

				$matched = true;

				if ($value !== '')
				{
					switch ($field['match_type'])
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
							$matched = preg_match('#' . str_replace('#', '\#', $field['match_regex']) . '#sU', $value);
							break;

						case 'callback':
							$matched = call_user_func_array(
								array($field['match_callback_class'], $field['match_callback_method']),
								array($field, &$value, &$error)
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
				$choices = unserialize($field['field_choices']);
				$value = strval($value);

				if (!isset($choices[$value]))
				{
					$value = '';
				}
				break;

			case 'checkbox':
			case 'multiselect':
				$choices = unserialize($field['field_choices']);
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
			case 'datetime':
			    if ($value != '')
			    {
			        $temp = explode(' ', $value);
			        if (count($temp) <> 2)
			        {
			            $error = new XenForo_Phrase('lpsf_please_enter_a_datetime_in_format');
			            return false;
			        }
			         
			        $defaultValue = array(
			            'date' => $temp[0],
			            'time' => $temp[1]
			        );
			     
			        // validate
			        $dateTimeStr = $defaultValue['date'] . ' ' . $defaultValue['time'];
			        $dateTime = DateTime::createFromFormat('Y-m-d g:ia', $dateTimeStr);
			    
			        if (!$dateTime)
			        {
			            $error = new XenForo_Phrase('lpsf_please_enter_a_datetime_in_format');
			            return false;
			        }
			    
			        $value = $dateTimeStr;
			    }
			    break;
			case 'time':
		        $dateTime = DateTime::createFromFormat('g:ia', $value);
		        if (!$dateTime)
		        {
		            $error = new XenForo_Phrase('lpsf_please_enter_a_time_in_format');
		            return false;
		        }
			    break;
			    
		}
		
		if (($value === '' || $value === array()) && $field['required'] == 1)
		{
			$error = new XenForo_Phrase('please_enter_value_for_required_field_x', array('field' => new XenForo_Phrase($this->getFieldMasterTitlePhraseValue($field['field_id']))));
			return false;			
		}

		return true;
	}

	/**
	 * Gets the possible field types.
	 *
	 * @return array [type] => keys: value, label, hint (optional)
	 */
	public function getFieldTypes()
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
			'wysiwyg' => array(
				'value' => 'wysiwyg',
				'label' => new XenForo_Phrase('wysiwyg')
			),
			'date' => array(
				'value' => 'date',
				'label' => new XenForo_Phrase('lpsf_date')
			),
			'rating' => array(
				'value' => 'rating',
				'label' => new XenForo_Phrase('lpsf_rating')
			),
		    'datetime' => array(
		        'value' => 'datetime',
		        'label' => new XenForo_Phrase('lpsf_datetime')
		    ),
		    'time' => array(
		        'value' => 'time',
		        'label' => new XenForo_Phrase('lpsf_time')
		    )		    
		);
	}

	/**
	 * Maps fields to their high level type "group". Field types can be changed only
	 * within the group.
	 *
	 * @return array [field type] => type group
	 */
	public function getFieldTypeMap()
	{
		return array(
			'textbox' => 'text',
			'textarea' => 'text',
			'radio' => 'single',
			'select' => 'single',
			'checkbox' => 'multiple',
			'multiselect' => 'multiple',
			'wysiwyg' => 'text',
			'date' => 'text',
			'rating' => 'rating',
		    'datetime' => 'text',
		    'time' => 'text'
		);
	}

	/**
	 * Gets the field's title phrase name.
	 *
	 * @param string $fieldId
	 *
	 * @return string
	 */
	public function getFieldTitlePhraseName($fieldId)
	{
		return 'field_' . $fieldId;
	}

	/**
	 * Gets the field's description phrase name.
	 *
	 * @param string $fieldId
	 *
	 * @return string
	 */
	public function getFieldDescriptionPhraseName($fieldId)
	{
		return 'field_' . $fieldId . '_desc';
	}

	/**
	 * Gets a field choices's phrase name.
	 *
	 * @param string $fieldId
	 * @param string $choice
	 *
	 * @return string
	 */
	public function getFieldChoicePhraseName($fieldId, $choice)
	{
		return 'field_' . $fieldId . '_choice_' . $choice;
	}

	/**
	 * Gets a field's master title phrase text.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getFieldMasterTitlePhraseValue($id)
	{
		$phraseName = $this->getFieldTitlePhraseName($id);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	/**
	 * Gets a field's master description phrase text.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getFieldMasterDescriptionPhraseValue($id)
	{
		$phraseName = $this->getFieldDescriptionPhraseName($id);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	/**
	 * Rebuilds the cache of field info for front-end display
	 *
	 * @return array
	 */
	public function rebuildFieldCache()
	{
		$cache = array();
		foreach ($this->getFields() AS $fieldId => $field)
		{
			$cache[$fieldId] = XenForo_Application::arrayFilterKeys($field, array(
				'field_id',
				'field_type'
			));
		}

		$this->_getDataRegistryModel()->set('fieldsInfo', $cache);
		return $cache;
	}
	
	/**
	 * Gets all field entries in the specified form.
	 *
	 * @param string $fieldId
	 * @param array Fetch options
	 *
	 * @return array [field id] => info
	 */
	public function getFieldsInForm($formId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareFieldFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed('
			SELECT lpsf_field.*
				' . $joinOptions['selectFields'] . '
			FROM lpsf_field
				' . $joinOptions['joinTables'] . '
			WHERE
				lpsf_field.form_id = ?
		', 'field_name', $formId);
	}	
	
	/**
	 * Appends the form field XML to a given DOM element.
	 *
	 * @param DOMElement $rootNode Node to append all form elements to
	 * @param string $formId Form ID to be exported
	 */
	public function appendFieldFormXml(DOMElement $rootNode, $formId)
	{
		$fields = $this->getFieldsInForm($formId);
		ksort($fields);
		$document = $rootNode->ownerDocument;
		
		foreach ($fields as $field)
		{
			$field = $this->prepareField($field);
			
			$fieldNode = $document->createElement('field');
			$fieldNode->setAttribute('title', $field['title']);
			$fieldNode->setAttribute('description', $field['description']);
			$fieldNode->setAttribute('display_order', $field['display_order']);
			$fieldNode->setAttribute('field_name', $field['field_name']);
			$fieldNode->setAttribute('field_type', $field['field_type']);
			$fieldNode->setAttribute('field_choices', $field['field_choices']);
			$fieldNode->setAttribute('match_type', $field['match_type']);
			$fieldNode->setAttribute('match_regex', $field['match_regex']);
			$fieldNode->setAttribute('match_callback_class', $field['match_callback_class']);
			$fieldNode->setAttribute('match_callback_method', $field['match_callback_method']);
			$fieldNode->setAttribute('min_length', $field['min_length']);
			$fieldNode->setAttribute('max_length', $field['max_length']);
			$fieldNode->setAttribute('required', $field['required']);
			$fieldNode->setAttribute('hide_title', $field['hide_title']);
			$fieldNode->setAttribute('default_value', $field['default_value']);
			$fieldNode->setAttribute('placeholder', $field['placeholder']);
			$fieldNode->setAttribute('active', $field['active']);
			$fieldNode->setAttribute('pre_text', $field['pre_text']);
			$fieldNode->setAttribute('post_text', $field['post_text']);
			
			$rootNode->appendChild($fieldNode);
		}
	}
	
	/**
	 * Takes an array of display_order => [field_id, form_id]
	 * and updates all fields (used for the drag/drop UI)
	 *
	 * @param array $order
	 */
	public function massUpdateDisplayOrder(array $order)
	{
		$sqlOrder = '';
		$sqlParent = '';
	
		$db = $this->_getDb();
	
		foreach ($order AS $displayOrder => $data)
		{
			$fieldId = $db->quote((int)$data[0]);
			$formId = $db->quote((int)$data[1]);
	
			$sqlOrder .= "WHEN $fieldId THEN " . $db->quote((int)$displayOrder * 10) . "\n";
		}
		
		$db->query('
			UPDATE `lpsf_field` SET
			`display_order` = CASE `field_id`
			' . $sqlOrder . '
				ELSE 0 END
			WHERE `form_id` = ' . $db->quote((int)$formId)
		);
	}
	
	/**
	 * Copies a field on a form
	 * 
	 * @param int $fieldId
	 */
	public function copyField($fieldId)
	{
		$field = $this->_getDb()->fetchRow('SELECT * FROM `lpsf_field` WHERE `field_id` = ?', $fieldId);
		$field['field_name'] .= '_' . XenForo_Application::$time;
		$field['display_order'] += 10;
		$field['field_id'] = null;
		
		$this->_getDb()->insert('lpsf_field', $field);

		$newFieldId = $this->_getDb()->lastInsertId('lpsf_field');
		
		// save the field to make sure caches and phrases get updated
		$fieldModel = XenForo_Model::create('LiquidPro_SimpleForms_Model_Field');
		$field = $fieldModel->getFieldById($newFieldId);
		
		$fieldDw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Field');
		$fieldDw->setExistingData($field);
		$fieldDw->setExtraData(LiquidPro_SimpleForms_DataWriter_Field::DATA_TITLE, new XenForo_Phrase($this->getFieldTitlePhraseName($fieldId)));
		$fieldDw->setExtraData(LiquidPro_SimpleForms_DataWriter_Field::DATA_DESCRIPTION, new XenForo_Phrase($this->getFieldDescriptionPhraseName($fieldId)));
		$fieldDw->save();
		
		return $newFieldId;
	}

	/**
	 * @return XenForo_Model_Phrase
	 */
	protected function _getPhraseModel()
	{
		return $this->getModelFromCache('XenForo_Model_Phrase');
	}
}