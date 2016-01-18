<?php

class LiquidPro_SimpleForms_DataWriter_Field extends XenForo_DataWriter
{
	/**
	 * Constant for extra data that holds the value for the phrase
	 * that is the title of this field.
	 *
	 * This value is required on inserts.
	 *
	 * @var string
	 */
	const DATA_TITLE = 'phraseTitle';

	/**
	 * Constant for extra data that holds the value for the phrase
	 * that is the description of this field.
	 *
	 * @var string
	 */
	const DATA_DESCRIPTION = 'phraseDescription';

	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'requested_field_not_found';

	/**
	 * List of choices, if this is a choice field. Interface to set field_choices properly.
	 *
	 * @var null|array
	 */
	protected $_fieldChoices = null;

	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'lpsf_field' => array(
				'field_id'              => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'type'					=> array('type' => self::TYPE_STRING, 'default' => 'user', 'allowedValues' => array('user', 'template', 'global')),
				'parent_field_id'		=> array('type' => self::TYPE_UINT),
				'form_id'				=> array('type' => self::TYPE_UINT),
			    'page_id'               => array('type' => self::TYPE_UINT),
				'display_order'         => array('type' => self::TYPE_UINT),
				'field_name'			=> array('type' => self::TYPE_STRING, 'maxLength' => 25, 'required' => true, 'verification' => array('LiquidPro_SimpleForms_DataWriter_Helper_Field', 'VerifyFieldName')),
				'field_type'            => array('type' => self::TYPE_STRING, 'default' => 'textbox',
					'allowedValues'     => array('textbox', 'textarea', 'select', 'radio', 'checkbox', 'multiselect', 'wysiwyg', 'date', 'rating', 'datetime', 'time')
				),
				'field_choices'         => array('type' => self::TYPE_SERIALIZED, 'default' => ''),
				'match_type'            => array('type' => self::TYPE_STRING, 'default' => 'none',
					'allowedValues'     => array('none', 'number', 'alphanumeric', 'email', 'url', 'regex', 'callback')
				),
				'match_regex'           => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 250),
				'match_callback_class'  => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'match_callback_method' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75),
				'max_length'            => array('type' => self::TYPE_UINT),
				'min_length'            => array('type' => self::TYPE_UINT),
				'required'              => array('type' => self::TYPE_BOOLEAN),
				'hide_title'			=> array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'default_value'			=> array('type' => self::TYPE_STRING, 'default' => '', 'verification' => array('LiquidPro_SimpleForms_DataWriter_Helper_Field', 'VerifyDefaultValue')),
				'placeholder'			=> array('type' => self::TYPE_STRING, 'default' => ''),
				'active'				=> array('type' => self::TYPE_BOOLEAN),
				'pre_text'				=> array('type' => self::TYPE_STRING, 'default' => ''),
				'post_text'				=> array('type' => self::TYPE_STRING, 'default' => '')
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
		if (!$id = $this->_getExistingPrimaryKey($data, 'field_id'))
		{
			return false;
		}

		return array('lpsf_field' => $this->_getFieldModel()->getFieldById($id));
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'field_id = ' . $this->_db->quote($this->getExisting('field_id'));
	}

	/**
	 * Sets the choices for this field.
	 *
	 * @param array $choices [choice key] => text
	 */
	public function setFieldChoices(array $choices)
	{
		foreach ($choices AS $value => &$text)
		{
			if ($value === '')
			{
				unset($choices[$value]);
				continue;
			}

			$text = strval($text);

			if ($text === '')
			{
				$this->error(new XenForo_Phrase('please_enter_text_for_each_choice'), 'field_choices');
				return false;
			}

			if (preg_match('#[^a-z0-9_]#i', $value))
			{
				$this->error(new XenForo_Phrase('please_enter_an_id_using_only_alphanumeric'), 'field_choices');
				return false;
			}

			if (strlen($value) > 25)
			{
				$this->error(new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', array('count' => 25)));
				return false;
			}
		}

		$this->_fieldChoices = $choices;
		$this->set('field_choices', $choices);

		return true;
	}

	/**
	 * Pre-save behaviors.
	 */
	protected function _preSave()
	{
		if (!$this->get('parent_field_id'))
		{
			if ($this->isChanged('match_callback_class') || $this->isChanged('match_callback_method'))
			{
				$class = $this->get('match_callback_class');
				$method = $this->get('match_callback_method');
	
				if (!$class || !$method)
				{
					$this->set('match_callback_class', '');
					$this->set('match_callback_method', '');
				}
				else if (!XenForo_Application::autoload($class) || !method_exists($class, $method))
				{
					$this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
				}
			}
	
			if ($this->isUpdate() && $this->isChanged('field_type'))
			{
				$typeMap = $this->_getFieldModel()->getFieldTypeMap();
				if ($typeMap[$this->get('field_type')] != $typeMap[$this->getExisting('field_type')])
				{
					$this->error(new XenForo_Phrase('you_may_not_change_field_to_different_type_after_it_has_been_created'), 'field_type');
				}
			}

			if (in_array($this->get('field_type'), array('select', 'radio', 'checkbox', 'multiselect')))
			{
				if (($this->isInsert() && !$this->_fieldChoices) || (is_array($this->_fieldChoices) && !$this->_fieldChoices))
				{
					$this->error(new XenForo_Phrase('please_enter_at_least_one_choice'), 'field_choices', false);
				}
			}
			else
			{
				$this->setFieldChoices(array());
			}
		}
		
		if ($this->get('type') != 'global' && $this->get('type') != 'template')
		{
			$titlePhrase = $this->getExtraData(self::DATA_TITLE);
			if ($titlePhrase !== null && strlen($titlePhrase) == 0)
			{
				$this->error(new XenForo_Phrase('please_enter_valid_title'), 'title');
			}
		}
		
		if ($this->isChanged('field_name'))
		{
			$fieldModel = $this->_getFieldModel();
			if ($fieldModel->checkFieldNameExists($this->get('field_name'), $this->get('type'), $this->get('form_id')))
			{
				$this->error(new XenForo_Phrase('field_ids_must_be_unique'));
			}
		}
		
		// if we're adding a field, set the page id
		if ($this->isInsert())
		{
		    $this->set('page_id', $this->_getPageModel()->getPageIdByFormId($this->get('form_id')));
		}
	}

	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		$fieldId = $this->get('field_id');
		
		// handle global fields
		if ($this->get('type') == 'global')
		{
			// update all children
			$this->_getFieldModel()->updateGlobalFields($this->get('field_id'));
			
			$children = $this->_getFieldModel()->getFields(array('parent_field_id' => $this->get('field_id')));
			
			// update all children phrases
			foreach ($children as $child)
			{
				$dw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Field');
				$dw->setExistingData($child, true);
				
                $dw->setFieldChoices($this->_fieldChoices);
                
                $dw->updateFieldChoicePhrases();
			}
		}
		else
		{
			$this->updatePhrases();
		}
						
		$this->_rebuildFieldCache();
	}
	
	public function updateFieldChoicePhrases()
	{
		if (is_array($this->_fieldChoices))
		{
			$this->_deleteExistingChoicePhrases();
		
			foreach ($this->_fieldChoices AS $choice => $text)
			{
				$this->_insertOrUpdateMasterPhrase(
					$this->_getChoicePhraseName($this->get('field_id'), $choice), $text,
					'', array('global_cache' => 1)
				);
			}
		}
	}
	
	public function updatePhrases()
	{
		// update field choice phrases
		$this->updateFieldChoicePhrases();
		
		// update the title phrase
		$titlePhrase = $this->getExtraData(self::DATA_TITLE);
		if ($titlePhrase !== null)
		{
			$this->_insertOrUpdateMasterPhrase(
				$this->_getTitlePhraseName($this->get('field_id')), $titlePhrase,
				'', array('global_cache' => 1)
			);
		}
		
		// update the description phrase
		$descriptionPhrase = $this->getExtraData(self::DATA_DESCRIPTION);
		if ($descriptionPhrase !== null)
		{
			$this->_insertOrUpdateMasterPhrase(
				$this->_getDescriptionPhraseName($this->get('field_id')), $descriptionPhrase
			);
		}
	}
	
	public function setFieldXml($field, $formId)
	{
		$fieldData = array(
			'form_id' => $formId,
			'type' => 'user',
			'display_order' => (int)$field['display_order'],
			'field_name' => (string)$field['field_name'],
			'field_type' => (string)$field['field_type'],
			'match_type' => (string)$field['match_type'],
			'match_regex' => (string)$field['match_regex'],
			'match_callback_class' => (string)$field['match_callback_class'],
			'match_callback_method' => (string)$field['match_callback_method'],
			'max_length' => (int)$field['max_length'],
			'min_length' => (int)$field['min_length'],
			'required' => (int)$field['required'],
			'hide_title' => (int)$field['hide_title'],
			'default_value' => (string)$field['default_value'],
			'placeholder' => (string)$field['placeholder'],
			'active' => (int)$field['active'],
			'pre_text' => (string)$field['pre_text'],
			'post_text' => (string)$field['post_text']
		);
		
		$this->bulkSet($fieldData);
		
		// set the title
		$this->setExtraData(
			LiquidPro_SimpleForms_DataWriter_Field::DATA_TITLE,
			(string)$field['title']
		);
		
		// set the description
		$this->setExtraData(
			LiquidPro_SimpleForms_DataWriter_Field::DATA_DESCRIPTION,
			(string)$field['description']
		);
	
		$this->setFieldChoices(unserialize((string)$field['field_choices']));
			
		$this->save();		
	}
	
	/**
	 * Pre-delete behaviors.
	 */	
	protected function _preDelete()
	{
		$db = $this->_db;
		$options = XenForo_Application::getOptions();
		
		// unassociate global field
		if ($this->get('type') == 'global')
		{
			$fieldModel = new LiquidPro_SimpleForms_Model_Field();
			$associatedFields = $fieldModel->getFields(array('parent_field_id' => $this->get('field_id')));

			switch ($options->deleteGlobalFieldsHandling)
			{
				// delete all the associated fields
				case 'delete':
					{
						foreach ($associatedFields as $associatedFieldId => $associatedField)
						{
							$quotedFieldId = $db->quote($associatedFieldId);
								
							// delete the responses for that field
							$db->delete('lpsf_response_field', "field_id = $quotedFieldId");
						
							// delete the field
							$db->delete('lpsf_field', "field_id = $quotedFieldId");
						}						
					}	
					break;
				// convert all the associated fields to user fields
				case 'convert':
					{
						foreach ($associatedFields as $associatedFieldId => $associatedField)
						{
							$dw = new LiquidPro_SimpleForms_DataWriter_Field();
							$dw->setExistingData($associatedField, true);
								
							// unassociate from the global field
							$dw->set('parent_field_id', null);
								
							// override global settings
							$dw->set('field_name', $this->get('field_name'));
							$dw->set('field_type', $this->get('field_type'));
							$dw->set('field_choices', $this->get('field_choices'));
							$dw->set('match_type', $this->get('match_type'));
							$dw->set('match_regex', $this->get('match_regex'));
							$dw->set('match_callback_class', $this->get('match_callback_class'));
							$dw->set('match_callback_method', $this->get('match_callback_method'));
							$dw->set('max_length', $this->get('max_length'));
							$dw->set('hide_title', $this->get('hide_title'));
							$dw->set('default_value', $this->get('default_value'));
							$dw->set('placeholder', $this->get('placeholder'));
							$dw->set('min_length', $this->get('min_length'));
							$dw->set('pre_text', $this->get('pre_text'));
							$dw->set('post_text', $this->get('post_text'));
								
							// save the associated field
							$dw->save();
						}						
					}
					break;
				// prevent the field from being deleted
				case 'prevent':
					{
						if (count($associatedFields) > 0)
						{
							$this->error(new XenForo_Phrase('fields_associated_with_global_field'));
						}						
					}
					break;
			}
		}
		
		$fieldId = $this->get('field_id');
		$fieldIdQuoted = $db->quote($fieldId);
		
		$db->delete('lpsf_response_field', "field_id = $fieldIdQuoted");
		
		parent::_preDelete();
	}
	
	
	/**
	 * Post-delete behaviors.
	 */
	protected function _postDelete()
	{
		$fieldId = $this->get('field_id');

		if ($this->get('type') != 'global')
		{
			$this->_deleteMasterPhrase($this->_getTitlePhraseName($fieldId));
			$this->_deleteMasterPhrase($this->_getDescriptionPhraseName($fieldId));
		}
		$this->_deleteExistingChoicePhrases();
	}

	/**
	 * Deletes all phrases for existing choices.
	 */
	protected function _deleteExistingChoicePhrases()
	{
		$fieldId = $this->get('field_id');

		$existingChoices = $this->getExisting('field_choices');
		if ($existingChoices && $existingChoices = @unserialize($existingChoices))
		{
			foreach ($existingChoices AS $choice => $text)
			{
				$this->_deleteMasterPhrase($this->_getChoicePhraseName($fieldId, $choice));
			}
		}
	}

	/**
	 * Gets the name of the title phrase for this field.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function _getTitlePhraseName($id)
	{
		return $this->_getFieldModel()->getFieldTitlePhraseName($id);
	}

	/**
	 * Gets the name of the description phrase for this field.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function _getDescriptionPhraseName($id)
	{
		return $this->_getFieldModel()->getFieldDescriptionPhraseName($id);
	}

	/**
	 * Gets the name of the choice phrase for a value in this field.
	 *
	 * @param string $fieldId
	 * @param string $choice
	 *
	 * @return string
	 */
	protected function _getChoicePhraseName($fieldId, $choice)
	{
		return $this->_getFieldModel()->getFieldChoicePhraseName($fieldId, $choice);
	}

	protected function _rebuildFieldCache()
	{
		return $this->_getFieldModel()->rebuildFieldCache();
	}

	/**
	 * @return LiquidPro_SimpleForms_Model_Field
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Field');
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Page
	 */
	protected function _getPageModel()
	{
	    return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Page');
	}
}