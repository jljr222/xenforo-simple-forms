<?php

class LiquidPro_SimpleForms_DataWriter_ForumForm extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'lpsf_forum_form' => array(
				'forum_form_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'forum_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'replace_button' => array('type' => self::TYPE_BOOLEAN, 'required' => true, 'default' => false),
				'form_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'button_text' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 75)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('lpsf_forum_form' => $this->_getForumFormModel()->getForumFormById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'forum_form_id = ' . $this->_db->quote($this->getExisting('forum_form_id'));
	}

	/**
	 * @return LiquidPro_SimpleForms_Model_ForumForm
	 */
	protected function _getForumFormModel()
	{
		return XenForo_Model::create('LiquidPro_SimpleForms_Model_ForumForm');
	}
}