<?php

class LiquidPro_SimpleForms_ControllerAdmin_FormDestination extends LiquidPro_SimpleForms_ControllerAdmin_Abstract
{
	/**
	 * Name of the DataWriter that will handle this content
	 *
	 * @var string
	 */
	protected $_dataWriterName = 'LiquidPro_SimpleForms_DataWriter_FormDestination';
	
	/**
	 * Gets the form destination add/edit form response.
	 *
	 * @param array formDestinationy
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	protected function _getFormDestinationAddEditResponse(array $formDestination)
	{
		$destinationModel = $this->_getDestinationModel();
		$formModel = $this->_getFormModel();
		$fieldModel = $this->_getFieldModel();
		$destinationOptionModel = $this->_getDestinationOptionModel();
		
		// get the form
		$form = $formModel->getFormById($formDestination['form_id']);
		
		// get the destination
		$destination = $destinationModel->getDestinationById($formDestination['destination_id']);
		
		// get the fields
		$fields = $fieldModel->prepareFields($fieldModel->getFields(array('form_id' => $formDestination['form_id'])));
		
		// get the destination options
		if (array_key_exists('form_destination_id', $formDestination))
		{
			$formDestination['options'] = $destinationOptionModel->prepareDestinationOptions($destinationOptionModel->getDestinationOptionsByFormDestinationId($formDestination['form_destination_id']), true);
		}
		else
		{
			// build new destination options
			$formDestination['options'] = $destinationOptionModel->prepareDestinationOptions($destinationOptionModel->getDestinationOptions(array('destination_id' => $formDestination['destination_id'])), true);
		}
		
		foreach ($formDestination['options'] as $optionId => &$option)
		{
			if (!is_array($option['format_params']) && $option['field_type'] != 'callback')
			{
				$option['format_params'] = array();
			}
			
			if ($option['evaluate_template'] && is_array($option['format_params']))
			{
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
		
		$viewParams = array(
			'destination' => $destination,
			'form' => $form,
			'fields' => $fields,
			'formDestination' => $formDestination
		);
		
		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_FormDestination_Edit', 'lpsf_form_destination_edit', $viewParams);
	}	
	
	/**
	 * Adds a form destination.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionAdd()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$destinationId = $this->_input->filterSingle('destination_id', XenForo_Input::UINT);
		
		if ($formId && $destinationId)
		{
			return $this->_getFormDestinationAddEditResponse(array(
				'destination_id' => $destinationId,
				'form_id' => $formId	
			));
		}
		else
		{
			$destinationModel = $this->_getDestinationModel();
			$destinations = $destinationModel->getDestinations();
			
			$destinationOptions = array();
			foreach ($destinations as $destinationId => $destination)
			{
				$destinationOptions[] = array(
					'label' => $destination['name'],
					'value' => $destinationId
				);
			}
			
			$viewParams = array(
				'destinationOptions' => $destinationOptions,
				'formId' => $formId	
			);
			
			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_FormDestination_AddType', 'lpsf_form_destination_add_type', $viewParams);
		}
	}
	
	public function actionEdit()
	{
		$formDestination = $this->_getFormDestinationOrError($this->_input->filterSingle('form_destination_id', XenForo_Input::UINT));
		
		return $this->_getFormDestinationAddEditResponse($formDestination);
	}
	
	public function actionSave()
	{
		$this->_assertPostOnly();
	
		if ($this->_input->filterSingle('delete', XenForo_Input::STRING))
		{
			return $this->responseReroute('LiquidPro_SimpleForms_ControllerAdmin_Form', 'deleteConfirm');
		}
	
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$formDestinationId = $this->_input->filterSingle('form_destination_id', XenForo_Input::UINT);
		
		$writerData = $this->_input->filter(array(
			'name' => XenForo_Input::STRING,
			'active' => XenForo_Input::UINT,
			'form_id' => XenForo_Input::UINT,
			'destination_id' => XenForo_Input::UINT
		));
		
		// handle form destination saving
		$writer = $this->_getDataWriter();
		
		if ($formDestinationId)
		{
			$writer->setExistingData($formDestinationId);
		}
		
		$writer->bulkSet($writerData);
		
		$destinationOptions = $this->_input->filterSingle('destination_options', XenForo_Input::ARRAY_SIMPLE);
		$destinationOptionsShown = $this->_input->filterSingle('destination_options_shown', XenForo_Input::ARRAY_SIMPLE);
		$writer->setDestinationOptions($destinationOptions, $destinationOptionsShown);
		$writer->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('forms/edit', '', array('form_id' => $formId)) . '#destinations'
		);
	}
	
	/**
	 * Deletes a form destination.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDelete()
	{
		$formDestinationId = $this->_input->filterSingle('form_destination_id', XenForo_Input::UINT);
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
	
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'LiquidPro_SimpleForms_DataWriter_FormDestination', 'form_destination_id',
				XenForo_Link::buildAdminLink('forms/edit', '', array('form_id' => $formId)) . '#destinations'
			);
		}
		else
		{
			$form = $this->_getFormOrError($formId);
			$formDestination = $this->_getFormDestinationOrError($formDestinationId);
			
			$viewParams = array(
				'form' => $form,
				'formDestination' => $formDestination
			);
	
			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_FormDestination_Delete', 'lpsf_form_destination_delete', $viewParams);
		}
	}	
	
	/**
	 * Disables the specified form destination.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDisable()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));
		
		$formDestinationId = $this->_input->filterSingle('form_destination_id', XenForo_Input::STRING);
		return $this->_switchFormDestinationActiveStateAndGetResponse($formDestinationId, 0);
	}
	
	/**
	 * Enables the specified form destination.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEnable()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));
	
		$formDestinationId = $this->_input->filterSingle('form_destination_id', XenForo_Input::STRING);
		return $this->_switchFormDestinationActiveStateAndGetResponse($formDestinationId, 1);
	}
	
	/**
	 * Helper to switch the active state for a form destination and get the controller response.
	 *
	 * @param string $formDestinationId Form Destination ID
	 * @param integer $activeState 0 or 1
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	protected function _switchFormDestinationActiveStateAndGetResponse($formDestinationId, $activeState)
	{
		$dw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_FormDestination');
		$dw->setExistingData($formDestinationId);
		$dw->set('active', $activeState);
		$dw->save();
	
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('forms/edit', '', array('form_id' => $dw->get('form_id'))) . '#destinations'
		);
	}
	
	/**
	 * @return LiquidPro_SimpleForms_DataWriter_Form
	 */
	protected function _getFormDataWriter()
	{
		return XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Form');
	}	
}