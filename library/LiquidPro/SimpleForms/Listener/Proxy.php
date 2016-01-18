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
	 * Controller Pre-Dispatch Listeners
	 */
	public static function controllerPreDispatch(XenForo_Controller $controller, $action)
	{
		$controllerName = get_class($controller);

		// bypass license validation on these pages
		if ($controllerName == 'LiquidPro_SimpleForms_ControllerAdmin_User' || $controllerName == 'LiquidPro_SimpleForms_ControllerPublic_Forum')
		{
		    return true;
		}		
		
		// get the current license key option
		$options = XenForo_Application::getOptions();
		$licenseKey = $options->simpleFormsLicenseKey;
	
		// if we are on a page that should check the license key and they haven't entered a license key, we should do some checking
		if ($licenseKey == '' && ($controllerName == 'XenForo_ControllerAdmin_Option' || substr($controllerName, 0, strlen('LiquidPro_SimpleForms_Controller')) == 'LiquidPro_SimpleForms_Controller'))
		{
			$licenseFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'license.dat';
			$fileLicenseKey = @file_get_contents($licenseFile);
				
			$localFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'local.dat';
				
			$fileLicenseKeyValid = LiquidPro_SimpleForms_Option_LicenseKey::VerifyLicense($fileLicenseKey);
	
			// if the file license key is valid, we should convert it over the the license key option
			if ($fileLicenseKeyValid)
			{
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
				$dw->setExistingData('simpleFormsLicenseKey');
				$dw->setOption(XenForo_DataWriter_Option::OPTION_REBUILD_CACHE, false);
				$dw->set('option_value', $fileLicenseKey);
				$dw->save();
	
				$options->simpleFormsLicenseKey = $fileLicenseKey;
				$dataRegistry = XenForo_Model::create('XenForo_Model_DataRegistry');
				$dataRegistry->set('options', $options);
	
				// attempt to remove license.dat and local.dat
				@unlink($licenseFile);
				@unlink($localFile);
			}
				
			// otherwise, we should tell the user that they haven't entered a license key
			else
			{
				if (substr($controllerName, 0, strlen('LiquidPro_SimpleForms_Controller')) == 'LiquidPro_SimpleForms_Controller')
				{
					$controllerResponse = new XenForo_ControllerResponse_Error();
					$controllerResponse->errorText = 'You have not entered a license key. Please set your license key in the <a href="' . XenForo_Link::buildAdminLink('options/list', array('group_id' => 'LiquidPro_SimpleForms')) . '">LiquidPro Simple Forms options</a>.';
					$controllerResponse->responseCode = 200;
						
					throw new XenForo_ControllerResponse_Exception($controllerResponse);
				}
			}
		}
	
		// otherwise, we should verify that the license is valid
		else if (substr($controllerName, 0, strlen('LiquidPro_SimpleForms_Controller')) == 'LiquidPro_SimpleForms_Controller')
		{
			LiquidPro_SimpleForms_Option_LicenseKey::VerifyLicense($licenseKey);
		}
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