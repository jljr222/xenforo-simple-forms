<?php

class LiquidPro_SimpleForms_ControllerAdmin_Form extends LiquidPro_SimpleForms_ControllerAdmin_Abstract
{
	/**
	 * Name of the DataWriter that will handle this node type
	 *
	 * @var string
	 */
	protected $_dataWriterName = 'LiquidPro_SimpleForms_DataWriter_Form';
	
	public function actionIndex()
	{		
		$formModel = $this->_getFormModel();
		
		$fetchOptions = array(
		    'numResponses' => true
		);
		
		// sort by display order if the option is set that way
		if (XenForo_Application::getOptions()->lpsfSortOrder == 'custom')
		{
		    $fetchOptions['order'] = 'display_order';
		}
		
		// fetch the forms
		$forms = $formModel->getForms(array(), $fetchOptions);

		$permissionSets = $this->_getPermissionModel()->getUserCombinationsWithContentPermissions('form');
		$formsWithPerms = array();
		foreach ($permissionSets AS $set)
		{
			$formsWithPerms[$set['content_id']] = true;
		}
		
		$viewParams = array(
			'forms' => $forms,
			'formsWithPerms' => $formsWithPerms
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_List', 'lpsf_form_list', $viewParams);
	}
	
	public function actionAdd()
	{
		$formModel = $this->_getFormModel();
		
		$destinationModel = $this->_getDestinationModel();
		$redirectDestinations = $destinationModel->getDestinationsWithRedirect();
		$destinations = array();
		foreach ($redirectDestinations as $destinationId => &$destination)
		{
			$destinations[$destinationId] = $destination['name'];
		}
		
		$viewParams = array(
			'dateCriteria' => $this->_getDateCriteria(),
			'redirectDestinations' => $destinations, 
			'form' => array()
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_Add', 'lpsf_form_add', $viewParams);
	}
	
	public function actionEdit()
	{
		$formModel = $this->_getFormModel();
		$fieldModel = $this->_getFieldModel();
		$fieldTypes = $fieldModel->getCountByType();
		$destinationModel = $this->_getDestinationModel();
		$destinationOptionModel = $this->_getDestinationOptionModel();
		
		if ($formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT))
		{
			// if a form ID was specified, we should be editing, so make sure a form exists
			$form = $formModel->getFormById($formId);
			if (!$form)
			{
				return $this->responseError(new XenForo_Phrase('requested_form_not_found'), 404);
			}
			$form = $formModel->prepareForm($form);
		}
		else
		{
			return $this->responseReroute('LiquidPro_SimpleForms_ControllerAdmin_Form', 'add');
		}
		
		// get the form's destinations
		$redirectDestinations = array();
		$formDestinations = $destinationModel->getDestinationsByFormId($formId);
		foreach ($formDestinations as $formDestinationId => &$formDestination)
		{
			// get the destination options
			$formDestination['options'] = $destinationOptionModel->prepareDestinationOptions($destinationOptionModel->getDestinationOptionsByFormDestinationId($formDestinationId), true);
			
			foreach ($formDestination['options'] as $optionId => &$option)
			{
				if ($option['evaluate_template'])
				{
					if (!is_array($option['format_params']))
					{
						$option['format_params'] = array();
					}
						
					if (array_key_exists('inputClass', $option['format_params']))
					{
						$option['format_params']['inputClass'] .= ' FormFieldHelper';
					}
					else
					{
						$option['format_params']['inputClass'] = 'FormFieldHelper';
					}
				}
			}
			
			// if the destination has a redirect method and is active, add it to the redirect destinations
			if ($formDestination['redirect_method'] && $formDestination['active'])
			{
				$redirectDestinations[$formDestinationId] = $formDestination['name'];
			}
		}
		
		$viewParams = array(
			'form' => $form,
			'dateCriteria' => $this->_getDateCriteria(),
			'fields' => $fieldModel->prepareFields($fieldModel->getFields(array('form_id' => $formId))),
			'fieldTypes' => $fieldModel->getFieldTypes(),
			'formDestinations' => $formDestinations,
			'redirectDestinations' => $redirectDestinations,
			'fieldTypes' => $fieldTypes
		);
		
		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_Edit', 'lpsf_form_edit', $viewParams);
	}
	
	public function actionResponses()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);
		
