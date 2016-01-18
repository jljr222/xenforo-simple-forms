<?php

class LiquidPro_SimpleForms_Install_12 extends LiquidPro_SimpleForms_Install_Abstract
{
	public function install(&$db)
	{
		// add WYSIWYG to lpsf_field.field_type
		$db->query("ALTER TABLE `lpsf_field` MODIFY COLUMN `field_type` ENUM('textbox','textarea','select','radio','checkbox','multiselect','wysiwyg');");
		
		// add destination option conversation_hide_empty_fields
		$count = $db->fetchOne("
			SELECT
				COUNT(*)
			FROM `lpsf_destination_option`
			WHERE `option_id` = 'conversation_hide_empty_fields'
		");
		if ($count == 0)
		{
			$db->query("
				INSERT INTO `lpsf_destination_option` (`option_id`,`destination_id`,`display_order`,`field_type`,`format_params`,`field_choices`,`match_type`,`match_regex`,`match_callback_class`,`match_callback_method`,`max_length`,`required`,`evaluate_template`) VALUES
				('conversation_hide_empty_fields', 4, 110, 'checkbox', '', 'a:1:{s:7:\"enabled\";s:7:\"Enabled\";}', 'none', '', '', '', 0, 0, 0)
			");
		}
		
		// add destination option thread_hide_empty_fields
		$count = $db->fetchOne("
			SELECT
				COUNT(*)
			FROM `lpsf_destination_option`
			WHERE `option_id` = 'thread_hide_empty_fields'
		");
		if ($count == 0)
		{
			$db->query("
				INSERT INTO `lpsf_destination_option` (`option_id`,`destination_id`,`display_order`,`field_type`,`format_params`,`field_choices`,`match_type`,`match_regex`,`match_callback_class`,`match_callback_method`,`max_length`,`required`,`evaluate_template`) VALUES
				('thread_hide_empty_fields', 1, 110, 'checkbox', '', 'a:1:{s:7:\"enabled\";s:7:\"Enabled\";}', 'none', '', '', '', 0, 0, 0)
			");
		}		
		
		// add destination option post_hide_empty_fields
		$count = $db->fetchOne("
			SELECT
				COUNT(*)
			FROM `lpsf_destination_option`
			WHERE `option_id` = 'post_hide_empty_fields'
		");
		if ($count == 0)
		{
			$db->query("
				INSERT INTO `lpsf_destination_option` (`option_id`,`destination_id`,`display_order`,`field_type`,`format_params`,`field_choices`,`match_type`,`match_regex`,`match_callback_class`,`match_callback_method`,`max_length`,`required`,`evaluate_template`) VALUES
				('post_hide_empty_fields', 2, 50, 'checkbox', '', 'a:1:{s:7:\"enabled\";s:7:\"Enabled\";}', 'none', '', '', '', 0, 0, 0)
			");
		}
		
		// add destination option email_hide_empty_fields
		$count = $db->fetchOne("
			SELECT
				COUNT(*)
			FROM `lpsf_destination_option`
			WHERE `option_id` = 'email_hide_empty_fields'
		");
		if ($count == 0)
		{
			$db->query("
				INSERT INTO `lpsf_destination_option` (`option_id`,`destination_id`,`display_order`,`field_type`,`format_params`,`field_choices`,`match_type`,`match_regex`,`match_callback_class`,`match_callback_method`,`max_length`,`required`,`evaluate_template`) VALUES
				('email_hide_empty_fields', 3, 60, 'checkbox', '', 'a:1:{s:7:\"enabled\";s:7:\"Enabled\";}', 'none', '', '', '', 0, 0, 0)
			");
		}
		
		// add destination option conversation_enable_attachments
		$count = $db->fetchOne("
			SELECT
				COUNT(*)
			FROM `lpsf_destination_option`
			WHERE `option_id` = 'conversation_enable_attachments'	
		");
		if ($count == 0)
		{
			$db->query("
				INSERT INTO `lpsf_destination_option` (`option_id`,`destination_id`,`display_order`,`field_type`,`format_params`,`field_choices`,`match_type`,`match_regex`,`match_callback_class`,`match_callback_method`,`max_length`,`required`,`evaluate_template`) VALUES
				('conversation_enable_attachments', 4, 120, 'checkbox', '', 'a:1:{s:7:\"enabled\";s:7:\"Enabled\";}', 'none', '', '', '', 0, 0, 0);
			");
		}

		// add destination option thread_enable_attachments
		$count = $db->fetchOne("
			SELECT
				COUNT(*)
			FROM `lpsf_destination_option`
			WHERE `option_id` = 'thread_enable_attachments'
		");
		if ($count == 0)
		{
			$db->query("
				INSERT INTO `lpsf_destination_option` (`option_id`,`destination_id`,`display_order`,`field_type`,`format_params`,`field_choices`,`match_type`,`match_regex`,`match_callback_class`,`match_callback_method`,`max_length`,`required`,`evaluate_template`) VALUES
				('thread_enable_attachments', 1, 120, 'checkbox', '', 'a:1:{s:7:\"enabled\";s:7:\"Enabled\";}', 'none', '', '', '', 0, 0, 0)
			");
		}
		
		// add destination option post_enable_attachments
		$count = $db->fetchOne("
			SELECT
				COUNT(*)
			FROM `lpsf_destination_option`
			WHERE `option_id` = 'post_enable_attachments'
		");
		if ($count == 0)
		{
			$db->query("
				INSERT INTO `lpsf_destination_option` (`option_id`,`destination_id`,`display_order`,`field_type`,`format_params`,`field_choices`,`match_type`,`match_regex`,`match_callback_class`,`match_callback_method`,`max_length`,`required`,`evaluate_template`) VALUES
				('post_enable_attachments', 2, 60, 'checkbox', '', 'a:1:{s:7:\"enabled\";s:7:\"Enabled\";}', 'none', '', '', '', 0, 0, 0)
			");
		}

		// add destination option email_enable_attachments
		$count = $db->fetchOne("
			SELECT
				COUNT(*)
			FROM `lpsf_destination_option`
			WHERE `option_id` = 'email_enable_attachments'
		");
		if ($count == 0)
		{
			$db->query("
				INSERT INTO `lpsf_destination_option` (`option_id`,`destination_id`,`display_order`,`field_type`,`format_params`,`field_choices`,`match_type`,`match_regex`,`match_callback_class`,`match_callback_method`,`max_length`,`required`,`evaluate_template`) VALUES
				('email_enable_attachments', 3, 70, 'checkbox', '', 'a:1:{s:7:\"enabled\";s:7:\"Enabled\";}', 'none', '', '', '', 0, 0, 0)
			");
		}

		// content type changes
		$db->query("
			UPDATE `xf_content_type` SET
				`fields` = 'a:2:{s:24:\"permission_handler_class\";s:44:\"LiquidPro_SimpleForms_ContentPermission_Form\";s:24:\"attachment_handler_class\";s:41:\"LiquidPro_SimpleForms_AttachmentHandler_Form\";}'
			WHERE `content_type` = 'form';
		");
		
		$data = array(
				'content_type' => 'form',
				'field_name' => 'attachment_handler_class',
				'field_value' => 'LiquidPro_SimpleForms_AttachmentHandler_Form'
		);
		$db->insert('xf_content_type_field', $data);
		
		// add the default value column to lpsf_field
		$table = $this->describeTable('lpsf_field');
		
		// check to see if default_value exists
		if (!array_key_exists('default_value', $table))
		{
			$db->query("ALTER TABLE `lpsf_field` ADD COLUMN `default_value` MEDIUMTEXT;");
		}
		
		// add the min length column to lpsf_field
		if (!array_key_exists('min_length', $table))
		{
			$db->query("ALTER TABLE `lpsf_field` ADD COLUMN `min_length` int;");
		}
		
		// add redirect method to lpsf_destination
		$table = $this->describeTable('lpsf_destination');
		if (!array_key_exists('redirect_method', $table))
		{
			$db->query("ALTER TABLE `lpsf_destination` ADD COLUMN `redirect_method` varchar(35);");
		}
		
		// set redirect methods
		$db->query("UPDATE `lpsf_destination` SET `redirect_method` = 'redirect' WHERE `name` IN ('Thread', 'Post');");
		
		// add redirect_method column to lpsf_form
		$table = $this->describeTable('lpsf_form');
		if (!array_key_exists('redirect_method', $table))
		{
			$db->query("ALTER TABLE `lpsf_form` ADD COLUMN `redirect_method` ENUM('url', 'destination') NOT NULL DEFAULT 'url' AFTER `complete_Message`;");
		}
		
		// rename complete_url to redirect_url in lpsf_form
		if (!array_key_exists('redirect_url', $table))
		{
			$db->query("ALTER TABLE `lpsf_form` CHANGE `complete_url` `redirect_url` varchar(250) NOT NULL;");
		}
		
		// add redirect_destination to lpsf_form
		if (!array_key_exists('redirect_destination', $table))
		{
			$db->query("ALTER TABLE `lpsf_form` ADD COLUMN `redirect_destination` int(10) unsigned AFTER `redirect_url`;");
		}
		
		$db->query("
			ALTER TABLE `lpsf_form`
			ADD CONSTRAINT `fk_lpsf_form_lpsf_destination1` FOREIGN KEY (`redirect_destination`) REFERENCES `lpsf_destination` (`destination_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
		");
		
		return true;
	}
}