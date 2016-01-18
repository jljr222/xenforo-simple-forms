<?php

class LiquidPro_SimpleForms_Listener_Proxy_DataWriterUser extends XFCP_LiquidPro_SimpleForms_Listener_Proxy_DataWriterUser
{
	protected function _preDelete()
	{
		$responseModel = new LiquidPro_SimpleForms_Model_Response();
		
		$action = XenForo_Application::getOptions()->deleteUserLogic;
		switch ($action)
		{
			case 'delete':
			{
				$responseModel->deleteUserResponses($this->get('user_id'));
				break;
			}
			
			case 'reassign':
			{
				$responseModel->reassignUserResponses($this->get('user_id'));
				break;
			}
		}
		
		parent::_preDelete();
	}
}