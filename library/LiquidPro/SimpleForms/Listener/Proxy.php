<?php

class LiquidPro_SimpleForms_Listener_Proxy
{
	/*
	 * Load Class Controller Listeners
	 */
	public static function loadForumAdminController($class, array &$extend)
	{
		$extend[] = 'LiquidPro_SimpleForms_Listener_Proxy_AdminControllerForum';
	}
	
	public static function loadUserAdminController($class, array &$extend)
	{
		$extend[] = 'LiquidPro_SimpleForms_Listener_Proxy_AdminControllerUser';
	}
	
	public static function loadUserGroupPromotionAdminController($class, array &$extend)
	{
		$extend[] = 'LiquidPro_SimpleForms_Listener_Proxy_AdminControllerUserGroupPromotion';
	}

	public static function loadUserDataWriter($class, array &$extend)
	{
		$extend[] = 'LiquidPro_SimpleForms_Listener_Proxy_DataWriterUser';
	}
	 
	public static function loadForumController($class, array &$extend)
	{
	    $extend[] = 'LiquidPro_SimpleForms_Listener_Proxy_ControllerForum';
	}
	
	/*
	 * Load Class Model Listeners
	 */
	public static function loadForumModel($class, array &$extend)
	{
		$extend[] = 'LiquidPro_SimpleForms_Listener_Proxy_ModelForum';
	}
	
	/*
	 * Navigation Tabs Listeners
	 */
	public static function navigationTabs(array &$extraTabs, $selectedTabId)
	{
		$options = XenForo_Application::getOptions();
	
		if (!$options->disableFormsTab && XenForo_Visitor::getInstance()->hasPermission('form', 'viewFormsList'))
		{
			$extraTabs['forms'] = array(
				'title' => new XenForo_Phrase('forms_tab'),
				'href' => XenForo_Template_Helper_Core::link('forms'),
				'position' => '',
				'selected' => ($selectedTabId == 'forms')
			);
		}
	}
	
	/**
	 * Template Create Listeners
	 */
	public static function templateCreateForumView(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if (array_key_exists('forum', $params) && array_key_exists('lpsf_form_id', $params['forum']))
	    {
	        $params['form'] = self::_getFormModel()->getFormById($params['forum']['lpsf_form_id']);
	    }
	} 
	
	/**
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected static function _getFormModel()
	{
	    return XenForo_Model::create('LiquidPro_SimpleForms_Model_Form');
	}
}