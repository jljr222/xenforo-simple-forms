<?php

class LiquidPro_SimpleForms_ControllerAdmin_Destination extends LiquidPro_SimpleForms_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$destinationModel = $this->_getDestinationModel();
		
		$viewParams = array(
			'destinations' => $destinationModel->prepareDestinations($destinationModel->getDestinations())
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Destination_List', 'lpsf_destination_list', $viewParams);
	}
	
	/**
	 * Gets the add/edit form response for a destination.
	 *
	 * @param array $destination
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	protected function _getFieldAddEditResponse(array $destination)
	{
		$destinationModel = $this->_getDestinationModel();

		if (!empty($destination['destination_id']))
		{
			$masterName = $destinationModel->getDestinationMasterNamePhraseValue($destination['destination_id']);
		}
		else
		{
			$masterName = '';
		}

		$viewParams = array(
			'destination' => $destination,
			'masterName' => $masterName
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Destination_Edit', 'lpsf_destination_edit', $viewParams);
	}
	
	/**
	 * Displays form to add a destination.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionAdd()
	{
		return $this->_getFieldAddEditResponse(array(
			'destination_id' => null,
			'name' => '',
			'display_order' => 1,
			'handler_class' => ''
		));
	}	
	
	/**
	 * Displays form to edit a destination.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEdit()
	{
		$destination = $this->_getFieldOrError($this->_input->filterSingle('destination_id', XenForo_Input::STRING));
		return $this->_getFieldAddEditResponse($destination);
	}	
	
	/**
	 * Saves a destination.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionSave()
	{
		$destinationId = $this->_input->filterSingle('destination_id', XenForo_Input::UINT);
		$newDestinationName = $this->_input->filterSingle('new_destination_name', XenForo_Input::STRING);
		$dwInput = $this->_input->filter(array(
			'destination_id' => XenForo_Input::UINT,
			'name' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'handler_class' => XenForo_Input::STRING
		));

		$writer = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Destination');
		if ($destinationId)
		{
			$writer->setExistingData($destinationId);
		}
		else
		{
			$writer->set('name', $newDestinationName);
		}

		$writer->bulkSet($dwInput);

		$writer->setExtraData(
			LiquidPro_SimpleForms_DataWriter_Destination::DATA_NAME,
			$this->_input->filterSingle('name', XenForo_Input::STRING)
		);

		$writer->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('destinations') . $this->getLastHash($writer->get('destination_id'))
		);
	}	
	
	/**
	 * Deletes a destination.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDelete()
	{
		$destinationId = $this->_input->filterSingle('destination_id', XenForo_Input::UINT);
		
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'LiquidPro_SimpleForms_DataWriter_Destination', 'destination_id',
				XenForo_Link::buildAdminLink('destinations')
			);
		}
		else
		{
			$destination = $this->_getFieldOrError($this->_input->filterSingle('destination_id', XenForo_Input::STRING));

			$viewParams = array(
				'destination' => $destination
			);

			return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Destination_Delete', 'lpsf_destination_delete', $viewParams);
		}
	}	
}