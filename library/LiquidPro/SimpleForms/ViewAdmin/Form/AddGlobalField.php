<?php

class LiquidPro_SimpleForms_ViewAdmin_Form_AddGlobalField extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$globals = $this->_params['globals'];
		
		$preparedGlobals = array();
		foreach ($globals as $global)
		{
			$preparedGlobal = array();
			
			$preparedGlobal['label'] = $global['title'];
			$preparedGlobal['value'] = $global['field_id'];
			
			$preparedGlobals[] = $preparedGlobal;
		}
		
		$this->setParams(array(
			'preparedGlobals' => $preparedGlobals
		));
	}
}