		$responseModel = $this->_getResponseModel();
		$responses = $responseModel->getResponses(array('form_id' => $formId), array('username' => true, 'order' => 'response_date', 'direction' => 'DESC'));
		
		$viewParams = array(
			'form' => $form,
			'responses' => $responses
		);
		
		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_Responses', 'lpsf_form_responses', $viewParams);
	}

	/**
	 * Validate a single field
	 *
	 * @return XenForo_ControllerResponse_View|XenForo_ControllerResponse_Error
	 */
	public function actionValidateField()
	{
		$this->_assertPostOnly();

		$field = $this->_getFieldValidationInputParams();
		$existingKey = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		
		if (preg_match('/^destination_options\[([a-zA-Z0-9_]+)\]\[([a-zA-Z0-9_]+)\]$/', $field['name'], $match) ||
			preg_match('/^destination_options\[([a-zA-Z0-9_]+)\]\[([a-zA-Z0-9_]+)\]\[\]$/', $field['name'], $match))
		{
			$writer = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Form');
			if ($existingKey)
			{
				$writer->setExistingData($existingKey);
			}

			$writer->setDestinationOptions(array($match[1] => array($match[2] => $field['value'])));
			
			if ($errors = $writer->getErrors())
			{
				return $this->responseError($errors);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				'',
				new XenForo_Phrase('redirect_field_validated', array('name' => $field['name'], 'value' => $field['value']))
			);
		}
		else
		{
			// handle normal fields
			return $this->_validateField(
				'LiquidPro_SimpleForms_DataWriter_Form',
				array('existingDataKey' => $existingKey)
			);
		}
	}
	
	public function actionExportResponses()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);
		
		$formModel = $this->_getFormModel();
		$fieldModel = $this->_getFieldModel();
		$responseModel = $this->_getResponseModel();
		
		// get the fields, get the responses, get the values
		$fields = $fieldModel->getFields(array('form_id' => $formId));
		$responses = $responseModel->getResponses(array('form_id' => $formId), array('username' => true));
		$values = $fieldModel->getFieldValues($formId);

		foreach ($values AS $value)
		{
			$responses[$value['response_id']][$fields[$value['field_id']]['field_name']] = $value['field_value'];
		}
		
		$headers = '';
		$headers .= 'response_id,response_date,user_id,username,ip_address';
		foreach ($fields AS $fieldId => $field)
		{
			$headers .= ',' . $field['field_name'];
		}
		$headers .= PHP_EOL;
		
		$language = XenForo_Visitor::getInstance()->getLanguage();
		$dateFormat = $language['date_format'];
		
		$lines = '';
		if (count($responses) > 0)
		{
			foreach ($responses AS $responseId => $response)
			{	
			    $line = $responseId;
				if (isset($response['response_date']))
				{
					$date = date($dateFormat, $response['response_date']);
					$date = str_replace('"', '""', $date);
					if (strpos($date, ',') || strpos($date, '"'))
					{
						$date = '"' . $date . '"';
					}
					
					$line .= ',' . $date;
				}
				else
				{
					$line .= ',';
				}
				
				if (isset($response['user_id']))
				{
					$line .= ',' . $response['user_id'];
				}
				else
				{
					$line .= ',';
				}
				
				if (isset($response['username']))
				{
					$line .= ',' . $response['username'];
				}
				else
				{
					$line .= ',';
				}
				
				if (isset($response['ip_address']))
				{
					$line .= ',' . $response['ip_address'];
				}
				else
				{ 
					$line .= ',';
				}
				
				foreach ($fields AS $fieldId => $field)
				{
					if (isset($response[$field['field_name']]))
					{
						if (is_array($response[$field['field_name']]))
						{
							$value = '';
							foreach ($response[$field['field_name']] AS $item)
							{
								$value .= ',' . $item;
							}
							$value = substr($value, 1, strlen($value) - 1);
						}
						else
						{
							$value = $response[$field['field_name']];
						}					
						
						$value = str_replace('"', '""', $value);
						$value = str_replace("\n", '', $value);
						if (strpos($value, ',') || strpos($value, '"'))
						{
							$value = '"' . $value . '"';
						}
						$line .= ',' . $value;
					}
					else
					{
						$line .= ',';
					}
				}
				
				$lines .= $line . PHP_EOL;
			}
		}

		$file = $headers . $lines;
		
		$this->_routeMatch->setResponseType('raw');
		
		$viewParams = array(
			'fileName' => $form['title'] . ' Responses.csv',
			'fileSize' => strlen($file),
			'file' => $file
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_ExportResponses', '', $viewParams);
	}
	
	public function actionExport()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::STRING);
		$form = $this->_getFormOrError($formId);

		$this->_routeMatch->setResponseType('xml');

		$viewParams = array(
			'form' => $form,
			'xml' => $this->_getFormModel()->getFormXml($form)
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_Export', '', $viewParams);
	}
	
	public function actionSave()
	{
		$this->_assertPostOnly();

		if ($this->_input->filterSingle('delete', XenForo_Input::STRING))
		{
			return $this->responseReroute('LiquidPro_SimpleForms_ControllerAdmin_Form', 'deleteConfirm');
		}

		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);

		$writerData = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'description' => XenForo_Input::STRING,
			'max_responses' => XenForo_Input::UINT,
			'max_responses_per_user' => XenForo_Input::UINT,
			'hide_from_list' => XenForo_Input::UINT,
			'redirect_method' => XenForo_Input::STRING,
			'redirect_destination' => XenForo_Input::UINT,
			'redirect_url' => XenForo_Input::STRING,
			'complete_message' => XenForo_Input::STRING,
			'css' => XenForo_Input::STRING,
			'require_attachment' => XenForo_Input::UINT,
			'active' => XenForo_Input::UINT,
			'start_date' => XenForo_Input::ARRAY_SIMPLE,
			'end_date' => XenForo_Input::ARRAY_SIMPLE,
		    'header_html' => XenForo_Input::STRING,
		    'footer_html' => XenForo_Input::STRING
		));
		
		// remove redirect_destination if we are not using it
		if ($writerData['redirect_destination'] == 0)
		{
			unset($writerData['redirect_destination']);
		}
		$writer = $this->_getDataWriter();
		
		if ($formId)
		{
			$writer->setExistingData($formId);
		}

		// get visitor for timezone information
		$visitor = XenForo_Visitor::getInstance();

		$writer->bulkSet($writerData);
		$writer->save();
		
		// it was a new form, so we should redirect back to the edit page, possibly even the fields tab
		if (!$formId)
		{
			$formId = $writer->get('form_id');
			
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('forms/edit', array('form_id' => $formId)) . '#fields'
			);
		}

		if ($this->_input->filterSingle('reload', XenForo_Input::STRING))
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
				XenForo_Link::buildAdminLink('forms/edit', $writer->getMergedData(), array('form_id' => $writer->get('form_id')))
			);
		}
		else
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('forms') . $this->getLastHash("{$formId}")
			);
		}
	}
	
	/**
	 * Deletes a form.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDelete()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'LiquidPro_SimpleForms_DataWriter_Form', 'form_id',
				XenForo_Link::buildAdminLink('forms')
			);
		}
		else
		{
			$form = $this->_getFormOrError($this->_input->filterSingle('form_id', XenForo_Input::STRING));

			$viewParams = array(
				'form' => $form
			);

			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_Delete', 'lpsf_form_delete', $viewParams);
		}
	}	
	
	public function actionDeleteConfirm()
	{
		$formModel = $this->_getFormModel();

		$form = $this->_getFormOrError($this->_input->filterSingle('form_id', XenForo_Input::UINT));

		$viewParams = array(
			'form' => $form
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_Delete', 'lpsf_form_delete', $viewParams);
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

		$formId = $this->_input->filterSingle('form_id', XenForo_Input::STRING);
		return $this->_switchFormActiveStateAndGetResponse($formId, 0);
	}	
	
	/**
	 * Disables the specified form.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEnable()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));

		$formId = $this->_input->filterSingle('form_id', XenForo_Input::STRING);
		return $this->_switchFormActiveStateAndGetResponse($formId, 1);
	}	
	
	/**
	 * Selectively enables or disables specified forms
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionToggle()
	{
		return $this->_getToggleResponse(
			$this->_getFormModel()->getForms(),
			'LiquidPro_SimpleForms_DataWriter_Form',
			'forms'
		);
	}	
	
	/**
	 * Imports a form.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionImport()
	{
		if ($this->isConfirmedPost())
		{
			$fileTransfer = new Zend_File_Transfer_Adapter_Http();
			if ($fileTransfer->isUploaded('upload_file'))
			{
				$fileInfo = $fileTransfer->getFileInfo('upload_file');
				$fileName = $fileInfo['upload_file']['tmp_name'];
			}
			else
			{
				$fileName = $this->_input->filterSingle('server_file', XenForo_Input::STRING);
			}

			$formId = $this->_getFormModel()->importFormXmlFromFile($fileName);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('forms', array('form_id' => $formId)) . '#fields'
			);
		}
		else
		{
			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_Import', 'lpsf_form_import');
		}
	}	
	
	/**
	 * Adds a template field to a form.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */	
	public function actionAddTemplateField()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);
		
		if ($this->isConfirmedPost())
		{
			$fieldModel = $this->_getFieldModel();
			$template = $fieldModel->prepareField($fieldModel->getFieldById($this->_input->filterSingle('template', XenForo_Input::UINT)));
			
			$dw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Field');

			$writerData = array_merge(
				$template, 
				array(
					'type' => 'user',
					'form_id' => $formId,
					'active' => 1
				)
			);
			$dw->setFieldChoices(unserialize($writerData['field_choices']));
			
			$dw->setExtraData(
				LiquidPro_SimpleForms_DataWriter_Field::DATA_TITLE,
				$writerData['title']
			);
			$dw->setExtraData(
				LiquidPro_SimpleForms_DataWriter_Field::DATA_DESCRIPTION,
				$writerData['description']
			);			

			// unset fields that shouldn't be duplicated
			unset($writerData['field_id']);
			unset($writerData['isMultiChoice']);
			unset($writerData['field_value']);
			unset($writerData['title']);
			unset($writerData['description']);
			unset($writerData['hasValue']);		
			unset($writerData['field_choices']);	
			
			$dw->bulkSet($writerData);
			$dw->save();
			
			// get the field id that was created
			$fieldId = $dw->get('field_id');
			
			// redirect to edit the field
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('fields/edit', array('field_id' => $fieldId))	
			);
		}
		else
		{
			$fieldModel = $this->_getFieldModel();
			$templates = $fieldModel->getFields(array(
				'type' => 'template'
			));
			
			$viewParams = array(
				'templates' => $fieldModel->prepareFields($templates),
				'form' => $form	
			);
			
			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_AddTemplateField', 'lpsf_form_add_template_field', $viewParams);
		}
	}

	/**
	 * Adds a global field to a form.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */	
	public function actionAddGlobalField()
	{ 
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);
	
		if ($this->isConfirmedPost())
		{
			$fieldModel = $this->_getFieldModel();
			$global = $fieldModel->prepareField($fieldModel->getFieldById($this->_input->filterSingle('global', XenForo_Input::UINT)));
			
			$dw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Field');
	
			$writerData = array_merge(
				$global,
				array(
					'type' => 'user',
					'form_id' => $formId,
					'parent_field_id' => $global['field_id'],
					'active' => 0
				)
			);
			$dw->setExtraData(LiquidPro_SimpleForms_DataWriter_Field::DATA_TITLE, $global['field_name']);
			$dw->setExtraData(LiquidPro_SimpleForms_DataWriter_Field::DATA_DESCRIPTION, '');
			$dw->setFieldChoices(unserialize($global['field_choices']));
				
			// unset fields that shouldn't be duplicated
			unset($writerData['field_id']);
			unset($writerData['isMultiChoice']);
			unset($writerData['field_value']);
			unset($writerData['title']);
			unset($writerData['description']);
			unset($writerData['hasValue']);

			$dw->bulkSet($writerData);
			$dw->save();
			
			// get the field id that was created
			$fieldId = $dw->get('field_id');
			
			// redirect to edit the field
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('fields/edit', array('field_id' => $fieldId))	
			);
		}
		else
		{
			$fieldModel = $this->_getFieldModel();
			$globals = $fieldModel->getFields(array(
				'type' => 'global'
			));
				
			$viewParams = array(
				'globals' => $fieldModel->prepareFields($globals),
				'form' => $form
			);
				
			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_AddGlobalField', 'lpsf_form_add_global_field', $viewParams);
		}
	}	
	
	public function actionDocumentation()
	{
	    return $this->responseRedirect(
	        XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, 
	        'https://liquidpro.atlassian.net/wiki/display/SF/Wiki'
        );
	}
	
	/**
	 * @return LiquidPro_SimpleForms_DataWriter_Form
	 */
	protected function _getDataWriter()
	{
		return XenForo_DataWriter::create($this->_dataWriterName);
	}
	
	/**
	 * @return LiquidPro_SimpleForms_DataWriter_FormDestination
	 */
	protected function _getFormDestinationDataWriter()
	{
		return XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_FormDestination');
	}
	
	/**
	 * Gets the permission model.
	 *
	 * @return XenForo_Model_Permission
	 */
	protected function _getPermissionModel()
	{
		return $this->getModelFromCache('XenForo_Model_Permission');
	}	
	
	/**
	 * Helper to switch the active state for a form and get the controller response.
	 *
	 * @param string $formId Form ID
	 * @param integer $activeState 0 or 1
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	protected function _switchFormActiveStateAndGetResponse($formId, $activeState)
	{
		$dw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Form');
		$dw->setExistingData($formId);
		$dw->set('active', $activeState);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('forms') . $this->getLastHash($formId)
		);
	}	
	
	protected function _getDateCriteria()
	{
		$hours = array();
		for ($i = 0; $i < 24; $i++)
		{
			$hh = str_pad($i, 2, '0', STR_PAD_LEFT);
			$hours[$hh] = $hh;
		}
		
		$minutes = array();
		for ($i = 0; $i < 60; $i += 5)
		{
			$mm = str_pad($i, 2, '0', STR_PAD_LEFT);
			$minutes[$mm] = $mm;
		}

		return array(
			'timezones' => XenForo_Helper_TimeZone::getTimeZones(),
			'hours' => $hours,
			'minutes' => $minutes
		);
	}
	
	/**
	 * Changes the display order.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDisplayOrder()
	{
	    $order = $this->_input->filterSingle('order', XenForo_Input::ARRAY_SIMPLE);
	
	    $this->_assertPostOnly();
	    $this->_getFormModel()->massUpdateDisplayOrder($order);
	
	    return $this->responseRedirect(
	        XenForo_ControllerResponse_Redirect::SUCCESS,
	        XenForo_Link::buildAdminLink('forms')
	    );
	}	
	
	/**
	 * Resets local license key
	 * 
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionResetLocalKey()
	{
	    LiquidPro_SimpleForms_Option_LicenseKey::resetLocalKey();
	    
	    return $this->responseRedirect(
	        XenForo_ControllerResponse_Redirect::SUCCESS,
	        XenForo_Link::buildAdminLink('forms')
	    );
	}
	
	protected function _getDb()
	{
		return XenForo_Application::get('db');
	}
}