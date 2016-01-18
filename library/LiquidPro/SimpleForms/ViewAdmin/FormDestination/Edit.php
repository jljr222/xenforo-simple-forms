<?php
class LiquidPro_SimpleForms_ViewAdmin_FormDestination_Edit extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		foreach ($this->_params['formDestination']['options'] as $optionId => &$option)
		{
			if (!is_array($option['format_params']) && is_array(@unserialize($option['format_params'])))
			{
				$option['format_params'] = unserialize($option['format_params']);
			}
			
			if ($option['evaluate_template'] && is_array($option['format_params']))
			{
				if (array_key_exists('inputClass', $option['format_params']) && $option['format_params']['inputClass'] != 'FormFieldHelper')
				{
					$option['format_params']['inputClass'] .= ' FormFieldHelper';
				}
				else
				{
					$option['format_params']['inputClass'] = 'FormFieldHelper';
				}
			}
				
			if ($option['field_type'] == 'callback')
			{
				$option['renderedOption'] = call_user_func($option['format_params'], $this, $option);
			}
		}
	}
}