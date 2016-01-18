<?php

class LiquidPro_SimpleForms_AttachmentHandler_Form extends XenForo_AttachmentHandler_Abstract
{
	protected $_formModel = null;

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

	/**
	 * Determines if attachments and be uploaded and managed in this context.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::_canUploadAndManageAttachments()
	 */
	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		return true;
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