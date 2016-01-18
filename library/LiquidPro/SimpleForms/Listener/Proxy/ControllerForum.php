<?php

class LiquidPro_SimpleForms_Listener_Proxy_ControllerForum extends XFCP_LiquidPro_SimpleForms_Listener_Proxy_ControllerForum
{
	public function actionForum()
	{
	    $response = parent::actionForum();
	    
	    if ($response instanceof XenForo_ControllerResponse_View)
	    { 
		  $response = $this->_injectForm($response);
	    }
	    
	    return $response;
	}

	public function actionCreateThread()
	{
	    $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
		$forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		$forum = $ftpHelper->assertForumValidAndViewable($forumId ? $forumId : $forumName);

		// redirect to form
		if (array_key_exists('lpsf_form_id', $forum) && $forum['lpsf_form_id'])
		{
		    $this->getRequest()->setParam('form_id', $forum['lpsf_form_id']);
		    
		    return $this->responseReroute('LiquidPro_SimpleForms_ControllerPublic_Form', 'respond');
		}
		
	    return parent::actionCreateThread();
	}	
	
	/**
	 * Inject the lpsf_form parameter into the response
	 *
	 * @param XenForo_ControllerResponse_View $response
	 * @return XenForo_ControllerResponse_View
	 */
	protected function _injectForm(XenForo_ControllerResponse_View $response)
	{
		if (array_key_exists('forum', $response->params) && array_key_exists('lpsf_form_id', $response->params['forum']))
		{
			$response->params['form'] = $this->_getFormModel()->getFormById($response->params['forum']['lpsf_form_id']); 
		}
	
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