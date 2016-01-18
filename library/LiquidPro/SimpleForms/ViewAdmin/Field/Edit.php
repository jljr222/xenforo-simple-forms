<?php

class LiquidPro_SimpleForms_ViewAdmin_Field_Edit extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$field = &$this->_params['field'];

		// wysiwyg default_value editor
		if (array_key_exists('field_type', $field) && $field['field_type'] == 'wysiwyg')
		{		
			$field['editor'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
				$this, 'default_value', $field['default_value'], array('editorId' => ($field['field_id'] . '_default_value'), 'template' => 'wysiwyg'));
		}

		// pre_text editor
		if (array_key_exists('pre_text', $field))
		{
			$this->_params['preTextEditor'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
				$this, 'pre_text', $field['pre_text']
			);
		}
		
		// post_text editor
		if (array_key_exists('pre_text', $field))
		{
			$this->_params['postTextEditor'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
				$this, 'post_text', $field['post_text']
			);
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