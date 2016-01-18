<?php
 
class LiquidPro_SimpleForms_Listener_Proxy_AdminControllerUser extends XFCP_LiquidPro_SimpleForms_Listener_Proxy_AdminControllerUser
{
	/**
	 * Searches for a forum by the left-most prefix of a title (for auto-complete(.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionSearchUsername()
	{
		$q = $this->_input->filterSingle('q', XenForo_Input::STRING);

		if ($q !== '')
		{
			$users = $this->_getUserModel()->getUsers(
				array('username' => array($q , 'r')),
				array('limit' => 10)
			);
		}
		else
		{
			$users = array();
		}

		$viewParams = array(
			'users' => $users
		);

		return $this->responseView(
			'LiquidPro_SimpleForms_ViewAdmin_User_SearchUsername',
			'',
			$viewParams
		);
	}	
}