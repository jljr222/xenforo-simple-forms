<?php

class LiquidPro_SimpleForms_ViewAdmin_User_SearchUsername extends XenForo_ViewAdmin_Base
{
	public function renderJson()
	{
		$results = array();
		foreach ($this->_params['users'] AS $user)
		{
			$results[$user['user_id']] = array(
				'username' => htmlspecialchars($user['username'])
			);
		}
		
		return array(
			'results' => $results
		);
	}
}
