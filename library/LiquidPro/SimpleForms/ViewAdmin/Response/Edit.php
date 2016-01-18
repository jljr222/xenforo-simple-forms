<?php

class LiquidPro_SimpleForms_ViewAdmin_Response_Edit extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$fields = &$this->_params['fields'];
		foreach ($fields as $fieldId => &$field)
		{
			if ($field['field_type'] == 'wysiwyg')
			{
				$field['editor'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
					$this,  'fields[' . $field['field_id'] . '][editor]', $field['field_value'], array('editorId' => ($fieldId . '_editor'), 'template' => 'wysiwyg')
				);
			}
			
			if ($field['field_type'] == 'rating')
			{
				$field['fieldChoices'] = array();
				for ($i = 1; $i <= XenForo_Application::getOptions()->lpsfRatingMax; $i++)
				{
					$field['fieldChoices'][] = $i;
				}
			}
		}
	}
}