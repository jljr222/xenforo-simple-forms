<?php

class LiquidPro_SimpleForms_Destination_Thread extends LiquidPro_SimpleForms_Destination_Abstract
{
	protected $_thread;
	
	public function save()
	{
		$forumId = $this->_options['thread_forum_node_id']['option_value'];
		$forum = $this->_getForumModel()->getForumById($forumId);
		
		$userId = $this->_options['thread_post_by_user_id']['option_value'] != '' ? $this->_options['thread_post_by_user_id']['option_value'] : XenForo_Visitor::getUserId();
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
		
		$input['title'] = htmlspecialchars_decode($this->_options['thread_title_template']['option_value']);
		
		if ($this->_options['thread_message_template']['option_value'] == '')
		{ 
			$message = '';
			
			foreach ($this->_templateFields AS $field)
			{
				if ($this->_options['thread_hide_empty_fields']['option_value'] == array() || ($this->_options['thread_hide_empty_fields']['option_value'] !== array() && $field['value'] != ''))
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
		    $this->_replaceAttachmentIds($this->_options['thread_message_template']['option_value']);

		    $message = $this->_options['thread_message_template']['option_value'];
		}
		$message = XenForo_Helper_String::autoLinkBbCode($message);
		
		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
		$writer->set('user_id', $user['user_id']);
		$writer->set('username', $user['username']);
		$writer->set('title', $input['title']);
		$writer->set('node_id', $forumId);
		
		$prefixId = 0;
		if (!empty($forum['default_prefix_id']))
		{
			$prefixId = $forum['default_prefix_id'];
		}

		// set the thread id from the options
		if (!empty($this->_options['thread_prefix']['option_value']))
		{
			$prefixId = $this->_options['thread_prefix']['option_value'];
		}
		
		// verify the thread is usable
		if (!array_key_exists('thread_prefix', $this->_options) || !$this->_getPrefixModel()->verifyPrefixIsUsable($this->_options['thread_prefix']['option_value'], $forum['node_id']))
		{
			$prefixId = 0; // not usable, just blank it out
		}
		
		$writer->set('prefix_id', $prefixId);

		if ($this->_options['thread_sticky']['option_value'] && $this->_getForumModel()->canStickUnstickThreadInForum($forum, $errorPhraseKey, null, $user))
		{
			$writer->set('sticky', $this->_options['thread_sticky']['option_value']);
		}
		
		if ($this->_options['thread_lock']['option_value'])
		{
			$writer->set('discussion_open', 0);
		}
		
		$postModel = XenForo_Model::create('XenForo_Model_Post');
		
		$writer->set('discussion_state', $postModel->getPostInsertMessageState(array(), $forum));

		$postWriter = $writer->getFirstMessageDw();
		$postWriter->set('message', $message);
		if ($this->_attachmentHash)
		{
			$postWriter->setExtraData(XenForo_DataWriter_DiscussionMessage::DATA_ATTACHMENT_HASH, $this->_attachmentHash);
		}
		$postWriter->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);

		$writer->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);

		$writer->preSave();
		
		if ($this->_options['thread_poll']['option_value']['question'] != '')
		{
			$pollInput = array();
			$pollInput['question'] = $this->_options['thread_poll']['option_value']['question'];
			$pollInput['public_votes'] = (array_key_exists('public_votes', $this->_options['thread_poll']['option_value'])) ? $this->_options['thread_poll']['option_value']['public_votes'] : false;
			
			// 1.4 options
			if (XenForo_Application::$versionId >= 1040000)
			{
				$pollInput['change_vote'] = (array_key_exists('change_vote', $this->_options['thread_poll']['option_value'])) ? $this->_options['thread_poll']['option_value']['change_vote'] : false;
				$pollInput['view_results_unvoted'] = (array_key_exists('view_results_unvoted', $this->_options['thread_poll']['option_value'])) ? $this->_options['thread_poll']['option_value']['view_results_unvoted'] : false;
				
				switch ($this->_options['thread_poll']['option_value']['max_votes_type'])
				{
					case 'single':
						$pollInput['max_votes'] = 1;
						break;
				
					case 'unlimited':
						$pollInput['max_votes'] = 0;
						break;
					
					default:
						$pollInput['max_votes'] = $this->_options['thread_poll']['option_value']['max_votes_value'];
				}
			}
			// 1.3 options
			else if (XenForo_Application::$versionId >= 1030000)
			{
				$pollInput['multiple'] = (array_key_exists('multiple', $this->_options['thread_poll']['option_value'])) ? $this->_options['thread_poll']['option_value']['multiple'] : false;
			}
			
			$pollWriter = XenForo_DataWriter::create('XenForo_DataWriter_Poll');

			// close date
			if (isset($this->_options['thread_poll']['option_value']['close']))
			{
				if (!$this->_options['thread_poll']['option_value']['close_length'])
				{
					$pollWriter->error(new XenForo_Phrase('please_enter_valid_length_of_time'));
				}
				else
				{
					$pollInput['close_date'] = strtotime('+' . $this->_options['thread_poll']['option_value']['close_length'] . ' ' . $this->_options['thread_poll']['option_value']['close_units']);
				}
			}	
			
			$pollWriter->bulkSet(
				$pollInput
			);
			$pollWriter->set('content_type', 'thread');
			$pollWriter->set('content_id', 0); // changed before saving
			$pollWriter->addResponses($this->_options['thread_poll']['option_value']['responses']);
			$pollWriter->preSave();
			$writer->mergeErrors($pollWriter->getErrors());

			$writer->set('discussion_type', 'poll', '', array('setAfterPreSave' => true));
		}			
		
