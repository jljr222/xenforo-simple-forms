<?php

class LiquidPro_SimpleForms_ViewPublic_Form_AddTemplateField extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$templates = &$this->_params('templates');

		$templateOptions = array();
		foreach ($templates as $fieldId => &$field)
		{
			$templateOptions[] = array(
				'value' => $fieldId,
				'label' => $field['title']	
			);
		}
		
		$this->_params('templateOptions') = $templateOptions;
		return;
	}
}