<?php

class LiquidPro_SimpleForms_Model_ForumForm extends XenForo_Model
{
	public function getForumFormById($forumFormId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM `lpsf_forum_form`
			WHERE `forum_form_id` = ?
		', $forumFormId);
	}
}