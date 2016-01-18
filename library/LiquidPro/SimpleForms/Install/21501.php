<?php

class LiquidPro_SimpleForms_Install_21501 extends LiquidPro_SimpleForms_Install_Abstract
{
	public function install(&$db)
	{
		// add the thread_lock destination option
		$rows = $db->fetchOne("SELECT COUNT(*) FROM `lpsf_destination_option` WHERE `option_id` = ?", array('thread_lock'));
		if ($rows == 0)
		{
			$db->query("
				INSERT INTO `lpsf_destination_option` (
					`option_id`
					,`destination_id`
					,`display_order`
					,`field_type`
					,`format_params`
					,`field_choices`
					,`match_type`
					,`match_regex`
					,`match_callback_class`
					,`match_callback_method`
					,`max_length`
					,`required`
					,`evaluate_template`
				) VALUES (
					'thread_lock'
					,1
					,55
					,'checkbox'
					,''
					,'a:1:{i:1;s:4:\"Lock\";}'
					,'none'
					,''
					,''
					,''
					,0
					,0
					,0
				);
			");
		}
		
		$rows = $db->fetchOne("SELECT COUNT(*) FROM `lpsf_form_destination_option` WHERE `option_id` = ?", array('thread_lock'));
		if ($rows == 0)
		{
			$db->query('
				INSERT INTO `lpsf_form_destination_option` (
					`form_destination_id`
					,`option_id`
					,`option_value`)
				SELECT
					`form_destination_id`
					,\'thread_lock\'
					,\'\'
				FROM `lpsf_form_destination`
				WHERE `destination_id` = 1
			');
		}
		
		// add poll destination option
		$rows = $db->fetchOne("SELECT COUNT(*) FROM `lpsf_destination_option` WHERE `option_id` = ?", array('thread_poll'));
		if ($rows == 0)
		{
    		$db->query("
    			INSERT INTO `lpsf_destination_option` (
    				`option_id`
    				,`destination_id`
    				,`display_order`
    				,`field_type`
    				,`format_params`
    				,`field_choices`
    				,`match_type`
    				,`match_regex`
    				,`match_callback_class`
    				,`match_callback_method`
    				,`max_length`
    				,`required`
    				,`evaluate_template`
    			) VALUES (
    				'thread_poll'
    				,1
    				,70
    				,'callback'
    				,'s:64:\"LiquidPro_SimpleForms_DestinationOption_ThreadPoll::renderOption\";'
    				,''
    				,'callback'
    				,''
    				,'LiquidPro_SimpleForms_Destination_Thread'
    				,'VerifyPoll'
    				,0
    				,0
    				,0
    			);
    		");
		}
		
		// convert old poll destination options to new poll destination option
		// be sure to filter out any destinations that already have a thread_poll option
		$formDestinationOptions = $db->fetchAll("
			SELECT
				`form_destination_id`
				,`option_id`
				,`option_value`
			FROM `lpsf_form_destination_option`
			WHERE `option_id` IN ('thread_poll_question', 'thread_poll_choices', 'thread_poll_multiple', 'thread_poll_public_votes')
		        AND `form_destination_id` NOT IN (
		            SELECT `form_destination_id`
		            FROM `lpsf_form_destination_option`
		            WHERE `option_id` = 'thread_poll'
		        )
		");
		
		// group the form destination options so we can process them together
		$formDestinations = array();
		foreach ($formDestinationOptions as $formDestinationOption)
		{
			if (!array_key_exists($formDestinationOption['form_destination_id'], $formDestinations))
			{
			    $formDestinations[$formDestinationOption['form_destination_id']] = array();
			}
			$formDestinations[$formDestinationOption['form_destination_id']][$formDestinationOption['option_id']] = $formDestinationOption['option_value'];
		}

		// loop through all of the form destinations and convert them over to the new thread_poll option
		foreach ($formDestinations as $formDestinationId => $formDestinationOption)
		{
			$optionArray = array();
				 
    		$optionArray['question'] = $formDestinationOption['thread_poll_question'];
			$optionArray['responses'] = unserialize($formDestinationOption['thread_poll_choices']);
			$optionArray['max_votes'] = (unserialize($formDestinationOption['thread_poll_multiple']) !== array()) ? 0 : 1;
			$optionArray['max_votes_type'] = ($optionArray['max_votes'] == 1) ? 'single' : 'unlimited';
			$optionArray['public_votes'] = (unserialize($formDestinationOption['thread_poll_public_votes']) !== array()) ? 1 : 0;

			$db->query("
				INSERT INTO `lpsf_form_destination_option` (
					`form_destination_id`
					,`option_id`
					,`option_value`
				) VALUES (
					?
					,'thread_poll'
					,?
				);
			", array($formDestinationId, serialize($optionArray)));
		}
		
		// delete old poll destination options
		$db->query("DELETE FROM `lpsf_destination_option` WHERE `option_id` IN ('thread_poll_question', 'thread_poll_choices', 'thread_poll_multiple', 'thread_poll_public_votes')");
		
		$db->query("DELETE FROM `lpsf_form_destination_option` WHERE `option_id` IN ('thread_poll_question', 'thread_poll_choices', 'thread_poll_multiple', 'thread_poll_public_votes')");
		
		$fields = array(
		    'attachment_handler_class' => 'LiquidPro_SimpleForms_AttachmentHandler_Form',
		    'permission_handler_class' => 'LiquidPro_SimpleForms_ContentPermission_Form',
		    'sitemap_handler_class' => 'LiquidPro_SimpleForms_SitemapHandler_Form'
		);
		$db->update('xf_content_type', array('fields' => serialize($fields)), "`content_type` = 'form'");
		
	    $db->insert('xf_content_type_field', array(
		    'content_type' => 'form',
		    'field_name' => 'sitemap_handler_class',
		    'field_value' => 'LiquidPro_SimpleForms_SitemapHandler_Form'
        ));
		
		return true;
	}
}