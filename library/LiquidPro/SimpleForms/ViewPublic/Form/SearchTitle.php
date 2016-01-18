<?php

class LiquidPro_SimpleForms_ViewPublic_Form_SearchTitle extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$results = array();
		foreach ($this->_params['forms'] AS $form)
		{
			$results[$form['form_id']] = array(
				'title' => htmlspecialchars($form['title'])
			);
		}
		
		return array(
			'results' => $results
		);
	}
}