		$writer->save();

		$thread = $writer->getMergedData();
		
		if (isset($pollWriter))
		{
			$pollWriter->set('content_id', $thread['thread_id'], '', array('setAfterPreSave' => true));
			$pollWriter->save();
		}			
		
		// set the thread
		$this->_thread = $thread;
		
		$threadModel = XenForo_Model::create('XenForo_Model_Thread');
		$threadModel->markThreadRead($thread, $forum, XenForo_Application::$time);
		
		// check to see if we need to watch the thread, and if we can watch the thread
		if ($this->_options['thread_automatically_watch']['option_value'] != '' && $threadModel->canWatchThread($thread, $forum))
		{
			$threadWatchModel = XenForo_Model::create('XenForo_Model_ThreadWatch');
			$threadWatchModel->setThreadWatchState(XenForo_Visitor::getUserId(), $thread['thread_id'], $this->_options['thread_automatically_watch']['option_value']);
		}
	}
	
	public function redirect()
	{
		return XenForo_Link::buildPublicLink('threads', $this->_thread);
	}
	
	public static function VerifyForumNodeId(array $option, &$value, &$error)
	{
		$forumModel = XenForo_Model::create('XenForo_Model_Forum');
		if (!$forumModel->getForumById($value))
		{
			$error = new XenForo_Phrase('requested_forum_not_found');
			return false;
		}
		
		return true;
	}
	
	public static function VerifyTitleTemplate(array $option, &$value, &$error)
	{
		return parent::VerifyTemplate($option, $value, $error);
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
	
	public static function VerifyPoll(array $option, &$value, &$error)
	{
		$value = unserialize($value);
		
		// cleanse the responses
		foreach ($value['responses'] as $key => $response)
		{
			if ($response == '')
				unset($value['responses'][$key]);
		}
		
		// set max_votes (1.4 compat)
		if (isset($value['multiple']) && !isset($value['max_votes']))
		{
			$value['max_votes'] = ($value['multiple'] == 0) ? 1 : 0;
			$value['max_votes_type'] = ($value['multiple'] == 1) ? 'unlimited' : 'single';
		}
		else if (!isset($value['multiple']) && !isset($value['max_votes']))
		{
			$value['max_votes'] = 1;
			$value['max_votes_type'] = 'single';
		}
		
		$optionValue = $value;
		$value = serialize($value);
		
		// only validate if the question isn't blank
		if ($optionValue['question'] != '')
		{
			if (count($optionValue['responses']) < 2)
			{
				$error = new XenForo_Phrase('please_enter_at_least_two_poll_responses');
				return false;
			}			
		}
		
		return true;
	}
	
	public static function VerifyDestination(array &$options, &$values)
	{
		$errors = array();
		
		if (isset($values['thread_enabled'])  )
		{
			// check for required
			foreach ($options as $optionId => $option) 
			{
				if ($option['required'] && ($values[$optionId] === '' || $values[$optionId] === array())) 
				{
					$errors['destination_options[' . $option['destination_id'] . '][' . $optionId . ']'] = new XenForo_Phrase('please_enter_value_for_required_field_x', array('field' => $option['title']));
				}
			}	
		}
		
		return $errors;
	}

	public static function getAttachmentConstraints($form_destination_id, $destinationOptions)
	{
		if (empty($destinationOptions['thread_forum_node_id']['option_value']))
		{
			return array();
		}

		$contentData = array('node_id' => $destinationOptions['thread_forum_node_id']['option_value']);
		return self::_getAttachmentConstraints('post', $contentData);
	}

	/**
	 * @return XenForo_Model_Forum
	 */
	protected function _getForumModel()
	{
		return XenForo_Model::create('XenForo_Model_Forum');
	}
	
	/**
	 * @return XenForo_Model_ThreadPrefix
	 */
	protected function _getPrefixModel()
	{
		return XenForo_Model::create('XenForo_Model_ThreadPrefix');
	}
}