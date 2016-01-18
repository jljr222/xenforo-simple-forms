<?php

class LiquidPro_SimpleForms_Route_Prefix_Forms implements XenForo_Route_Interface
{
	/**
	 * Match a specific route for an already matched prefix.
	 *
	 * @see XenForo_Route_Interface::match()
	 */
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'form_id');
		$action = $router->resolveActionAsPageNumber($action, $request);
		return $router->getRouteMatch('LiquidPro_SimpleForms_ControllerPublic_Form', $action, 'forms');
	}

	/**
	 * Method to build a link to the specified page/action with the provided
	 * data and params.
	 *
	 * @see XenForo_Route_BuilderInterface
	 */
	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		$action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'form_id', 'title');
	}
}