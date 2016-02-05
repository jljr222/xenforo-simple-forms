<?php

class LiquidPro_SimpleForms_Destination_Conversation extends LiquidPro_SimpleForms_Destination_Abstract
{
	public function save()
	{
		$userId = $this->_options['conversation_send_by_user_id']['option_value'] != '' ? $this->_options['conversation_send_by_user_id']['option_value'] : XenForo_Visitor::getUserId();
		$recipientUserId = $this->_options['conversation_recipient_user_id']['option_value'];
		
		$recipientUserId = explode(',', $recipientUserId);
		
		// guests can't participate in conversations
		if (!$userId)
		{
			return false;
		}
		
		// users can't send themselves messages
		if ($userId == $recipientUserId)
		{
			return false;
		}

		$userModel = XenForo_Model::create('XenForo_Model_User');
		$user = $userModel->getUserById($userId);	

		if ($this->_options['conversation_message_template']['option_value'] == '')
		{
			$message = '';
			
			foreach ($this->_templateFields AS $field)
			{
				if ($this->_options['conversation_hide_empty_fields']['option_value'] == array() || ($this->_options['conversation_hide_empty_fields']['option_value'] !== array() && $field['value'] != ''))
				{				
					$message .= $field['title'] . ':' . PHP_EOL;
					if ($field['field_type'] == 'wysiwyg')
					{
						$message .= '[INDENT="1"]' . $field['value'] . '[/INDENT]' . PHP_EOL;
					}
					else 
					{
						$message .= '[INDENT="1"][B]' . $field['value'] . '[/B][/INDENT]' . PHP_EOL;
					}
				}
			}
		}
		else
		{
			$message = $this->_options['conversation_message_template']['option_value'];
		}		
		$message = XenForo_Helper_String::autoLinkBbCode($message);

		$locked = ($this->_options['conversation_locked']['option_value'] !== array()) ? true : false;
		
		$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
		$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $user);
		$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $message);	
		$conversationDw->set('user_id', $user['user_id']);
		$conversationDw->set('username', $user['username']);
		$conversationDw->set('title', $this->_options['conversation_title_template']['option_value']);
		$conversationDw->set('conversation_open', $locked ? 0 : 1);
		$conversationDw->addRecipientUserIds($recipientUserId);

		$messageDw = $conversationDw->getFirstMessageDw();
		$messageDw->set('message', $message);
		if ($this->_attachmentHash)
		{
			$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_ATTACHMENT_HASH, $this->_attachmentHash);
		}	
		
		$conversationDw->preSave();
		
		$conversationDw->save();
		$conversation = $conversationDw->getMergedData();

		$this->_getConversationModel()->markConversationAsRead(
			$conversation['conversation_id'], $user['user_id'], XenForo_Application::$time
		);
	}
	
	public static function VerifyTitleTemplate(array $option, &$value, &$error)
	{
		return parent::VerifyTemplate($option, $value, $error);
	}
	
	public static function VerifySendUserId(array $option, &$value, &$error)
	{
		if ($value != '') 
		{
			$userModel = XenForo_Model::create('XenForo_Model_User');
			if (!$userModel->getUserById($value)) 
			{
				$error = new XenForo_Phrase('requested_user_not_found');
				return false;
			}			
		}
		
		return true;
	}
	
	public static function VerifyRecipientUserId(array $option, &$value, &$error)
	{
		if ($value != '') 
		{
			$users = explode(',', $value);
			if ($users[count($users) - 1] == '')
			{
				unset($users[count($users) - 1]);
				
				// find the last , in the string and remove anything after it
				$value = substr($value, 0, strrpos($value, ','));
			}
			
			foreach ($users as $user)
			{
				$userModel = XenForo_Model::create('XenForo_Model_User');
				if (!$userModel->getUserById($user))
				{
					$error = new XenForo_Phrase('requested_user_not_found');
					return false;
				}
			}
		}
		
		return true;
	}
	
	public static function VerifyMessageTemplate(array $option, &$value, &$error)
	{
		return parent::VerifyTemplate($option, $value, $error);
	}
	
	public static function VerifyDestination(array &$options, &$values)
	{
		$errors = array();
		
		if (isset($values['conversation_enabled']))  
		{
			// check for required
			foreach ($options as $optionId => $option) 
			{
				if ($option['required'] && ($values[$optionId] === '' || $values[$optionId] === array())) 
				{
					$errors['destination_options[' . $option['destination_id'] . '][' . $optionId . ']'] = new XenForo_Phrase('please_enter_value_for_all_required_fields');
				}
			}
			
			if ($values['conversation_send_by_user_id'] == $values['conversation_recipient_user_id'])
			{
				$errors['destination_options[' . $option['destination_id'] . '][conversation_send_by_user_id]'] = new XenForo_Phrase('conversation_sender_and_recipient_cannot_be_the_same');
			}			
		}
		
		return $errors;
	}

	public static function getAttachmentConstraints($form_destination_id, $destinationOptions)
	{
		$contentData = array();
		return self::_getAttachmentConstraints($form_destination_id, 'conversation_message', $contentData);
	}
	
	/**
	 * @return XenForo_Model_Conversation
	 */
	protected function _getConversationModel()
	{
		return XenForo_Model::create('XenForo_Model_Conversation');
	}
}