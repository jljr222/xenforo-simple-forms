<?php

abstract class LiquidPro_SimpleForms_Destination_Abstract
{
	protected $_formId;
	protected $_fields;
	protected $_options;
	protected $_attachmentHash;
	protected $_originalAttachmentId;
	protected $_attachmentId;
	
	// variables to be used inside template rendering
	protected $_templateFields;
	protected $_templateOptions;
	
	public function __construct($formId, $fields, $options, $controller = null)
	{
		$this->_formId = $formId;
		$this->_fields = $fields;
		$this->_options = $this->_prepareOptions($options);
		
		$this->_templateFields = $this->_prepareFieldsForTemplate($this->_fields);
		$this->_templateOptions = $this->_prepareOptionsForTemplate($this->_options);
		
		$this->_compileTemplateBasedOptionValues();
	}
	
	protected function _compileTemplateBasedOptionValues()
	{
	    $params = array();
	    $params['fields'] = $this->_templateFields;
	    $params['options'] = $this->_templateOptions;
	    
	    if (XenForo_Visitor::getUserId())
	    {
	        $params['visitor'] = XenForo_Visitor::getInstance();
	    }
	    else
	    {
	        $params['visitor'] = array(
	            'username' => 'Guest'
	        );
	    }
	    
		foreach ($this->_options AS $optionId => &$option)
		{
			if ($option['evaluate_template'])
			{
			    if (is_array($option['option_value']))
			    {
			        foreach ($option['option_value'] AS &$optionValue)
			        {
			            if (is_string($optionValue))
			            {
                            $optionValue = $this->_renderTemplate($optionValue, $params);
			            }
			        }
			    }
			    else
			    {
			        $option['option_value'] = $this->_renderTemplate($option['option_value'], $params);
			    }
			}
		}		
	}
	
	protected function _renderTemplate($template, array $params = array())
	{
	    extract($params);
	    
	    $compiler = new XenForo_Template_Compiler($template);
	    
	    XenForo_Application::disablePhpErrorHandler();
	    @eval($compiler->compile());
	    XenForo_Application::enablePhpErrorHandler();
	    
	    return htmlspecialchars_decode($__output, ENT_QUOTES);	    
	}
	
	protected function _prepareFieldsForTemplate($fields)
	{
		$templateFields = array();
		foreach ($fields AS $fieldId => $field)
		{
			if ($field['active'])
			{
				if ($field['field_type'] == 'checkbox'
						|| $field['field_type'] == 'select'
						|| $field['field_type'] == 'multiselect'
						|| $field['field_type'] == 'radio'
						|| $field['field_type'] == 'rating')
				{
					if (!is_array($field['field_choices']))
					{
						$choices = unserialize($field['field_choices']);
					}
		
					if (is_array($field['field_value']))
					{
						$text = '';
						foreach ($field['field_value'] as $choiceId)
						{
							$text .= ', ' . $choices[$choiceId];
						}
						$templateFields[$field['field_name']]['value'] = substr($text, 2);
					}
					else
					{
						if ($field['field_type'] == 'rating' && $field['field_value'] != '')
						{
							$templateFields[$field['field_name']]['value'] = $field['field_value'] . (new XenForo_Phrase('lpsf_rating_separator')) . XenForo_Application::getOptions()->lpsfRatingMax;
						}
						else
						{
							if (isset($choices[$field['field_value']]))
							{
								$templateFields[$field['field_name']]['value'] = $choices[$field['field_value']];
							}
							else
							{
								$templateFields[$field['field_name']]['value'] = '';
							}
						}
					}
				}
				else
				{
					$templateFields[$field['field_name']]['value'] = htmlspecialchars_decode($field['field_value']);
						
					if (($field['field_type'] == 'date' || $field['field_type'] == 'datetime') && $field['field_value'] != '')
					{
					    $dateTime = new DateTime($field['field_value'], XenForo_Locale::getDefaultTimeZone());
					}
					
					if ($field['field_type'] == 'date' && isset($dateTime))
					{
						$templateFields[$field['field_name']]['value'] = XenForo_Locale::date($dateTime, 'absolute');
					}
					
					if ($field['field_type'] == 'datetime' && isset($dateTime))
					{
					    $templateFields[$field['field_name']]['value'] = XenForo_Locale::dateTime($dateTime, 'absolute');
					}					
				}
		
				$templateFields[$field['field_name']]['field_type'] = $field['field_type'];
				$templateFields[$field['field_name']]['title'] = $field['title']->render();
			}
		}
		
		return $templateFields;
	}
	
	protected function _prepareOptionsForTemplate($options)
	{
		$preparedOptions = array();
		foreach ($options AS $optionId => &$option)
		{
			$preparedOptions[$option['option_id']] = $option['option_value'];
		}
		
		return $preparedOptions;
	}
	
	protected function _prepareOptions($options)
	{
		foreach ($options as $optionId => &$option)
		{
			// attempt to unserialize the option value
			$unserialize = @unserialize($option['option_value']);
			if ($unserialize)
			{
				$option['option_value'] = $unserialize;
			}			
		}
		
		return $options;
	}
	
	protected static function VerifyTemplate(array $option, &$value, &$error)
	{
		try
		{
			$compiler = new XenForo_Template_Compiler($value);
			$parsed = $compiler->lexAndParse();

			$compiler->setFollowExternal(false);
			$compiler->compileParsed($parsed, '', 0, 0);
		}
		catch (XenForo_Template_Compiler_Exception $e)
		{
			$error = $e->getMessage();
			return false;
		}

		return true;
	}
	
	public function setAttachmentHash($originalAttachmentId, $attachmentId, $attachmentHash)
	{
		$this->_attachmentHash = $attachmentHash;
		$this->_originalAttachmentId = $originalAttachmentId;
		$this->_attachmentId = $attachmentId;
		
		// loop through fields to replace reference to the attachment in WYSIWYG fields
		foreach ($this->_templateFields as $fieldId => &$field)
		{
			if ($field['field_type'] == 'wysiwyg')
			{
			    $this->_replaceAttachmentIds($field['value']);
			}
		}
		
		$this->_compileTemplateBasedOptionValues();
	}
	
	protected function _replaceAttachmentIds(&$field)
	{
	    $oldString = '[ATTACH=full]' . $this->_originalAttachmentId . '[/ATTACH]';
	    $newString = '[ATTACH=full]' . $this->_attachmentId . '[/ATTACH]';
	    $field = str_replace($oldString, $newString, $field);
	    
	    $oldString = '[ATTACH]' . $this->_originalAttachmentId . '[/ATTACH]';
	    $newString = '[ATTACH]' . $this->_attachmentId . '[/ATTACH]';
	    $field = str_replace($oldString, $newString, $field);
	}
}