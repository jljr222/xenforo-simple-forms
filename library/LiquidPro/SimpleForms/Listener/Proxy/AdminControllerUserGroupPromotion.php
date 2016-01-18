<?php

class LiquidPro_SimpleForms_Listener_Proxy_AdminControllerUserGroupPromotion extends XFCP_LiquidPro_SimpleForms_Listener_Proxy_AdminControllerUserGroupPromotion
{
	public function actionAdd()
	{
		return $this->_injectForm(parent::actionAdd());
	}
	
	public function actionEdit()
	{
		return $this->_injectForm(parent::actionEdit());
	}
	
	/**
	 * Inject the lpsf_form parameter into the response
	 * 
	 * @param XenForo_ControllerResponse_View $response
	 * @return XenForo_ControllerResponse_View
	 */
	protected function _injectForm(XenForo_ControllerResponse_View $response)
	{
		$response->params['lpsf_forms'] = $this->_getFormModel()->getForms();
		
		return $response;
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected function _getFormModel()
	{
		return XenForo_Model::create('LiquidPro_SimpleForms_Model_Form');
	}
}