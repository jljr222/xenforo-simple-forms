<?php

class LiquidPro_SimpleForms_ContentPermission_Form implements XenForo_ContentPermission_Interface
{
	/**
	 * Tracks whether we've initialized the form data. Many calls to
	 * {@link rebuildContentPermissions()} may happen on one object.
	 *
	 * @var boolean
	 */
	protected $_formsInitialized = false;

	/**
	 * Permission model
	 *
	 * @var XenForo_Model_Permission
	 */
	protected $_permissionModel = null;

	/**
	 * Global perms that apply to this call to rebuild the permissions. These permissions
	 * can be manipulated if necessary and the global permissions will actually be modified.
	 *
	 * @var array
	 */
	protected $_globalPerms = array();

	/**
	 * List of node types.
	 *
	 * @var array
	 */
	protected $_nodeTypes = array();

	/**
	 * The forms. This data is used to build permissions.
	 *
	 * @var array Format: [form id] => form info
	 */
	protected $_forms = array();

	/**
	 * All form permission entries for the tree, grouped by system, user group, and user.
	 *
	 * @var array
	 */
	protected $_formPermissionEntries = array();

	/**
	 * Builds the form permissions for a user collection.
	 *
	 * @param XenForo_Model_Permission $permissionModel Permission model that called this.
	 * @param array $userGroupIds List of user groups for the collection
	 * @param integer $userId User ID for the collection, if there are custom permissions
	 * @param array $permissionsGrouped List of all valid permissions, grouped
	 * @param array $globalPerms The global permissions that apply to this combination
	 *
	 * @return array
	 */
	public function rebuildContentPermissions($permissionModel, array $userGroupIds, $userId, array $permissionsGrouped, array &$globalPerms)
	{
		$this->_permissionModel = $permissionModel;
		$this->_globalPerms = $globalPerms;

		$this->_formSetup();
		
		$finalPermissions = $this->_buildFormPermissions($userId, $userGroupIds, $globalPerms, $permissionsGrouped);

		$globalPerms = $this->_globalPerms;
		
		return $finalPermissions;
	}

	/**
	 * Sets up the necessary information about the form, existing permission entries,
	 * etc. Only runs if not initialized.
	 */
	protected function _formSetup()
	{
		if ($this->_formsInitialized)
		{
			return;
		}

		$formModel = $this->_getFormModel();

		$this->_forms = $this->_getFormModel()->getForms();
		$this->_formPermissionEntries = $this->_permissionModel->getAllContentPermissionEntriesByTypeGrouped('form');
		
		$this->_formsInitialized = true;
	}

	/**
	 * Allows the node data to be injected manually. Generally only needed for testing.
	 *
	 * @param array $nodeTypes
	 * @param array $nodeTree
	 * @param array $nodePermissionEntries
	 */
	public function setNodeDataManually(array $nodeTypes, array $nodeTree, array $nodePermissionEntries)
	{
		$this->_nodeTypes = $nodeTypes;
		$this->_nodeTree = $nodeTree;
		$this->_nodePermissionEntries = $nodePermissionEntries;

		$this->_formsInitialized = true;
	}

	/**
	 * Builds form permissions for the specified combination.
	 *
	 * @param integer $userId
	 * @param array $userGroupIds
	 * @param array $basePermissions Base permissions, coming from global or parent; [group][permission] => allow/unset/etc
	 * @param array $permissionsGrouped List of all valid permissions, grouped
	 *
	 * @return array Final permissions (true/false), format: [form id][permission] => value
	 */
	protected function _buildFormPermissions($userId, array $userGroupIds, array $basePermissions, array $permissionsGrouped)
	{
		if (!isset($this->_forms))
		{
			return array();
		}
		
		/*
		if (!isset($basePermissions['form']['respondToForms']))
		{
			if (isset($this->_globalPerms['form']['respondToForms']))
			{
				$basePermissions['form']['respondToForms'] = $this->_globalPerms['form']['respondToForms'];
			}
			else
			{
				$basePermissions['form']['respondToForms'] = 'unset';
			}
		}
		*/

		$basePermissions = $this->_adjustBasePermissionAllows($basePermissions);

		$finalPermissions = array();

		foreach ($this->_forms AS $form)
		{
			$formId = $form['form_id'];

			$groupEntries = $this->_getUserGroupFormEntries($formId, $userGroupIds);
			$userEntries = $this->_getUserFormEntries($formId, $userId);
			$formWideEntries = $this->_getFormWideEntries($formId);

			$formPermissions = $this->_permissionModel->buildPermissionCacheForCombination(
				$permissionsGrouped, $formWideEntries, $groupEntries, $userEntries,
				$basePermissions
			);

			$finalFormPermissions = $this->_permissionModel->canonicalizePermissionCache(
				$formPermissions['form']
			);
			
			$finalPermissions[$formId] = $finalFormPermissions;
		}

		return $finalPermissions;
	}

