<?php

class LiquidPro_SimpleForms_DestinationOption_ThreadPoll
{
	public static function renderOption(XenForo_View $view, array $option)
	{
		$maxResponses = XenForo_Application::get('options')->pollMaximumResponses;
		if ($maxResponses == 0)
		{
			$maxResponses = 10; // number to create for non-JS users
		}
		if ($maxResponses > 2)
		{
			$pollExtraArray = array_fill(0, $maxResponses - 2, true);
		}
		else
		{
			$pollExtraArray = array();
		}
		
		return $view->createTemplateObject('lpsf_destination_option_thread_poll', array(
			'option' => $option,
			'pollExtraArray' => $pollExtraArray,
			'xfVersion' => XenForo_Application::$versionId
		));
	}
}