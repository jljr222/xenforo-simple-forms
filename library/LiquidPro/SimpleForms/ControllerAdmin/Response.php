<?php

class LiquidPro_SimpleForms_ControllerAdmin_Response extends LiquidPro_SimpleForms_ControllerAdmin_Abstract
{
	/**
	 * Name of the DataWriter that will handle this node type
	 *
	 * @var string
	 */
	protected $_dataWriterName = 'LiquidPro_SimpleForms_DataWriter_Response';
	
	public function actionIndex()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);
		
		$responseModel = $this->_getResponseModel();
		$responses = $responseModel->getResponses(array('form_id' => $formId), array('username' => true));
		
	}
	
	/**
	 * Edits a response.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEdit()
	{
		$response = $this->_getResponseOrError($this->_input->filterSingle('response_id', XenForo_Input::UINT));
		$form = $this->_getFormOrError($response['form_id']);
		$fieldModel = $this->_getFieldModel();
		
		$viewParams = array(
			'form' => $form,
			'fields' => $fieldModel->prepareFields($fieldModel->getFieldsByResponseId($response['response_id']), true),
			'response' => $response
		);
		
		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Response_Edit', 'lpsf_response_edit', $viewParams);
	}	
	
	public function actionSave()
	{
		$this->_assertPostOnly();
		
		$responseId = $this->_input->filterSingle('response_id', XenForo_Input::UINT);
		$response = $this->_getResponseOrError($responseId);
		$form = $this->_getFormOrError($response['form_id']);
		
		$writer = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Response');
		$writer->disableDestinations();
		$writer->setExistingData($response);
		
		$fields = $this->_input->filterSingle('fields', XenForo_Input::ARRAY_SIMPLE);
		$fieldsShown = $this->_input->filterSingle('fields_shown', XenForo_Input::ARRAY_SIMPLE);
	
		// handle wysiwyg
		foreach ($fields as $fieldId => &$field)
		{
			if (is_array($field))
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
		}
		
		$writer->setFields($fields, $fieldsShown);
		
		$writer->preSave(false);

		if ($dwErrors = $writer->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		
		$writer->save();
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('forms/responses', $form) . $this->getLastHash("{$responseId}")
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

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				'',
				new XenForo_Phrase('redirect_field_validated', array('name' => $field['name'], 'value' => $field['value']))
			);
		}
	}
	
	/**
	 * Deletes a response.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDelete()
	{
		$response = $this->_getResponseOrError($this->_input->filterSingle('response_id', XenForo_Input::UINT));
		$form = $this->_getFormOrError($response['form_id']);

		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'LiquidPro_SimpleForms_DataWriter_Response', 'response_id',
				XenForo_Link::buildAdminLink('forms/responses', $form)
			);
		}
		else
		{
			$viewParams = array(
				'response' => $response,
				'form' => $form
			);

			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Response_Delete', 'lpsf_response_delete', $viewParams);
		}
	}	
	
	public function actionDeleteConfirm()
	{
		$response = $this->_getResponseOrError($this->_input->filterSingle('response_id', XenForo_Input::UINT));
		$form = $this->_getFormOrError($response['form_id']);

		$viewParams = array(
			'response' => $response,
			'form' => $form
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_Delete', 'lpsf_form_delete', $viewParams);
	}

	public function actionClear()
	{
		$form = $this->_getFormOrError($this->_input->filterSingle('form_id', XenForo_Input::UINT));
		
		if ($this->isConfirmedPost())
		{
			$this->_getResponseModel()->massDeleteResponses($form['form_id']);
			
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
				XenForo_Link::buildAdminLink('forms/responses', $form),
				new XenForo_Phrase('lpsf_responses_cleared')
			);
		}
	
		$viewParams = array(
			'form' => $form
		);
	
		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Form_Clear', 'lpsf_response_clear', $viewParams);
	}
}