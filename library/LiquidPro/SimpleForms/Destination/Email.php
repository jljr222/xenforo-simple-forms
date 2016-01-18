<?php

class LiquidPro_SimpleForms_Destination_Email extends LiquidPro_SimpleForms_Destination_Abstract
{
	public function save()
	{
		$subject = $this->_options['email_subject_template']['option_value'];
		
		$recipient = trim($this->_options['email_recipient_email']['option_value']);
		if (strpos($recipient, ',') !== false)
		{
			$recipient = explode(',', $recipient);
			foreach ($recipient as &$recip)
			{
				$recip = trim($recip);
			}
		}
		
		$sender = trim($this->_options['email_sender_email']['option_value']);
		$format = $this->_options['email_format']['option_value'];
		
		// default to visitor's e-mail address, but stop execution for guests
		if ($sender == '')
		{
			if (!XenForo_Visitor::getUserId())
			{
				return false;
			}
			else
			{
				$sender = XenForo_Visitor::getInstance()->email;
			}
		}
		
		if ($this->_options['email_message_template']['option_value'] == '')
		{
			$message = '';
			
			foreach ($this->_templateFields AS $field)
			{
				if ($this->_options['email_hide_empty_fields']['option_value'] == array() || ($this->_options['email_hide_empty_fields']['option_value'] !== array() && $field['value'] != ''))
				{
					$message .= $field['title'] . ': ' . $field['value'] . PHP_EOL;
				}
			}
		}
		else
		{
			$message = $this->_options['email_message_template']['option_value'];
		}

		$transport = XenForo_Mail::getDefaultTransport();

		$mailObj = new Zend_Mail('utf-8');
		$mailObj->setSubject($subject)
			->addTo($recipient)
			->setFrom(XenForo_Application::getOptions()->defaultEmailAddress)
			->setReplyTo($sender);
		
		if ($this->_attachmentHash)
		{
			$attachmentModel = XenForo_Model::create('XenForo_Model_Attachment');
			
			$attachments = $attachmentModel->getAttachmentsByTempHash($this->_attachmentHash);
			foreach ($attachments as $attachmentId => $attachment)
			{
				$attachmentData = $attachmentModel->getAttachmentDataById($attachment['data_id']);
				$attachmentDataFile = $attachmentModel->getAttachmentDataFilePath($attachmentData);
				$fileOutput = new XenForo_FileOutput($attachmentDataFile);
				
				$mailAttachment = $mailObj->createAttachment($fileOutput->getContents());
				$mailAttachment->filename = $attachmentData['filename'];
				
				// delete the attachment as it is no longer needed
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_Attachment');
				$dw->setExistingData($attachment);	
				$dw->delete();
			}
		}			
			
		$options = XenForo_Application::get('options');
		$bounceEmailAddress = $options->bounceEmailAddress;
		if (!$bounceEmailAddress)
		{
			$bounceEmailAddress = $options->defaultEmailAddress;
		}
		$mailObj->setReturnPath($bounceEmailAddress);

		// create plain text message
		$bbCodeParserText = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Text'));
		$messageText = new XenForo_BbCode_TextWrapper($message, $bbCodeParserText);			
		
		if ($format == 'html')
		{
			// create html message
			$bbCodeParserHtml = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('HtmlEmail'));
			$messageHtml = new XenForo_BbCode_TextWrapper($message, $bbCodeParserHtml);			
			
			$mailObj->setBodyHtml(htmlspecialchars_decode($messageHtml))
				->setBodyText($messageText);
		}
		else
		{
			$mailObj->setBodyText($messageText);
		}

		$mailObj->send($transport);
	}
	
	public static function VerifySubjectTemplate(array $option, &$value, &$error)
	{
		return parent::VerifyTemplate($option, $value, $error);
	}

	public static function VerifySenderEmail(array $option, &$value, &$error)
	{
		return parent::VerifyTemplate($option, $value, $error);
	}
	
	public static function VerifyRecipientEmail(array $option, &$value, &$error)
	{
		return parent::VerifyTemplate($option, $value, $error);
	}	
	
	public static function VerifyDestination(array &$options, &$values)
	{
		$errors = array();

		if (isset($values['email_enabled'])) 
		{
			// check for required
			foreach ($options as $optionId => $option) 
			{
				if ($option['required'] && (!isset($values[$optionId]) || ($values[$optionId] === '' || $values[$optionId] === array()))) 
				{
					$errors['destination_options[' . $option['destination_id'] . '][' . $optionId . ']'] = new XenForo_Phrase('please_enter_value_for_all_required_fields');
				}
			}
		}
		
		return $errors;
	}
}