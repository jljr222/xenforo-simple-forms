<?php

class LiquidPro_SimpleForms_ControllerAdmin_Field extends LiquidPro_SimpleForms_ControllerAdmin_Abstract
{
	/**
	 * Gets the add/edit form response for a field.
	 *
	 * @param array $field
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	protected function _getFieldAddEditResponse(array $field)
	{
		$fieldModel = $this->_getFieldModel();
		$formModel = $this->_getFormModel();
		
		if (isset($field['form_id']))
		{
		    $form = $formModel->getFormById($field['form_id']);
		}
		
		$typeMap = $fieldModel->getFieldTypeMap();
		$validFieldTypes = $fieldModel->getFieldTypes();

		if (!empty($field['field_id']))
		{
			$masterTitle = $fieldModel->getFieldMasterTitlePhraseValue($field['field_id']);
			$masterDescription = $fieldModel->getFieldMasterDescriptionPhraseValue($field['field_id']);

			$existingType = $typeMap[$field['field_type']];
			foreach ($validFieldTypes AS $typeId => $type)
			{
				if ($typeMap[$typeId] != $existingType)
				{
					unset($validFieldTypes[$typeId]);
				}
			}
		}
		else
		{
			$masterTitle = '';
			$masterDescription = '';
			$existingType = false;
		}

		$viewParams = array(
			'field' => $field,
			'masterTitle' => $masterTitle,
			'masterDescription' => $masterDescription,
			'masterFieldChoices' => $fieldModel->getFieldChoices($field['field_id'], $field['field_choices'], true),
			'validFieldTypes' => $validFieldTypes,
			'fieldTypeMap' => $typeMap,
			'existingType' => $existingType
		);
		
		if (isset($form))
		{
		    $viewParams['form'] = $form;
		}
		
		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Field_Edit', 'lpsf_field_edit', $viewParams);
	}
	
	/**
	 * Displays template fields.
	 * 
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionTemplates()
	{
		$fieldModel = $this->_getFieldModel();
		$templates = $fieldModel->getFields(array(
			'type' => 'template'	
		));
		
		$viewParams = array(
			'templates' => $fieldModel->prepareFields($templates)	
		);
		
		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Field_TemplatesList', 'lpsf_field_templates_list', $viewParams);
	}
	
	/**
	 * Displays global fields.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionGlobals()
	{
		$fieldModel = $this->_getFieldModel();
		$globals = $fieldModel->getFields(array(
			'type' => 'global'
		));
	
		$viewParams = array(
			'globals' => $fieldModel->prepareFields($globals)
		);
	
		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Field_GlobalsList', 'lpsf_field_globals_list', $viewParams);
	}	

	/**
	 * Displays form to add a field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionAdd()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);
		
		$fieldModel = $this->_getFieldModel();
		$fieldTypes = $fieldModel->getCountByType();
		
		$options = array();
		$options[] = array(
		    'value' => 'user',
		    'label' => new XenForo_Phrase('field'),
		    'selected' => true
		);
		
		// if global fields exist, include in the types
		if (array_key_exists('global', $fieldTypes))
		{
			$options[] = array(
			    'value' => 'global',
			    'label' => new XenForo_Phrase('global_field')
			);
		}
		
		// if template fields exist, include in the types
		if (array_key_exists('template', $fieldTypes))
		{
			$options[] = array(
			    'value' => 'template',
			    'label' => new XenForo_Phrase('template_field') 
			);
		}
		
		// if there are no options other than user, just send them to the add field page
		if (!$type && count($options) == 1)
		{
			$type = 'user';
		}
		
		// association a field to a form
		if ($formId && $type)
		{
			$default = array(
				'field_id' => null,
				'form_id' => $this->_input->filterSingle('form_id', XenForo_Input::UINT),
				'display_order' => $this->_getFieldModel()->getGreatestDisplayOrderByFormId($formId) + 10,
				'field_type' => 'textbox',
				'field_choices' => '',
				'match_type' => 'none',
				'match_regex' => '',
				'match_callback_class' => '',
				'match_callback_method' => '',
				'max_length' => 0,
				'min_length' => 0,
				'required' => 0,
				'type' => $type,
				'active' => 1,
				'pre_text' => '',
				'post_text' => ''
			);
			
			switch ($type)
			{
			    case 'global':
		        {
		            return $this->responseReroute('LiquidPro_SimpleForms_ControllerAdmin_Form', 'add-global-field');
		        }
			    case 'template':
		        {
		            return $this->responseReroute('LiquidPro_SimpleForms_ControllerAdmin_Form', 'add-template-field');
		        }
			    default:
		        {
		            return $this->_getFieldAddEditResponse($default);
		        }
			}
		}
		
		// adding a global/template field
		else if (!$formId && $type)
		{
		    $default = array(
	            'field_id' => null,
	            'field_type' => 'textbox',
	            'field_choices' => '',
	            'match_type' => 'none',
	            'match_regex' => '',
	            'match_callback_class' => '',
	            'match_callback_method' => '',
	            'max_length' => 0,
		    	'min_length' => 0,
	            'type' => $type,
		    	'pre_text' => '',
		    	'post_text' => ''
		    );
		    
		    if ($type != 'global')
		    {
		    	$default['display_order'] = 1;
		    	$default['required'] = 0;
		    	$default['active'] = 1;
		    }
		    
		    return $this->_getFieldAddEditResponse($default);
		}
		
		// association type
		else
		{
			$viewParams = array(
				'formId' => $formId,
				'options' => $options
			);
			
			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Field_AddType', 'lpsf_field_add_type', $viewParams);
		}
	}

	/**
	 * Displays form to edit a field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEdit()
	{
		$field = $this->_getFieldOrError($this->_input->filterSingle('field_id', XenForo_Input::STRING));
		return $this->_getFieldAddEditResponse($field);
	}

	/**
	 * Saves a field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionSave()
	{
		$fieldId = $this->_input->filterSingle('field_id', XenForo_Input::UINT);
		
		$dw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Field');
		if ($fieldId)
		{
			$dw->setExistingData($fieldId);
		}
		
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);
		$fieldType = $this->_input->filterSingle('field_type', XenForo_Input::STRING);
		
		switch ($type)
		{
			case 'global':
			{
				$dwInput = $this->_input->filter(array(
					'type' => XenForo_Input::STRING,
					'field_name' => XenForo_Input::STRING,
					'field_type' => XenForo_Input::STRING,
					'match_type' => XenForo_Input::STRING,
					'match_regex' => XenForo_Input::STRING,
					'match_callback_class' => XenForo_Input::STRING,
					'match_callback_method' => XenForo_Input::STRING,
					'max_length' => XenForo_Input::UINT,
					'min_length' => XenForo_Input::UINT,
					'placeholder' => XenForo_Input::STRING,
				));

				// set the redirect location
				$redirect = XenForo_Link::buildAdminLink('fields/globals') . $this->getLastHash("field_{$fieldId}");
				
				break;
			}
			case 'template':
			{
				$dwInput = $this->_input->filter(array(
					'type' => XenForo_Input::STRING,
					'field_name' => XenForo_Input::STRING,
					'display_order' => XenForo_Input::UINT,
					'field_type' => XenForo_Input::STRING,
					'match_type' => XenForo_Input::STRING,
					'match_regex' => XenForo_Input::STRING,
					'match_callback_class' => XenForo_Input::STRING,
					'match_callback_method' => XenForo_Input::STRING,
					'max_length' => XenForo_Input::UINT,
					'min_length' => XenForo_Input::UINT,
					'hide_title' => XenForo_Input::UINT,
				    'default_value' => ($fieldType == 'datetime' && $fieldId) ? XenForo_Input::ARRAY_SIMPLE : XenForo_Input::STRING,
					'placeholder' => XenForo_Input::STRING				
				));

				// set the redirect location
				$redirect = XenForo_Link::buildAdminLink('fields/templates') . $this->getLastHash("field_{$fieldId}");
				
				break;
			}
			case 'user':
			{
				// common inputs for user fields (associated template, associated global, user)
				$dwInput = $this->_input->filter(array(
					'type' => XenForo_Input::STRING,
					'field_name' => XenForo_Input::STRING,
					'form_id' => XenForo_Input::UINT,
					'display_order'	=> XenForo_Input::UINT,
					'required' => XenForo_Input::UINT,
					'hide_title' => XenForo_Input::UINT,
					'default_value' => ($fieldType == 'datetime' && $fieldId) ? XenForo_Input::ARRAY_SIMPLE : XenForo_Input::STRING,
					'active' => XenForo_Input::UINT
				));
				
				// normal user field or associated template field
				if (!$dw->get('parent_field_id'))
				{
					$dwInput = $dwInput + $this->_input->filter(array(
						'field_type' => XenForo_Input::STRING,
						'match_type' => XenForo_Input::STRING,
						'match_regex' => XenForo_Input::STRING,
						'match_callback_class' => XenForo_Input::STRING,
						'match_callback_method' => XenForo_Input::STRING,
						'max_length' => XenForo_Input::UINT,
						'min_length' => XenForo_Input::UINT,
						'placeholder' => XenForo_Input::STRING			
					));
				}

				// set the redirect location
				$redirect = XenForo_Link::buildAdminLink('forms/edit', array('form_id' => $formId)) . '#fields';
				
				break;
			}
		}
		
		if (array_key_exists('default_value', $dwInput) &&  is_array($dwInput['default_value']))
		{
		    $dwInput['default_value'] = $dwInput['default_value']['date'] . ' ' . $dwInput['default_value']['time'];
		}
		
		$dw->bulkSet($dwInput);
		
		if ($type != 'global')
		{
			// set the pre text
			$preText = $this->getHelper('Editor')->getMessageText('pre_text', $this->_input);
			if ($preText != '')
			{
				$preText = XenForo_Helper_String::autoLinkBbCode($preText);
			}
			$dw->set('pre_text', $preText);
			
			// set the post text
			$postText = $this->getHelper('Editor')->getMessageText('post_text', $this->_input);
			if ($postText != '')
			{
				$postText = XenForo_Helper_String::autoLinkBbCode($postText);
			}
			$dw->set('post_text', $postText);
			
			// set the default value
			if ($dw->get('field_type') == 'wysiwyg')
			{
				$defaultValue = $this->getHelper('Editor')->getMessageText('default_value', $this->_input);
				$defaultValue = XenForo_Helper_String::autoLinkBbCode($defaultValue);
				
				$dw->set('default_value', $defaultValue);
			}
			
			// set the title and description
			$title = $this->_input->filterSingle('title', XenForo_Input::STRING);
			$description = $this->_input->filterSingle('description', XenForo_Input::STRING);
		}
		else
		{
			$title = '';
			$description = '';
		}
		
		// set the title and description
		$dw->setExtraData(LiquidPro_SimpleForms_DataWriter_Field::DATA_TITLE, $title);
		$dw->setExtraData(LiquidPro_SimpleForms_DataWriter_Field::DATA_DESCRIPTION, $description);

		// set the field choices, but not if it is a global field associated to a form
		if (!$dw->get('parent_field_id'))
		{
			$fieldChoices = $this->_input->filterSingle('field_choice', XenForo_Input::STRING, array('array' => true));
			$fieldChoicesText = $this->_input->filterSingle('field_choice_text', XenForo_Input::STRING, array('array' => true));
			$fieldChiocesCombined = array();
			foreach ($fieldChoices AS $key => $choice)
			{
				if (isset($fieldChoicesText[$key]))
				{
					$fieldChiocesCombined[$choice] = $fieldChoicesText[$key];
				}
			}
	
			$dw->setFieldChoices($fieldChiocesCombined);
		}
		
		// save the field
		$dw->save();

		// redirect to the appropriate location
		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, $redirect);		
	}

	/**
	 * Deletes a field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDelete()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		
		if ($this->isConfirmedPost())
		{
		    $fieldId = $this->_input->filterSingle('field_id', XenForo_input::UINT);
		    $fieldModel = $this->_getFieldModel();
		    $field = $fieldModel->getFieldById($fieldId);
		    
			if ($formId)
			{
				$redirect = XenForo_Link::buildAdminLink('forms/edit', array('form_id' => $formId)) . '#fields';
			}
			else
			{
				switch ($field['type'])
				{
					case 'template':
					{
							$redirect = XenForo_Link::buildAdminLink('fields/templates');
							break;
					}
					case 'global':
					{
							$redirect = XenForo_Link::buildAdminLink('fields/globals');
							break;
					}
				}				
			}	    
		    
			return $this->_deleteData('LiquidPro_SimpleForms_DataWriter_Field', 'field_id', $redirect);
		}
		else
		{
			$field = $this->_getFieldOrError($this->_input->filterSingle('field_id', XenForo_Input::STRING));

			$viewParams = array(
				'field' => $this->_getFieldModel()->prepareField($field)
			);
			
			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Field_Delete', 'lpsf_field_delete', $viewParams);
		}
	}
	
	public function actionCopy()
	{
		$fieldId = $this->_getFieldModel()->copyField(
			$this->_input->filterSingle('field_id', XenForo_Input::UINT)
		);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('fields/edit', array('field_id' => $fieldId))
		);
	}
	
	/**
	 * Disables the specified form.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDisable()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));
	
		$fieldId = $this->_input->filterSingle('field_id', XenForo_Input::UINT);
		return $this->_switchFormActiveStateAndGetResponse($fieldId, 0);
	}
	
	/**
	 * Enables the specified field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEnable()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));
	
		$fieldId = $this->_input->filterSingle('field_id', XenForo_Input::UINT);
		return $this->_switchFormActiveStateAndGetResponse($fieldId, 1);
	}	
	
	/**
	 * Moves a specified field up.
	 * 
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionMoveUp()
	{
		$fieldId = $this->_input->filterSingle('field_id', XenForo_Input::UINT);
		return $this->_moveField($fieldId, 'up');
	}
	
	/**
	 * Changes the display order for a field.
	 * 
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDisplayOrder()
	{
		$order = $this->_input->filterSingle('order', XenForo_Input::ARRAY_SIMPLE);
		
		$this->_assertPostOnly();
		$this->_getFieldModel()->massUpdateDisplayOrder($order);
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('smilies')
		);
	}
	
	/**
	 * Moves a specified field down.
	 * 
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionMoveDown()
	{
		$fieldId = $this->_input->filterSingle('field_id', XenForo_Input::UINT);
		return $this->_moveField($fieldId, 'down');
	}
	
	/**
	 * Moves a specified field to a position
	 */
	protected function _moveField($fieldId, $direction)
	{
		$fieldModel = $this->_getFieldModel();
		$fieldModel->moveFieldDisplayOrder($fieldId, $direction);
		
		$field = $fieldModel->getFieldById($fieldId);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('forms/edit', array('form_id' => $field['form_id'])) . '#fields'
		);		
	}
	
	/**
	 * Helper to switch the active state for a field and get the controller response.
	 *
	 * @param string $fieldId Field ID
	 * @param integer $activeState 0 or 1
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	protected function _switchFormActiveStateAndGetResponse($fieldId, $activeState)
	{
		$dw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Field');
		$dw->setExistingData($fieldId);
		$dw->set('active', $activeState);
		$dw->save();
		
		$formId = $dw->get('form_id');
	
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('forms/edit', array('form_id' => $formId)) . '#fields'
		);
	}	
}