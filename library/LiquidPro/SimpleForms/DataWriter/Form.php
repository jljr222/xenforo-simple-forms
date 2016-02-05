<?php

class LiquidPro_SimpleForms_DataWriter_Form extends XenForo_DataWriter
{
	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'requested_form_not_found';
	
	/**
	 * Returns all xf_node fields, plus form-specific fields
	 */
	protected function _getFields()
	{
		return array(
			'lpsf_form' => array(
				'form_id'                => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'title'                  => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 50, 'requiredError' => 'please_enter_valid_title'),
				'description'            => array('type' => self::TYPE_STRING, 'default' => ''),
				'active'                 => array('type' => self::TYPE_BOOLEAN, 'default' => 0, 'required' => true),
				'hide_from_list'         => array('type' => self::TYPE_BOOLEAN, 'default' => 0, 'required' => true),
				'max_responses'          => array('type' => self::TYPE_UINT, 'default' => 0, 'required' => true),
				'max_responses_per_user' => array('type' => self::TYPE_UINT, 'default' => 0, 'required' => true),
				'redirect_method'        => array('type' => self::TYPE_STRING, 'allowedValues' => array('url', 'destination', 'self'), 'required' => true),
				'redirect_url'           => array('type' => self::TYPE_STRING, 'default' => '', 'required' => false),
				'redirect_destination'   => array('type' => self::TYPE_UINT, 'required' => false),
				'complete_message'       => array('type' => self::TYPE_STRING, 'default' => '', 'required' => false),
				'start_date'             => array('type' => self::TYPE_UNKNOWN, 'verification' => array('$this', '_verifyDate')),
				'end_date'               => array('type' => self::TYPE_UNKNOWN, 'verification' => array('$this', '_verifyDate')),
				'css'                    => array('type' => self::TYPE_STRING, 'required' => false, 'default' => ''),
				'require_attachment'     => array('type' => self::TYPE_UINT, 'default' => 0, 'min' => 0, 'max' => 20, 'required' => true),
				'display_order'          => array('type' => self::TYPE_UINT),
			    'header_html'            => array('type' => self::TYPE_STRING, 'default' => ''),
			    'footer_html'            => array('type' => self::TYPE_STRING, 'default' => '')
			)
		);
	}
	
	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'form_id = ' . $this->_db->quote($this->getExisting('form_id'));
	}
	
	protected function _preDelete()
	{
		$db = $this->_db;
		$formId = $this->get('form_id');
		$formIdQuoted = $db->quote($formId);
		
		$db->delete('lpsf_response_field', "field_id IN (SELECT field_id FROM lpsf_field WHERE form_id = $formIdQuoted)");
		$db->delete('lpsf_response', "form_id = $formIdQuoted");
		$db->delete('lpsf_form_destination_option', "form_destination_id IN (SELECT form_destination_id FROM lpsf_form_destination WHERE form_id = $formIdQuoted)");
		$db->delete('lpsf_form_destination', "form_id = $formIdQuoted");
		$db->delete('lpsf_field', "form_id = $formIdQuoted");
		
		parent::_preDelete();
	}
	
	protected function _preSave()
	{
		// if we are redirecting to a destination, make sure it is enabled
		if ($this->get('redirect_destination'))
		{
			// get the destination
			$redirectDestination = $this->_getDestinationModel()->getDestinationById($this->get('redirect_destination'));
		}
		
		parent::_preSave();
	}
	
	protected function _postSave()
	{
		if ($this->isInsert())
		{
			// insert a dummy page
			$pageDw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Page');
			$pageDw->bulkSet(array(
				'form_id' => $this->get('form_id'),
				'page_number' => 1,
				'title' => '',
				'description' => ''
			));
			$pageDw->save();
		}

		parent::_postSave();
	}

	protected function _postSaveAfterTransaction()
	{
		if ($this->isInsert())
		{
			$this->getModelFromCache('XenForo_Model_Permission')->rebuildPermissionCache();
		}
	}
	
	/**
	* Gets the actual existing data out of data that was passed in. See parent for explanation.
	*
	* @param mixed
	*
	* @return array|false
	*/
	protected function _getExistingData($data)
	{
		$formModel = $this->_getFormModel();
		
		if (!$formId = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		$form = $formModel->getFormById($formId);
		if (!$form)
		{
			return false;
		}

		return $this->getTablesDataFromArray($form);
	}
	
	protected function _verifyDate(&$date)
	{
		$date = serialize($date);
		return true;
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected function _getFormModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Form');
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_DestinationOption
	 */
	protected function _getDestinationOptionModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_DestinationOption');
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Destination
	 */
	protected function _getDestinationModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Destination');
	}
	
	/**
	 * @return LiquidPro_SimpleForms_DataWriter_Page
	 */
	protected function _getPageDataWriter()
	{
	    return XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Page');
	}
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Page
	 */
	protected function _getPageModel()
	{
	    return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Page');
	}
}