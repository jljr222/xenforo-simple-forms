<?php

class LiquidPro_SimpleForms_ControllerPublic_Form extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		if (!XenForo_Visitor::getInstance()->hasPermission('form', 'viewFormsList'))
		{
			throw $this->getErrorOrNoPermissionResponseException('do_not_have_permission');
		}
		
		$formModel = $this->_getFormModel();
		
		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$formsPerPage = XenForo_Application::get('options')->formsPerPage;
		
		$visitor = XenForo_Visitor::getInstance();
		
		$criteria = array(
			'num_fields' => array(1, '>='),
			'active' => 1,
			'hide_from_list' => 0
		);
		
		$fetchOptions = array(
		    'perPage' => $formsPerPage,
		    'page' => $page,
		    'numResponses' => true,
		    'numUserResponses' => true,
		    'permissionCombinationId' => $visitor['permission_combination_id']
        );
		
		// sort by display order if custom
		if (XenForo_Application::getOptions()->lpsfSortOrder == 'custom')
		{
		    $fetchOptions['order'] = 'display_order';
		}
		
		$forms = $formModel->getForms($criteria, $fetchOptions);
		
		foreach ($forms AS $formId => &$form)
		{
			$form = $formModel->prepareForm($form);
			if (!$formModel->canRespondToForm($form, $errorPhraseKey))
			{
				unset($forms[$formId]);
			}
		}	
			
		$viewParams = array(
			'forms' => $forms,
			'visibleForms' => count($forms),
			'formsPerPage' => $formsPerPage,
			'page' => $page,
			'totalForms' => $formModel->countForms($criteria)
		);
		
		return $this->responseView('LiquidPro_SimpleForms_ViewPublic_Form_List', 'form_list', $viewParams);
	}
	
	public function actionRespond()
	{
		$form = $this->_getFormOrError($this->_input->filterSingle('form_id', XenForo_Input::UINT));
		$fieldModel = $this->_getFieldModel();
		$destinationOptionModel = $this->_getDestinationOptionModel();
		
		$attachmentParams = array();
		
		// check to see if attachments are enabled
		$attachmentsEnabled = $destinationOptionModel->getAttachmentsEnabled($form['form_id']);
		if ($attachmentsEnabled)
		{
			$attachmentParams = array(
				'hash' => md5(uniqid('', true)),
				'content_type' => 'form',
				'content_data' => array('form_id' => $form['form_id'])
			);			
		}
		
		$params = array();
		if (XenForo_Visitor::getUserId())
		{
		    $params['visitor'] = XenForo_Visitor::getInstance();
		}
		else
		{
		    $params['visitor'] = array(
		        'username' => 'Guest'
		    );
		}		
		
		// process GET supplied default values
		$fields = $fieldModel->prepareFields($fieldModel->getFields(array('form_id' => $form['form_id'])), true);
		foreach ($fields as $fieldId => &$field)
		{
		    $field['default_value'] = $this->_renderTemplate($field['default_value'], $params);
		    
			$getDefaultValue = $this->_input->filterSingle($field['field_name'], XenForo_Input::STRING);
			if ($getDefaultValue)
			{
				$field['default_value'] = $getDefaultValue;
			}
		}
		
		$viewParams = array(
			'form' => $form,
			'fields' => $fields,
			'attachmentManager' => $attachmentsEnabled,
			'attachmentParams' => $attachmentParams,
			'attachmentConstraints' => $this->getModelFromCache('XenForo_Model_Attachment')->getAttachmentConstraints(),
			'captcha' => XenForo_Captcha_Abstract::createDefault()
		);
		
		return $this->responseView('LiquidPro_SimpleForms_ViewPublic_Form_Respond', 'form_respond', $viewParams);
	}
	
	protected function _renderTemplate($template, array $params = array())
	{
	    extract($params);
	     
	    $compiler = new XenForo_Template_Compiler($template);
	     
	    XenForo_Application::disablePhpErrorHandler();
	    @eval($compiler->compile());
	    XenForo_Application::enablePhpErrorHandler();
	     
	    return htmlspecialchars_decode($__output, ENT_QUOTES);
	}	
	
	public function actionSave()
	{
		$this->_assertPostOnly();
		
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);

		if (!XenForo_Captcha_Abstract::validateDefault($this->_input))
		{
			return $this->responseCaptchaFailed();
		}		
		
		/* @var $writer LiquidPro_SimpleForms_DataWriter_Response */
		$writer = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Response');
		$writerData = array(
			'form_id' => $formId,
			'ip_address' => $_SERVER['REMOTE_ADDR'],
			'response_date' => XenForo_Application::$time
		);
		
		// user id
		if (XenForo_Visitor::getUserId())
		{
			$writerData['user_id'] = XenForo_Visitor::getUserId();
		}
		
		$writer->bulkSet($writerData);
		
		// attachment data
		$attachmentHash = $this->_input->filterSingle('attachment_hash', XenForo_Input::STRING);
		if ($attachmentHash)
		{
			$writer->setExtraData(LiquidPro_SimpleForms_DataWriter_Response::DATA_ATTACHMENT_HASH, $this->_input->filterSingle('attachment_hash', XenForo_Input::STRING));
		}
		
		// form_field values should go in the response data writer
		$fields = $this->_input->filterSingle('fields', XenForo_Input::ARRAY_SIMPLE);
		$fieldsShown = $this->_input->filterSingle('fields_shown', XenForo_Input::ARRAY_SIMPLE);
		
		// array to string conversion
		foreach ($fields as $fieldId => &$field)
		{
		    //  handle wysiwyg
			if (is_array($field) && count($field) == 1)
			{
				if (array_key_exists('editor_html', $field))
				{
					$field = $this->getHelper('Editor')->convertEditorHtmlToBbCode($field['editor_html'], $this->_input);
				}	
				else if (array_key_exists('editor', $field))
				{
					$field = $field['editor'];
				}			
			}
			
			// handle datetime
			if (is_array($field) && count($field) == 2)
			{
			    if (array_key_exists('date', $field) && array_key_exists('time', $field))
			    {
			        $field = $field['date'] . ' ' . $field['time'];
			    }
			}
		}
		
		$writer->setFields($fields, $fieldsShown);
		$writer->preSave();
		
		if ($dwErrors = $writer->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		
		$writer->save();
		
		switch ($form['redirect_method'])
		{
			case 'url':
			{
				if ($form['redirect_url'] == '')
				{
					$visitor = XenForo_Visitor::getInstance();
					if ($visitor->hasPermission('form', 'viewFormsList'))
					{
						$redirectUrl = XenForo_Link::buildPublicLink('forms');
					}
					else
					{
						$redirectUrl = XenForo_Link::buildPublicLink('index');
					}
				}
				else
				{
					$redirectUrl = $form['redirect_url'];
				}
						
				break;
			}
			case 'destination':
			{
				$redirectUrl = $writer->getRedirectUrl();
				break;
			}
			default: 
			{
				// redirect back to the form
				$redirectUrl = XenForo_Link::buildPublicLink('forms/respond', $form);

				break;
			}
		}
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirectUrl,
			new XenForo_Phrase($form['complete_message'])
		);		
	}
	
	/**
	 * Validate a single field
	 *
	 * @return XenForo_ControllerResponse_Redirect
	 */
	public function actionValidateField()
	{
		$this->_assertPostOnly();

		$field = $this->_getFieldValidationInputParams();

		if (preg_match('/^field\[([a-zA-Z0-9_]+)\]$/', $field['name'], $match))
		{
			$writer = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Response');
			$writer->setFields(array($match[1] => $field['value']));

			if ($errors = $writer->getErrors())
			{
				return $this->responseError($errors);
			}
		}
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			'',
			new XenForo_Phrase('redirect_field_validated', array('name' => $field['name'], 'value' => $field['value']))
		);
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
	    $visitor = XenForo_Visitor::getInstance();
		$language = $visitor->getLanguage();
		
		$criteria = array(
			'num_fields' => array(1, '>='),
			'active' => 1
		);	
		if (!empty($id))
		{
			$criteria['form_id'] = $id;
		}
		
		$fetchOptions = array(
			'numResponses' => true,
			'numUserResponses' => true,
			'permissionCombinationId' => $visitor['permission_combination_id']
		);

		$form = $this->_getFormModel()->getForms($criteria, $fetchOptions);
		if (!$form)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('requested_form_not_found'), 404));
		}
		$form = $this->_getFormModel()->prepareForm(array_pop($form));

		if (!$this->_getformModel()->canRespondToForm($form, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}				
		 
		if ($form['max_responses'] > 0) {
			if ($form['num_responses'] > $form['max_responses'])
			{
				throw $this->responseException($this->responseError(new XenForo_Phrase('max_responses_reached', array('max_responses' => $form['max_responses'], 'num_responses' => $form['num_responses'])), 403));
			}
		}
		
		if ($form['max_responses_per_user'] > 0) {
			if ($form['num_user_responses'] >= $form['max_responses_per_user'])
			{
				throw $this->responseException($this->responseError(new XenForo_Phrase('max_responses_per_user_reached', array('max_responses_per_user' => $form['max_responses_per_user'], 'num_user_responses' => $form['num_user_responses'])), 403));
			}
		}

		return $form;
	}	
	
	/**
	 * Searches for a form by the left-most prefix of a title (for auto-complete).
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionSearchTitle()
	{
		$q = $this->_input->filterSingle('q', XenForo_Input::STRING);

		$criteria = array(
			'start_date' => array(XenForo_Application::$time, '<='),
			'end_date' => array(XenForo_Application::$time, '>='),
			'num_fields' => array(1, '>='),
			'title' => array($q , 'r'),
			'max_responses' => true,
			'max_responses_per_user' => XenForo_Visitor::getUserId()
		);
		
		$fetchOptions = array(
			'limit' => 10
		);
		
		if ($q !== '')
		{
			$forms = $this->_getFormModel()->getForms($criteria, $fetchOptions);
		}
		else
		{
			$forms = array();
		}

		$viewParams = array(
			'forms' => $forms
		);

		return $this->responseView(
			'LiquidPro_SimpleForms_ViewPublic_Form_SearchTitle',
			'',
			$viewParams
		);
	}	
	
	/**
	 * Session activity details.
	 * @see XenForo_Controller::getSessionActivityDetailsForList()
	 */
	public static function getSessionActivityDetailsForList(array $activities)
	{
		$formIds = array();
		foreach ($activities AS $activity)
		{
			if (!empty($activity['params']['form_id']))
			{
				$formIds[$activity['params']['form_id']] = intval($activity['params']['form_id']);
			}
		}
		
		$formData = array();
		
		if ($formIds)
		{
			/* @var $formModel LiquidPro_SimpleForms_Model_Form */
			$formModel = XenForo_Model::create('LiquidPro_SimpleForms_Model_Form');
		
			$visitor = XenForo_Visitor::getInstance();
			$permissionCombinationId = $visitor['permission_combination_id'];
		
			$forms = $formModel->getForms(array('form_id' => $formIds));
			foreach ($forms AS $form)
			{
				$form = $formModel->prepareForm($form);
				
				if ($formModel->canRespondToForm($form))
				{
					$form['title'] = XenForo_Helper_String::censorString($form['title']);
		
					$formData[$form['form_id']] = array(
						'title' => $form['title'],
						'url' => XenForo_Link::buildPublicLink('forms/respond', $form)
					);
				}
			}
		}
		
		$output = array();
		foreach ($activities AS $key => $activity)
		{
			$form = false;
			if (!empty($activity['params']['form_id']))
			{
				$formId = $activity['params']['form_id'];
				if (isset($formData[$formId]))
				{
					$form = $formData[$formId];
				}
			}
		
			if ($form)
			{
				$output[$key] = array(
					new XenForo_Phrase('lpsf_viewing_form'),
					$form['title'],
					$form['url'],
					''
				);
			}
			else
			{
				$output[$key] = new XenForo_Phrase('lpsf_viewing_form');
			}
		}
		
		return $output;		
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
	 * @return XenForo_Model_Permission
	 */
	protected function _getPermissionModel()
	{
		return $this->getModelFromCache('XenForo_Model_Permission');
	}	
	
	/**
	 * @return LiquidPro_SimpleForms_Model_DestinationOption
	 */
	protected function _getDestinationOptionModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_DestinationOption');
	}	
	
	/**
	 * @return XenForo_Model_Attachment
	 */
	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}	
}