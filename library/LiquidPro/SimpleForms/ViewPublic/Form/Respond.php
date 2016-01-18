<?php

class LiquidPro_SimpleForms_ViewPublic_Form_Respond extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));
		
		$fields = &$this->_params['fields'];
		foreach ($fields as $fieldId => &$field)
		{
			if ($field['field_type'] == 'wysiwyg')
			{
				$field['editor'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
					$this,  'fields[' . $field['field_id'] . '][editor]', $field['default_value'], array('editorId' => ($fieldId . '_editor'))
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
			
			// render the pre text
			if ($field['pre_text'] != '')
			{
				$field['pre_text'] = new XenForo_BbCode_TextWrapper($field['pre_text'], $bbCodeParser);
			}
			
			// render the post text
			if ($field['post_text'] != '')
			{
				$field['post_text'] = new XenForo_BbCode_TextWrapper($field['post_text'], $bbCodeParser);
			}

			// datetime default_value editor
			if (array_key_exists('field_type', $field) && $field['field_type'] == 'datetime')
			{
			    $temp = explode(' ', $field['default_value']);
			
			    if (count($temp) == 2)
			    {
			        $field['default_value'] = array(
			            'date' => $temp[0],
			            'time' => $temp[1]
			        );
			    }
			    else
			    {
			        $field['default_value'] = array(
			            'date' => '',
			            'time' => ''
			        );
			    }
			}
		}
	}
}