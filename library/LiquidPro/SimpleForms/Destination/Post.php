<?php

class LiquidPro_SimpleForms_Destination_Post extends LiquidPro_SimpleForms_Destination_Abstract
{
	protected $_post;
	
	public function save()
	{
		$threadId = $this->_options['post_thread_id']['option_value'];
		$threadModel = $this->_getThreadModel();
		$thread = $threadModel->getThreadById($threadId);
		$forum = $this->_getForumModel()->getForumById($thread['node_id']);
		
		$userId = $this->_options['post_post_by_user_id']['option_value'] != '' ? $this->_options['post_post_by_user_id']['option_value'] : XenForo_Visitor::getUserId();
		if (!$userId)
		{
			$user = array(
				'username' => 'Guest',
				'user_id' => null
			);
		}
		else
		{
			$userModel = XenForo_Model::create('XenForo_Model_User');
			$user = $userModel->getUserById($userId);
		}
		
		if ($this->_options['post_message_template']['option_value'] == '')
		{
			$message = '';
			
			foreach ($this->_templateFields AS $field)
			{
				if ($this->_options['post_hide_empty_fields']['option_value'] == array() || ($this->_options['post_hide_empty_fields']['option_value'] !== array() && $field['value'] != ''))
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
			$message = $this->_options['post_message_template']['option_value'];
		}
		$message = XenForo_Helper_String::autoLinkBbCode($message);			

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
		$writer->set('user_id', $user['user_id']);
		$writer->set('username', $user['username']);
		$writer->set('message', $message);
		$writer->set('message_state', $this->_getPostModel()->getPostInsertMessageState($thread, $forum));
		$writer->set('thread_id', $threadId);
		$writer->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
		if ($this->_attachmentHash)
		{
			$writer->setExtraData(XenForo_DataWriter_DiscussionMessage::DATA_ATTACHMENT_HASH, $this->_attachmentHash);
		}			
		$writer->preSave();
		
		$writer->save();
		$post = $writer->getMergedData();
		
		// set the post
		$this->_post = $post;
	}
	
	public function redirect()
	{
		return XenForo_Link::buildPublicLink('posts', $this->_post);
	}	
	
	public static function VerifyThreadId(array $option, &$value, &$error)
	{
		$threadModel = XenForo_Model::create('XenForo_Model_Thread');
		if (!$threadModel->getThreadById($value)) 
		{
			$error = new XenForo_Phrase('requested_thread_not_found');
			return false;
		}
		
		return true;
	}
	
	public static function VerifyPostUserId(array $option, &$value, &$error)
	{
		if ($value != '') 
		{
			$userModel = XenForo_Model::create('XenForo_Model_user');
			if (!$userModel->getUserById($value)) 
			{
				$error = new XenForo_Phrase('requested_user_not_found');
				return false;
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

		if (isset($values['post_enabled']))  
		{
			// check for required
			foreach ($options as $optionId => $option) 
			{
				if ($option['required'] && ($values[$optionId] === '' || $values[$optionId] === array())) 
				{
					$errors['destination_options[' . $option['destination_id'] . '][' . $optionId . ']'] = new XenForo_Phrase('please_enter_value_for_all_required_fields');
				}
			}	
		}
		
		return $errors;
	}
	
	/**
	 * @return XenForo_Model_Thread
	 */
	protected function _getThreadModel()
	{
		return XenForo_Model::create('XenForo_Model_Thread');
	}
	
	/**
	 * @return XenForo_Model_Post
	 */
	protected function _getPostModel()
	{
		return XenForo_Model::create('XenForo_Model_Post');
	}
	
	/**
	 * @return XenForo_Model_Forum
	 */
	protected function _getForumModel()
	{
		return XenForo_Model::create('XenForo_Model_Forum');
	}
}