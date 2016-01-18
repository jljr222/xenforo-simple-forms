<?php

class LiquidPro_SimpleForms_Install_21 extends LiquidPro_SimpleForms_Install_Abstract
{
	public function install(&$db)
	{
		// add self to lpsf_form.redirect_method
		$db->query("ALTER TABLE `lpsf_form` CHANGE `redirect_method` `redirect_method` ENUM('url', 'destination', 'self') NOT NULL;");
		
		$table = $this->describeTable('lpsf_field');
		
		// add require_attachment to lpsf_form
		if (!array_key_exists('require_attachment', $table))
		{
			$db->query("ALTER TABLE `lpsf_form` ADD COLUMN `require_attachment` TINYINT NOT NULL DEFAULT 0;");
		}
		
		// add pre_text to lpsf_field
		if (!array_key_exists('pre_text', $table))
		{
			$db->query('ALTER TABLE `lpsf_field` ADD COLUMN `pre_text` mediumtext;');
		}
		
		// add post_text to lpsf_field
		if (!array_key_exists('post_text', $table))
		{ 
			$db->query('ALTER TABLE `lpsf_field` ADD COLUMN `post_text` mediumtext;');
		}
		
		// add callback to lpsf_destination_option.type
		$db->query("ALTER TABLE `lpsf_destination_option` CHANGE `field_type` `field_type` ENUM('textbox','textarea','select','radio','checkbox','multiselect','array','callback') NOT NULL;");
		
		// add the thread_prefix destination option
		$rows = $db->fetchOne("SELECT COUNT(*) FROM `lpsf_destination_option` WHERE `option_id` = ?", array('thread_prefix'));
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
					'thread_prefix'
					,1
					,45
					,'callback'
					,'s:66:\"LiquidPro_SimpleForms_DestinationOption_ThreadPrefix::renderOption\";'
					,''
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

		$rows = $db->fetchOne("SELECT COUNT(*) FROM `lpsf_form_destination_option` WHERE `option_id` = ?", array('thread_prefix'));
		if ($rows == 0)
		{
			$db->query('
				INSERT INTO `lpsf_form_destination_option` (
					`form_destination_id`
					,`option_id`
					,`option_value`)
				SELECT
					`form_destination_id`
					,\'thread_prefix\'
					,\'a:0:{}\'
				FROM `lpsf_form_destination`
				WHERE `destination_id` = 1				
			');
		}

		return true;
	}
}