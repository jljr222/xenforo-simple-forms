<?php

class LiquidPro_SimpleForms_ViewAdmin_Form_AddTemplateField extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$templates = $this->_params['templates'];
		
		$preparedTemplates = array();
		foreach ($templates as $template)
		{
			$preparedTemplate = array();
			
			$preparedTemplate['label'] = $template['title'];
			$preparedTemplate['value'] = $template['field_id'];
			
			$preparedTemplates[] = $preparedTemplate;
		}
		
		$this->setParams(array(
			'preparedTemplates' => $preparedTemplates
		));
	}
}