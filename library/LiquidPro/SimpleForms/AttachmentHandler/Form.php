<?php

class LiquidPro_SimpleForms_AttachmentHandler_Form extends XenForo_AttachmentHandler_Abstract
{
	protected $_formModel = null;
	protected $_destinationOptionModel = null;

	/**
	 * Key of primary content in content data array.
	 *
	 * @var string
	 */
	protected $_contentIdKey = 'response_id';

	/**
	 * Route to get to a post
	 *
	 * @var string
	 */
	protected $_contentRoute = 'form';

	/**
	 * Name of the phrase that describes the conversation_message content type
	 *
	 * @var string
	 */
	protected $_contentTypePhraseKey = 'form'	;

	protected $_constraints = null;
	/**
	 * Determines if attachments and be uploaded and managed in this context.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::_canUploadAndManageAttachments()
	 */
	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		$this->_constraints = null;
		$destinationOptionModel = $this->_getDestinationOptionModel();
		$attachmentTypes = $destinationOptionModel->getAttachmentsDestinationHandlers($contentData['form_id']);
		if ($attachmentTypes)
		{
			foreach($attachmentTypes as $form_destination_id => $row)
			{
				$handler_class = $row['handler_class'];
				if (method_exists($handler_class, 'getAttachmentConstraints'))
				{
					$destinationOptions = $destinationOptionModel->getDestinationOptionsByFormDestinationId($form_destination_id);					
					//$constraints2 =
					$constraints = call_user_func_array(array($handler_class, 'getAttachmentConstraints'), array($form_destination_id, $destinationOptions));					
					// make sure all destinations actually support attachments.
					if (empty($constraints))
					{
						return false;
					}
					// accept the first constraint
					if ($this->_constraints === null)
					{
						$this->_constraints = $constraints;
					}
/*
					//var_export($constraints2);
					// merge constraints. Most restrictive wins
					if ($constraints2['size'] && $constraints2['size'] < $constraints['size'])
					{
						$constraints['size'] = $constraints2['size'];
					}
					if ($constraints2['count'] && $constraints2['count'] < $constraints['count'])
					{
						$constraints['count'] = $constraints2['count'];
					}
					if ($constraints2['width'] && $constraints2['width'] < $constraints['width'])
					{
						$constraints['width'] = $constraints2['width'];
					}
					if ($constraints2['height'] && $constraints2['height'] < $constraints['height'])
					{
						$constraints['height'] = $constraints2['height'];
					}
*/					
				}
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Determines if the specified attachment can be viewed.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::_canViewAttachment()
	 */
	protected function _canViewAttachment(array $attachment, array $viewingUser)
	{
		return true;
	}

	/**
	 * Code to run after deleting an associated attachment.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::attachmentPostDelete()
	 */
	public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
	{

	}
	
	public function getAttachmentConstraints()
	{
		if ($this->_constraints !== null)
		{
			return $this->_constraints;
		}
		return parent::getAttachmentConstraints();
	}

	/**
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected function _getDestinationOptionModel()
	{
		if (!$this->_destinationOptionModel)
		{
			$this->_destinationOptionModel = XenForo_Model::create('LiquidPro_SimpleForms_Model_DestinationOption');
		}

		return $this->_destinationOptionModel;
	}

	/**
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected function _getFormModel()
	{
		if (!$this->_formModel)
		{
			$this->_formModel = XenForo_Model::create('LiquidPro_SimpleForms_Model_Form');
		}

		return $this->_formModel;
	}

	/**
	 * @see XenForo_AttachmentHandler_Abstract::_getContentRoute()
	 */
	protected function _getContentRoute()
	{
		return 'forms';
	}
}