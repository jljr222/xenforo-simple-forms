<?php

class LiquidPro_SimpleForms_DataWriter_Helper_Field
{
	public static function VerifyFieldName(&$fieldName, XenForo_DataWriter $dw, $inputName = false)
	{
		if (preg_match('/[^a-zA-Z0-9_]/', $fieldName))
		{
			$dw->error(new XenForo_Phrase('please_enter_an_id_using_only_alphanumeric'), $inputName);
			return false;
		}
		
		if ($fieldName !== $dw->getExisting('field_name'))
		{
			$fieldModel = XenForo_Model::create('LiquidPro_SimpleForms_Model_Field');
			
			if (in_array($dw->get('type'), array('global', 'template')))
			{
				$existingField = $fieldModel->getFieldByTypeAndName($dw->get('type'), $fieldName);
			}
			else
			{
				$existingField = $fieldModel->getFieldByFormIdAndName($dw->get('form_id'), $fieldName);
			}
		}
		
		if (isset($existingField) && $existingField)
		{
			$dw->error(new XenForo_Phrase('field_ids_must_be_unique'), $inputName);
			return false;
		}
		
		return true;
	}
	
	public static function VerifyDefaultValue(&$defaultValue, XenForo_DataWriter $dw, $inputName = false)
	{
	    if ($defaultValue == '')
	    {
	        return true;
	    }
	    
	    $fieldType = $dw->get('field_type');
	    
	    if ($fieldType == 'datetime')
	    {
	        if (is_string($defaultValue))
	        {
	            $temp = explode(' ', $defaultValue);
	            if (count($temp) <> 2)
	            {
	                $dw->error(new XenForo_Phrase('lpsf_please_enter_a_datetime_in_format'), $inputName);
	                return false;	                
	            }
	            
	            $defaultValue = array(
	                'date' => $temp[0],
	                'time' => $temp[1]
	            );
	        }
	        
            if (is_array($defaultValue) && array_key_exists('date', $defaultValue) && array_key_exists('time', $defaultValue))
            {
                // validate
                $dateTimeStr = $defaultValue['date'] . ' ' . $defaultValue['time'];
                $dateTime = DateTime::createFromFormat('Y-m-d g:ia', $dateTimeStr);
                
                if (!$dateTime)
                {
                    $dw->error(new XenForo_Phrase('lpsf_please_enter_a_datetime_in_format'), $inputName);
                    return false;
                }         

                $defaultValue = $dateTimeStr; 
            }
	    }
	    
	    if ($fieldType == 'time')
	    {
            $dateTime = DateTime::createFromFormat('g:ia', $defaultValue);
            if (!$dateTime)
            {
                $dw->error(new XenForo_Phrase('lpsf_please_enter_a_time_in_format'), $inputName);
                return false;
            }
	    }	    
	    
	    if ($fieldType == 'date')
	    {
	        $dateTime = DateTime::createFromFormat('Y-m-d', $defaultValue);
            if (!$dateTime)
            {
	            $dw->error(new XenForo_Phrase('lpsf_please_enter_a_date_in_format'), $inputName);
	            return false;
	        }
	    }
	    
	    return true;
	}
}