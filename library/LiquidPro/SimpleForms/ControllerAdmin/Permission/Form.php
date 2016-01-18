<?php

class LiquidPro_SimpleForms_ControllerAdmin_Permission_Form extends XenForo_ControllerAdmin_Permission_Abstract
{
	protected function _preDispatch($action)
	{
		parent::_preDispatch($action);
		$this->assertAdminPermission('form');
	}

	/**
	 * For a single form, shows page with options to edit node/user group/user permissions.
	 * If no node is specified, uses the nodesindex action instead.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionFormOptions()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);

		$permissionSets = $this->_getPermissionModel()->getUserCombinationsWithContentPermissions('form');
		$groupsWithPerms = array();
		foreach ($permissionSets AS $set)
		{
			if ($set['user_group_id'] && $set['content_id'] == $formId)
			{
				$groupsWithPerms[$set['user_group_id']] = true;
			}
		}

		$viewParams = array(
			'form' => $form,
			'userGroups' => $this->_getUserGroupModel()->getAllUserGroups(),
			'groupsWithPerms' => $groupsWithPerms,
			'users' => $this->_getPermissionModel()->getUsersWithContentUserPermissions('form', $formId),
			'revoked' => $this->_permissionsAreRevoked($form['form_id'], 0, 0)
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Permission_Form', 'permission_form', $viewParams);
	}
	
	/**
	 * Changes the revoke status for the form-wide settings.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionFormWideRevoke()
	{
		$this->_assertPostOnly();

		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);

		$revoke = $this->_input->filterSingle('revoke', XenForo_Input::UINT);

		$this->_setPermissionRevokeStatus($form['form_id'], 0, 0, $revoke);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('form-permissions', $form)
		);
	}
	
	/**
	 * Displays a form to edit a user group's permissions for a form.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUserGroup()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);

		$userGroupId = $this->_input->filterSingle('user_group_id', XenForo_Input::UINT);
		$userGroup = $this->_getValidUserGroupOrError($userGroupId);

		$permissionModel = $this->_getPermissionModel();

		$permissions = $permissionModel->getUserCollectionContentPermissionsForGroupedInterface(
			'form', $form['form_id'], 'form', $userGroup['user_group_id']
		);
		
		unset($permissions['form']['formPermissions']['permissions'][0]);
		
		$viewParams = array(
			'form' => $form,
			'userGroup' => $userGroup,
			'permissions' => $permissions,
			'permissionChoices' => $permissionModel->getPermissionChoices('userGroup', true)
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Permission_FormUserGroup', 'permission_form_user_group', $viewParams);
	}
	
	/**
	 * Redirects to the correct page to add permissions for the specified user.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUserAdd()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);

		$userName = $this->_input->filterSingle('username', XenForo_Input::STRING);
		$user = $this->_getUserModel()->getUserByName($userName);
		if (!$user)
		{
			return $this->responseError(new XenForo_Phrase('requested_user_not_found'), 404);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
			XenForo_Link::buildAdminLink('form-permissions/user', $form, array('user_id' => $user['user_id']))
		);
	}
	
	/**
	 * Displays a form to edit a user's permissions for a form.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUser()
	{
		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);

		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$user = $this->_getValidUserOrError($userId);

		$permissionModel = $this->_getPermissionModel();

		$permissions = $permissionModel->getUserCollectionContentPermissionsForGroupedInterface(
			'form', $form['form_id'], 'form', 0, $user['user_id']
		);

		$viewParams = array(
			'form' => $form,
			'user' => $user,
			'permissions' => $permissions,
			'permissionChoices' => $permissionModel->getPermissionChoices('user', true)
		);

		return $this->responseView('LiquidPro_SimpleForms_ViewAdmin_Permission_FormUser', 'permission_form_user', $viewParams);
	}
	
	/**
	 * Updates a user's permissions for a form.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUserSave()
	{
		$this->_assertPostOnly();

		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);

		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$user = $this->_getValidUserOrError($userId);

		$permissions = $this->_input->filterSingle('permissions', XenForo_Input::ARRAY_SIMPLE);

		$this->_getPermissionModel()->updateContentPermissionsForUserCollection(
			$permissions, 'form', $form['form_id'], 0, $user['user_id']
		);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('form-permissions', $form) . $this->getLastHash("user_{$userId}")
		);
	}
	
	/**
	 * Updates a user group's permissions for a node.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUserGroupSave()
	{
		$this->_assertPostOnly();

		$formId = $this->_input->filterSingle('form_id', XenForo_Input::UINT);
		$form = $this->_getFormOrError($formId);

		$userGroupId = $this->_input->filterSingle('user_group_id', XenForo_Input::UINT);
		$userGroup = $this->_getValidUserGroupOrError($userGroupId);

		$permissions = $this->_input->filterSingle('permissions', XenForo_Input::ARRAY_SIMPLE);

		$this->_getPermissionModel()->updateContentPermissionsForUserCollection(
			$permissions, 'form', $form['form_id'], $userGroup['user_group_id']
		);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('form-permissions', $form) . $this->getLastHash("user_group_{$userGroupId}")
		);
	}
	
	protected function _permissionsAreRevoked($formId, $userGroupId, $userId)
	{
		$permissions = $this->_getPermissionModel()->getContentPermissionsWithValues(
			'form', $formId, array('form'), $userGroupId, $userId
		);

		foreach ($permissions AS $permission)
		{
			if ($permission['permission_group_id'] == 'form'
				&& $permission['permission_id'] == 'viewFormsList'
				&& $permission['permission_value'] === 'reset'
			)
			{
				return true;
			}
		}

		return false;
	}
	
	protected function _setPermissionRevokeStatus($formId, $userGroupId, $userId, $revoke)
	{
		$update = array('form' => array('viewFormsList' => $revoke ? 'reset' : 'unset', 'respondToForms' => $revoke ? 'reset' : 'unset'));

		$this->_getPermissionModel()->updateContentPermissionsForUserCollection(
			$update, 'form', $formId, $userGroupId, $userId
		);
	}
	
	/**
	 * Gets the specified form or throws an exception.
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function _getFormOrError($id)
	{
		return $this->getRecordOrError(
			$id, $this->_getFormModel(), 'getFormById',
			'requested_form_not_found'
		);
	}	

	/**
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected function _getFormModel()
	{
		return $this->getModelFromCache('LiquidPro_SimpleForms_Model_Form');
	}	
}