	/**
	 * Get all user group entries that apply to this form for the specified user groups.
	 *
	 * @param integer $formId
	 * @param array $userGroupIds
	 *
	 * @return array
	 */
	protected function _getUserGroupFormEntries($formId, array $userGroupIds)
	{
		$rawUgEntries = $this->_formPermissionEntries['userGroups'];
		$groupEntries = array();
		foreach ($userGroupIds AS $userGroupId)
		{
			if (isset($rawUgEntries[$userGroupId], $rawUgEntries[$userGroupId][$formId]))
			{
				$groupEntries[$userGroupId] = $rawUgEntries[$userGroupId][$formId];
			}
		}

		return $groupEntries;
	}

	/**
	 * Gets all user entries that apply to this form for the specified user ID.
	 *
	 * @param $formId
	 * @param $userId
	 *
	 * @return array
	 */
	protected function _getUserFormEntries($formId, $userId)
	{
		$rawUserEntries = $this->_formPermissionEntries['users'];
		if ($userId && isset($rawUserEntries[$userId], $rawUserEntries[$userId][$formId]))
		{
			return $rawUserEntries[$userId][$formId];
		}
		else
		{
			return array();
		}
	}

	/**
	 * Get form-wide permissions for this node.
	 *
	 * @param $formId
	 *
	 * @return array
	 */
	protected function _getFormWideEntries($formId)
	{
		if (isset($this->_formPermissionEntries['system'][$formId]))
		{
			return $this->_formPermissionEntries['system'][$formId];
		}
		else
		{
			return array();
		}
	}

	/**
	 * Adjusts base (inherited) content_allow values to allow only. This
	 * allows them to be revoked.
	 *
	 * @param array $basePermissions
	 *
	 * @return array Adjusted base perms
	 */
	protected function _adjustBasePermissionAllows(array $basePermissions)
	{
		foreach ($basePermissions AS $group => $p)
		{
			foreach ($p AS $id => $value)
			{
				if ($value === 'content_allow')
				{
					$basePermissions[$group][$id] = 'allow';
				}
			}
		}

		return $basePermissions;
	}

	/**
	 * Force a node-wide reset to override content allow settings from the permissions.
	 * This is used to cause reset to take priority over content allow from a parent, but
	 * not content allow from this node.
	 *
	 * @param array $nodeWideEntries
	 * @param array $nodePermissions
	 *
	 * @return array Updated node permissions
	 */
	protected function _forceNodeWideResetOverride(array $nodeWideEntries, array $nodePermissions)
	{
		foreach ($nodeWideEntries AS $nwGroupId => $nwGroup)
		{
			foreach ($nwGroup AS $nwPermId => $nwValue)
			{
				if ($nwValue === 'reset'
					&& isset($nodePermissions[$nwGroupId][$nwPermId])
					&& $nodePermissions[$nwGroupId][$nwPermId] === 'content_allow'
				)
				{
					$nodePermissions[$nwGroupId][$nwPermId] = 'reset';
				}
			}
		}

		return $nodePermissions;
	}

	/**
	 * Gets the node model object.
	 *
	 * @return XenForo_Model_Node
	 */
	protected function _getNodeModel()
	{
		return XenForo_Model::create('XenForo_Model_Node');
	}
	
	/**
	 * Gets the form model object.
	 *
	 * @return LiquidPro_SimpleForms_Model_Form
	 */
	protected function _getFormModel()
	{
		return XenForo_Model::create('LiquidPro_SimpleForms_Model_Form');
	}
}