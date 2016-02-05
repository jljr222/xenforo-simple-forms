<?php

class LiquidPro_SimpleForms_Model_Form extends XenForo_Model
{
	/**
	 * Fetches the combined node-form record for the specified node id
	 *
	 * @param integer $formId Form ID
	 *
	 * @return array
	 */
	public function getFormById($formId, array $fetchOptions = array())
	{
		if (empty($formId))
		{
			return false;
		}		
		
		$joinOptions = $this->prepareFormJoinOptions($fetchOptions);
		
		return $this->_getDb()->fetchRow('
			SELECT form.*
				' . $joinOptions['selectFields'] . '
			FROM lpsf_form AS form
				' . $joinOptions['joinTables'] . '
			WHERE form.form_id = ?
		', $formId);
	}
	
	/**
	 * Fetches the combined node-form record for the specified node id
	 *
	 * @param integer $formDestinationId Form destination ID
	 *
	 * @return array
	 */
	public function getFormDestinationById($formDestinationId)
	{
		if (empty($formDestinationId))
		{
			return false;
		}
	
		return $this->_getDb()->fetchRow('
			SELECT form_destination.*
			FROM lpsf_form_destination AS form_destination
			WHERE form_destination.form_destination_id = ?
			', $formDestinationId);
	}	
	
	public function getFormDestinationsInForm($formId)
	{
		return $this->fetchAllKeyed('
			SELECT form_destination.*
			FROM lpsf_form_destination AS form_destination
			WHERE form_destination.form_id = ?
		', 'form_destination_id', $formId);
	}
	
	/**
	 * Fetches all combined node-form records
	 * 
	 * @param array $conditions
	 * @param array $fetchOptions
	 * 
	 * @return array
	 */
	public function getForms(array $conditions = array(), array $fetchOptions = array())
	{
		$joinOptions = $this->prepareFormJoinOptions($fetchOptions);
		$whereConditions = $this->prepareFormConditions($conditions, $fetchOptions);
		$orderClause = $this->prepareFormOrderOptions($fetchOptions, 'form.title');
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT form.*
				' . $joinOptions['selectFields'] . '
				FROM lpsf_form AS form
				' . $joinOptions['joinTables'] . '
				WHERE ' . $whereConditions . '
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'form_id');
	}
	
	/**
	 * Fetches an array suitable as source for admin template 'options' tag from forms array
	 *
	 * @param array Array of forms, including form_id and title
	 * @param integer FormId of selected node
	 *
	 * @return array
	 */
	public function getFormOptionsArray(array $forms, $selectedFormId = 0)
	{
		$options = array();
	
		foreach ($forms AS $formId => $form)
		{
			$options[$formId] = array(
				'value' => $formId,
				'label' => $form['title'],
				'selected' => ($formId == $selectedFormId)
			);
		}
	
		return $options;
	}
	
	/**
	 * Returns an array containing the form ids found from the complete result given the range specified,
	 * along with the total number of forms found.
	 *
	 * @param integer Find forms with form_id greater than...
	 * @param integer Maximum forms to return at once
	 *
	 * @return array
	 */
	public function getFormIdsInRange($start, $limit)
	{
	    $db = $this->_getDb();
	
	    return $db->fetchCol($db->limit('
			SELECT `form_id`
			FROM `lpsf_form`
			WHERE `form_id` > ?
			ORDER BY `form_id`
		', $limit), $start);
	}
	
	/**
	 * Gets the count of forms that match the specified conditions.
	 *
	 * @param array $conditions
	 *
	 * @return int
	 */
	public function countForms(array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->prepareFormConditions($conditions, $fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM lpsf_form AS form
			WHERE ' . $whereClause
		);
	}
	
	public function prepareForm(array $form)
	{
		$visitor = XenForo_Visitor::getInstance();
				
		$form['start_date'] = unserialize($form['start_date']);
		$startDate = $form['start_date'];
		if ($startDate && $startDate['enabled'] == 'start') 
		{
			$form['start_date']['datetime'] = new DateTime("$startDate[ymd] $startDate[hh]:$startDate[mm]",
				new DateTimeZone(((array_key_exists('user_tz', $form['start_date']) && $form['start_date']['user_tz'] == 1) ? $visitor['timezone'] : $form['start_date']['timezone'])));
			$form['start_date']['timestamp'] = $form['start_date']['datetime']->getTimestamp();
		}
		else
		{
			$form['start_date'] = array();
			$form['start_date']['enabled'] = '';
			$form['start_date']['ymd'] = '';
			$form['start_date']['hh'] = '';
			$form['start_date']['mm'] = '';
			$form['start_date']['user_tz'] = 0;
			$form['start_date']['timezone'] = '';
		}
			
		$form['end_date'] = unserialize($form['end_date']);
		$endDate = $form['end_date'];
		if ($endDate && $endDate['enabled'] == 'end') 
		{
			$form['end_date']['datetime'] = new DateTime("$endDate[ymd] $endDate[hh]:$endDate[mm]",
				new DateTimeZone(((array_key_exists('user_tz', $form['end_date']) && $form['end_date']['user_tz'] == 1) ? $visitor['timezone'] : $form['end_date']['timezone'])));
			$form['end_date']['timestamp'] = $form['end_date']['datetime']->getTimestamp();
		}
		else
		{
			$form['end_date'] = array();
			$form['end_date']['enabled'] = '';
			$form['end_date']['ymd'] = '';
			$form['end_date']['hh'] = '';
			$form['end_date']['mm'] = '';
			$form['end_date']['user_tz'] = 0;
			$form['end_date']['timezone'] = '';
		}
		
		return $form;
	}
	
	/**
	 * Checks the 'join' key of the incoming array for the presence of the FETCH_x bitfields in this class
	 * and returns SQL snippets to join the specified tables if required
	 *
	 * @param array $fetchOptions Array containing a 'join' integer key build from this class's FETCH_x bitfields and other keys
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys. Example: selectFields = ', user.*, foo.title'; joinTables = ' INNER JOIN foo ON (foo.id = other.id) '
	 */
	public function prepareFormJoinOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		$db = $this->_getDb();

		if (!empty($fetchOptions['permissionCombinationId']))
		{
			$selectFields .= ',
				permission.cache_value AS form_permission_cache';
			$joinTables .= '
				LEFT JOIN xf_permission_cache_content AS permission
					ON (permission.permission_combination_id = ' . $db->quote($fetchOptions['permissionCombinationId']) . '
						AND permission.content_type = \'form\'
						AND permission.content_id = form.form_id)';
		}
		
		if (isset($fetchOptions['numResponses']))
		{
			$selectFields .= ",
				(SELECT COUNT(*) FROM lpsf_response AS response WHERE response.form_id = form.form_id) AS num_responses";
		}
		
		if (isset($fetchOptions['numUserResponses']))
		{
			$selectFields .= ",
				(SELECT COUNT(*) FROM lpsf_response AS response WHERE response.form_id = form.form_id AND response.user_id = " . $db->quote(XenForo_Visitor::getUserId()) . ") AS num_user_responses";
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	
	/**
	 * Construct 'ORDER BY' clause
	 *
	 * @param array $fetchOptions (uses 'order' key)
	 * @param string $defaultOrderSql Default order SQL
	 *
	 * @return string
	 */
	public function prepareFormOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'title' => 'form.title',
		    'display_order' => 'form.display_order'
		);
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}
	
	/**
	 * Prepares a collection of form fetching related conditions into an SQL clause
	 *
	 * @param array $conditions List of conditions
	 * @param array $fetchOptions Modifiable set of fetch options (may have joins pushed on to it)
	 *
	 * @return string SQL clause (at least 1=1)
	 */
	public function prepareFormConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();
		
		if (!empty($conditions['title']))
		{
			if (is_array($conditions['title']))
			{
				$sqlConditions[] = 'form.title LIKE ' . XenForo_Db::quoteLike($conditions['title'][0], $conditions['title'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'form.title LIKE ' . XenForo_Db::quoteLike($conditions['title'], 'lr', $db);
			}
		}
		
		if (!empty($conditions['start_date']))
		{
			if (is_array($conditions['start_date']))
			{
				$sqlConditions[] = "form.start_date = '' OR form.start_date " . $conditions['start_date'][1] . " " . $conditions['start_date'][0];
			}
		}
		
		if (!empty($conditions['end_date']))
		{
			if (is_array($conditions['end_date']))
			{
				$sqlConditions[] = "form.end_date = 0 OR form.end_date " . $conditions['end_date'][1] . " " . $conditions['end_date'][0];
			}
		}	
		
		if (!empty($conditions['num_fields']))
		{
			if (is_array($conditions['num_fields']))
			{
				$sqlConditions[] = "(SELECT COUNT(*) FROM lpsf_field WHERE form_id = form.form_id) " . $conditions['num_fields'][1] . " " . $conditions['num_fields'][0];
			}
		}
		
		if (!empty($conditions['max_responses']))
		{
			$sqlConditions[] = "form.max_responses = 0 OR (SELECT COUNT(*) FROM lpsf_response WHERE form_id = form.form_id) < form.max_responses";
		}
		
		if (!empty($conditions['max_responses_per_user']))
		{
			if (is_array($conditions['max_responses_per_user']))
			{
				$sqlConditions[] = 'form.max_responses_per_user = 0 OR (SELECT COUNT(*) FROM lpsf_response WHERE user_id = ' . $conditions['max_responses_per_user']['user_id'] . ') < form.max_responses_per_user';
			}
			else
			{
				$sqlConditions[] = 'form.max_responses_per_user = 0 OR (SELECT COUNT(*) FROM lpsf_response WHERE user_id = ' . $conditions['max_responses_per_user'] . ') < form.max_responses_per_user';
			}
		}
		
		if (!empty($conditions['form_id']))
		{
			if (is_array($conditions['form_id']))
			{
				$sqlConditions[] = 'form.form_id IN (' . $db->quote($conditions['form_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'form.form_id = ' . $db->quote($conditions['form_id']);
			}
		}
		
		if (isset($conditions['active']))
		{
			$sqlConditions[] = 'form.active = ' . $db->quote($conditions['active']);
		}
		
		if (isset($conditions['hide_from_list']))
		{
			$sqlConditions[] = 'form.hide_from_list = ' . $db->quote($conditions['hide_from_list']);
		}
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	/**
	 * Determines if the specified form can be viewed with the given permissions.
	 *
	 * @param array $form Info about the forum posting in
	 * @param string $errorPhraseKey Returned phrase key for a specific error
	 * @param array|null $formPermissions
	 * @param array|null $viewingUser
	 *
	 * @return boolean
	 */
	public function canRespondToForm(array $form, &$errorPhraseKey = '', array $formPermissions = null, array $viewingUser = null)
	{
		$visitor = XenForo_Visitor::getInstance();
		
		$this->standardizeViewingUserReferenceForForm($form['form_id'], $viewingUser, $formPermissions);

		if ($form['start_date'] && $form['start_date']['enabled'] == 'start' && XenForo_Application::$time < $form['start_date']['datetime']->format('U'))
		{
			return false;
		}
		
		if ($form['end_date'] && $form['end_date']['enabled'] == 'end' && XenForo_Application::$time >= $form['end_date']['datetime']->format('U'))
		{
			return false;
		} 
		
		return XenForo_Permission::hasContentPermission($formPermissions, 'respondToForms');
	}	
	
	/**
	 * Standardizes the viewing user reference for the specific form.
	 *
	 * @param integer $formId
	 * @param array|null $viewingUser Viewing user; if null, use visitor
	 * @param array|null $formPermissions Permissions for this form
	 */
	public function standardizeViewingUserReferenceForForm($formId, array &$viewingUser = null, array &$formPermissions = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!is_array($formPermissions))
		{
			/* @var $permissionCacheModel XenForo_Model_PermissionCache */
			$permissionCacheModel = XenForo_Model::create('XenForo_Model_PermissionCache');

			$formPermissions = $permissionCacheModel->getContentPermissionsForItem(
				$viewingUser['permission_combination_id'], 'form', $formId
			);
		}
	}
	
	/**
	 * Gets the XML data for the specified form.
	 *
	 * @param array $form Form info
	 *
	 * @return DOMDocument
	 */
	public function getFormXml(array $form)
	{
		$document = new DOMDocument('1.0', 'utf-8');
		$document->formatOutput = true;

		$rootNode = $document->createElement('form');
		$rootNode->setAttribute('title', $form['title']);
		$rootNode->setAttribute('description', $form['description']);
		$rootNode->setAttribute('active', $form['active']);
		$rootNode->setAttribute('hide_from_list', $form['hide_from_list']);
		$rootNode->setAttribute('max_responses', $form['max_responses']);
		$rootNode->setAttribute('max_responses_per_user', $form['max_responses_per_user']);
		$rootNode->setAttribute('complete_message', $form['complete_message']);
		$rootNode->setAttribute('redirect_method', $form['redirect_method']);
		$rootNode->setAttribute('redirect_url', $form['redirect_url']);
		
		// if the redirect destination is set, we need to get the name of the destination
		// so when we import the form, we can correctly associate the active redirect destination
		if ($form['redirect_destination'] != '')
		{
		    $form['redirect_destination'] = $this->_getDb()->fetchOne('SELECT `name` FROM `lpsf_form_destination` WHERE `form_destination_id` = ?', array($form['redirect_destination']));
		}
		$rootNode->setAttribute('redirect_destination', $form['redirect_destination']);
		
		$rootNode->setAttribute('start_date', $form['start_date']);
		$rootNode->setAttribute('end_date', $form['end_date']);
		$rootNode->setAttribute('css', $form['css']);
		$rootNode->setAttribute('require_attachment', $form['require_attachment']);
		$rootNode->setAttribute('header_html', $form['header_html']);
		$rootNode->setAttribute('footer_html', $form['footer_html']); 
		$document->appendChild($rootNode);

		$formId = $form['form_id'];

		$dataNode = $rootNode->appendChild($document->createElement('field'));
		$this->getModelFromCache('LiquidPro_SimpleForms_Model_Field')->appendFieldFormXml($dataNode, $formId);

		$dataNode = $rootNode->appendChild($document->createElement('destination'));
		$this->appendFormDestinationXml($dataNode, $formId);
		
		return $document;
	}	
	
	/**
	 * Imports a form using XML from a file.
	 *
	 * @param string $fileName Path to file
	 *
	 * @return int $formId Form ID of the form added
	 */
	public function importFormXmlFromFile($fileName)
	{
		if (!file_exists($fileName) || !is_readable($fileName))
		{
			throw new XenForo_Exception(new XenForo_Phrase('please_enter_valid_file_name_requested_file_not_read'), true);
		}

		try
		{
			$document = new SimpleXMLElement($fileName, 0, true);
		}
		catch (Exception $e)
		{
			throw new XenForo_Exception(
				new XenForo_Phrase('provided_file_was_not_valid_xml_file'), true
			);
		}
		
		return $this->importFormXml($document);
	}	
	
	/**
	 * Imports form XML from a simple XML document.
	 *
	 * @param SimpleXMLElement $xml
	 *
	 * @return int $formId Form ID of the form added
	 */
	public function importFormXml(SimpleXMLElement $xml)
	{
		if ($xml->getName() != 'form')
		{
			throw new XenForo_Exception(new XenForo_Phrase('provided_file_is_not_a_form_xml_file'), true);
		}
		
		$formData = array(
			'title' => (string)$xml['title'],
			'description' => (string)$xml['description'],
			'active' => (int)$xml['active'],
			'hide_from_list' => (int)$xml['hide_from_list'],
			'max_responses' => (int)$xml['max_responses'],
			'max_responses_per_user' => (int)$xml['max_responses_per_user'],
			'complete_message' => (string)$xml['complete_message'],
			'redirect_method' => (string)$xml['redirect_method'],
			'redirect_url' => (string)$xml['redirect_url'],
			'redirect_destination' => (string)$xml['redirect_destination'],
			'start_date' => (isset($xml['start_date']) ? (string)$xml['start_date'] : serialize(array())),
			'end_date' => (isset($xml['end_date']) ? (string)$xml['end_date'] : serialize(array())),
			'css' => (string)$xml['css'],
		    'header_html' => (string)$xml['header_html'],
		    'footer_html' => (string)$xml['footer_html']
		);
		
		// get rid of a blank redirect destination
		if ($formData['redirect_destination'] == '')
		{
			unset($formData['redirect_destination']);
		}
		
		$db = $this->_getDb();
		XenForo_Db::beginTransaction($db);

		$formDw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Form');
		$formDw->setImportMode(true);
		$formDw->bulkSet($formData);
		$formDw->save();
		// get the form id
		$formId = $formDw->get('form_id');
		
		$pageDw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Page');
		$pageDw->setImportMode(true);
		$pageDw->bulkSet(array(
			'form_id' => $formId,
			'page_number' => 1,
			'title' => '',
			'description' => ''
		));
		$pageDw->save();

		foreach ($xml->children() as $child)
		{
			switch ($child->getName())
			{
				case 'field':
				{
					foreach ($child->children() as $field)
					{
						$formFieldDw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Field');
						$formFieldDw->setFieldXml($field, $formId);
					}
					break;
				}
					
				case 'destination':
				{
					foreach ($child->children() as $destination)
					{
						$formDestinationDw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_FormDestination');
						$newFormDestinationId = $formDestinationDw->setFormDestinationXml($destination, $formId);

						if (isset($formData['redirect_destination']) && $destination['name'] == $formData['redirect_destination'])
						{
						    $formDestinationId = $newFormDestinationId;
						}
					}
					break;
				}
			}
		}
		
		// if we have a form destination id, set it on the redirect_destination
		if (isset($formDestinationId))
		{
		    $formDw = XenForo_DataWriter::create('LiquidPro_SimpleForms_DataWriter_Form');
		    $formDw->setImportMode(true);
			$formDw->setExistingData($formId);
		    $formDw->set('redirect_destination', $formDestinationId);
		    $formDw->save();
		}
		
		XenForo_Db::commit($db);

		$this->_getFieldModel()->rebuildFieldCache();
		$this->getModelFromCache('XenForo_Model_Language')->rebuildLanguageCaches();
		$this->getModelFromCache('XenForo_Model_Permission')->rebuildPermissionCache();

		return $formId;
	}	
	
	/**
	 * Appends the form destination XML to a given DOM element.
	 *
	 * @param DOMElement $rootNode Node to append all form elements to
	 * @param string $formId Form ID to be exported
	 */
	public function appendFormDestinationXml(DOMElement $rootNode, $formId)
	{
		$destinationOptionModel = XenForo_Model::create('LiquidPro_SimpleForms_Model_DestinationOption');
		
		$formDestinations = $this->getFormDestinationsInForm($formId);
		if ($formDestinations === array())
			return;
		
		
		ksort($formDestinations);
		$document = $rootNode->ownerDocument;
	
		foreach ($formDestinations as $formDestinationId => $formDestination)
		{
			$formDestinationNode = $document->createElement('destination');
			$formDestinationNode->setAttribute('destination_id', $formDestination['destination_id']);
			$formDestinationNode->setAttribute('name', $formDestination['name']);
			$formDestinationNode->setAttribute('active', $formDestination['active']);
				
			// get the destination options for that destination
			$dataNode = $formDestinationNode->appendChild($document->createElement('option'));
			$destinationOptionModel->appendDestinationOptionFormXml($dataNode, $formDestinationId);
			
			$rootNode->appendChild($formDestinationNode);
		}
	}
	
	/**
	 * Takes an array of display_order => form_id
	 * and updates all fields (used for the drag/drop UI)
	 *
	 * @param array $order
	 */
	public function massUpdateDisplayOrder(array $order)
	{
	    // do not execute if we have nothing to update
	    if (count($order) == 0)
	    {
	        return;
	    }
	    
	    $sqlOrder = '';
	    $sqlParent = '';
	
	    $db = $this->_getDb();
	
	    foreach ($order AS $displayOrder => $data)
	    {
	        $formId = $db->quote((int)$data);
	
	        $sqlOrder .= "WHEN $formId THEN " . $db->quote((int)$displayOrder * 10) . "\n";
	    }
	    
	    $db->query('
			UPDATE `lpsf_form` SET
			`display_order` = CASE `form_id`
			' . $sqlOrder . '
				ELSE 0 END
	    ');
	}

	protected function _getFieldModel()
	{
		return XenForo_Model::create('LiquidPro_SimpleForms_Model_Field');
	}
}