<?php

class LiquidPro_SimpleForms_Install_0 extends LiquidPro_SimpleForms_Install_Abstract
{
	public function install(&$db)
	{
		// form content type
		$data = array(
			'content_type' => 'form',
			'addon_id' => 'LiquidPro_SimpleForms',
			'fields' => serialize(array(
				'permission_handler_class' => 'LiquidPro_SimpleForms_ContentPermission_Form'
			))
		);
		$db->insert('xf_content_type', $data);
		
		// form permissions handler
		$data = array(
			'content_type' => 'form',
			'field_name' => 'permission_handler_class',
			'field_value' => 'LiquidPro_SimpleForms_ContentPermission_Form'
		);
		$db->insert('xf_content_type_field', $data);
		
		// lpsf_destination
		$db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_destination` (
			  `destination_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(50) NOT NULL,
			  `handler_class` varchar(75) NOT NULL,
			  `display_order` int(10) unsigned NOT NULL DEFAULT '1',
			  PRIMARY KEY (`destination_id`),
			  UNIQUE KEY `name_UNIQUE` (`name`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
		");
				
		// lpsf_destination data
		$db->query("
			INSERT INTO `lpsf_destination` (`destination_id`, `name`, `handler_class`, `display_order`) VALUES
			(1, 'Thread', 'LiquidPro_SimpleForms_Destination_Thread', 10),
			(2, 'Post', 'LiquidPro_SimpleForms_Destination_Post', 20),
			(3, 'E-mail', 'LiquidPro_SimpleForms_Destination_Email', 40),
			(4, 'Conversation', 'LiquidPro_SimpleForms_Destination_Conversation', 30);
		");
		
		// lpsf_destination_option
		$db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_destination_option` (
			  `option_id` varchar(50) NOT NULL COMMENT '	',
			  `destination_id` int(10) unsigned NOT NULL,
			  `display_order` int(10) unsigned NOT NULL DEFAULT '1',
			  `field_type` enum('textbox','textarea','select','radio','checkbox','multiselect','array') NOT NULL DEFAULT 'textbox',
			  `format_params` mediumblob NOT NULL,
			  `field_choices` mediumblob NOT NULL,
			  `match_type` enum('none','number','alphanumeric','email','url','regex','callback') NOT NULL DEFAULT 'none',
			  `match_regex` varchar(250) NOT NULL DEFAULT '',
			  `match_callback_class` varchar(75) NOT NULL DEFAULT '',
			  `match_callback_method` varchar(75) NOT NULL DEFAULT '',
			  `max_length` int(11) NOT NULL DEFAULT '0',
			  `required` smallint(6) NOT NULL DEFAULT '0',
			  `evaluate_template` smallint(5) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY (`option_id`),
			  KEY `fk_lpsf_destination_option_lpsf_destination1` (`destination_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		// lpsf_destination_option data
		$db->query("
			INSERT INTO `lpsf_destination_option` (`option_id`, `destination_id`, `display_order`, `field_type`, `format_params`, `field_choices`, `match_type`, `match_regex`, `match_callback_class`, `match_callback_method`, `max_length`, `required`, `evaluate_template`) VALUES
			('conversation_enabled', 4, 1, 'checkbox', '', 0x613a313a7b733a373a22656e61626c6564223b733a373a22456e61626c6564223b7d, 'none', '', '', '', 0, 1, 0),
			('conversation_locked', 4, 100, 'checkbox', '', 0x613a313a7b693a313b733a363a224c6f636b6564223b7d, 'none', '', '', '', 0, 0, 0),
			('conversation_message_template', 4, 50, 'textarea', '', '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Conversation', 'VerifyMessageTemplate', 0, 0, 1),
			('conversation_recipient_user_id', 4, 40, 'textbox', 0x613a333a7b733a31303a22646174612d616355726c223b733a32313a2275736572732f7365617263682d757365726e616d65223b733a31343a22646174612d6163446973706c6179223b733a383a22757365726e616d65223b733a31303a22696e707574436c617373223b733a32383a224175746f436f6d706c65746547656e6572696320416353696e676c65223b7d, '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Conversation', 'VerifyRecipientUserId', 0, 1, 0),
			('conversation_send_by_user_id', 4, 30, 'textbox', 0x613a333a7b733a31303a22646174612d616355726c223b733a32313a2275736572732f7365617263682d757365726e616d65223b733a31343a22646174612d6163446973706c6179223b733a383a22757365726e616d65223b733a31303a22696e707574436c617373223b733a32383a224175746f436f6d706c65746547656e6572696320416353696e676c65223b7d, '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Conversation', 'VerifySendUserId', 0, 0, 0),
			('conversation_title_template', 4, 20, 'textbox', '', '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Conversation', 'VerifyTitleTemplate', 0, 1, 1),
			('email_enabled', 3, 1, 'checkbox', '', 0x613a313a7b733a373a22656e61626c6564223b733a373a22456e61626c6564223b7d, 'none', '', '', '', 0, 1, 0),
			('email_format', 3, 45, 'radio', '', 0x613a323a7b733a343a2268746d6c223b733a343a2268746d6c223b733a353a22706c61696e223b733a353a22706c61696e223b7d, 'none', '', '', '', 0, 1, 0),
			('email_message_template', 3, 50, 'textarea', '', '', 'none', '', '', '', 0, 0, 1),
			('email_recipient_email', 3, 40, 'textbox', '', '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Email', 'VerifyRecipientEmail', 0, 0, 1),
			('email_sender_email', 3, 30, 'textbox', '', '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Email', 'VerifySenderEmail', 0, 0, 1),
			('email_subject_template', 3, 20, 'textbox', '', '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Email', 'VerifySubjectTemplate', 0, 1, 1),
			('post_enabled', 2, 1, 'checkbox', '', 0x613a313a7b733a373a22656e61626c6564223b733a373a22456e61626c6564223b7d, 'none', '', '', '', 0, 1, 0),
			('post_message_template', 2, 40, 'textarea', '', '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Post', 'VerifyMessageTemplate', 0, 0, 1),
			('post_post_by_user_id', 2, 30, 'textbox', 0x613a333a7b733a31303a22646174612d616355726c223b733a32313a2275736572732f7365617263682d757365726e616d65223b733a31343a22646174612d6163446973706c6179223b733a383a22757365726e616d65223b733a31303a22696e707574436c617373223b733a32383a224175746f436f6d706c65746547656e6572696320416353696e676c65223b7d, '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Post', 'VerifyPostUserId', 0, 0, 0),
			('post_thread_id', 2, 20, 'textbox', '', '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Post', 'VerifyThreadId', 0, 1, 0),
			('thread_enabled', 1, 1, 'checkbox', '', 0x613a313a7b733a373a22656e61626c6564223b733a373a22456e61626c6564223b7d, 'none', '', '', '', 0, 1, 0),
			('thread_forum_node_id', 1, 20, 'textbox', 0x613a333a7b733a31303a22646174612d616355726c223b733a31393a22666f72756d732f7365617263682d7469746c65223b733a31343a22646174612d6163446973706c6179223b733a353a227469746c65223b733a31303a22696e707574436c617373223b733a32383a224175746f436f6d706c65746547656e6572696320416353696e676c65223b7d, '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Thread', 'VerifyForumNodeId', 0, 1, 0),
			('thread_message_template', 1, 60, 'textarea', '', '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Thread', 'VerifyMessageTemplate', 0, 0, 1),
			('thread_poll_choices', 1, 80, 'array', '', '', 'none', '', '', '', 0, 0, 0),
			('thread_poll_multiple', 1, 90, 'checkbox', '', 0x613a313a7b693a313b733a373a22456e61626c6564223b7d, 'none', '', '', '', 0, 0, 0),
			('thread_poll_public_votes', 1, 100, 'checkbox', '', 0x613a313a7b693a313b733a373a22456e61626c6564223b7d, 'none', '', '', '', 0, 0, 0),
			('thread_poll_question', 1, 75, 'textbox', '', '', 'none', '', '', '', 100, 0, 1),
			('thread_post_by_user_id', 1, 40, 'textbox', 0x613a333a7b733a31303a22646174612d616355726c223b733a32313a2275736572732f7365617263682d757365726e616d65223b733a31343a22646174612d6163446973706c6179223b733a383a22757365726e616d65223b733a31303a22696e707574436c617373223b733a32383a224175746f436f6d706c65746547656e6572696320416353696e676c65223b7d, '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Thread', 'VerifyPostUserId', 0, 0, 0),
			('thread_sticky', 1, 50, 'checkbox', '', 0x613a313a7b693a313b733a363a22537469636b79223b7d, 'none', '', '', '', 0, 0, 0),
			('thread_title_template', 1, 30, 'textbox', '', '', 'callback', '', 'LiquidPro_SimpleForms_Destination_Thread', 'VerifyTitleTemplate', 0, 1, 1);
		");	
		$db->query("
			ALTER TABLE `lpsf_destination_option`
			  ADD CONSTRAINT `fk_lpsf_destination_option_lpsf_destination1` FOREIGN KEY (`destination_id`) REFERENCES `lpsf_destination` (`destination_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
		");
		
		// lpsf_form
		$db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_form` (
			  `form_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `title` varchar(50) NOT NULL,
			  `description` text NOT NULL,
			  `active` tinyint(3) NOT NULL DEFAULT '0',
			  `hide_from_list` tinyint(3) NOT NULL DEFAULT '0',
			  `max_responses` int(10) unsigned NOT NULL DEFAULT '0',
			  `max_responses_per_user` int(10) unsigned NOT NULL DEFAULT '0',
			  `complete_message` text NOT NULL,
			  `complete_url` varchar(250) NOT NULL,
			  `start_date` int(10) unsigned DEFAULT NULL,
			  `end_date` int(10) unsigned DEFAULT NULL,
			  PRIMARY KEY (`form_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
		");
		
		// lpsf_form_destination_option
		$db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_form_destination_option` (
			  `form_id` int(10) unsigned NOT NULL,
			  `option_id` varchar(50) NOT NULL,
			  `option_value` mediumtext NOT NULL,
			  PRIMARY KEY (`form_id`,`option_id`),
			  KEY `fk_lpsf_form_destination_option_lpsf_form1` (`form_id`),
			  KEY `fk_lpsf_form_destination_option_lpsf_destination_option1` (`option_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");	
		$db->query("
			ALTER TABLE `lpsf_form_destination_option`
			  ADD CONSTRAINT `fk_lpsf_form_destination_option_lpsf_destination_option1` FOREIGN KEY (`option_id`) REFERENCES `lpsf_destination_option` (`option_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
			  ADD CONSTRAINT `lpsf_form_destination_option_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `lpsf_form` (`form_id`);		
		");
		
		// lpsf_field
		$db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_field` (
			  `field_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `form_id` int(10) unsigned NOT NULL,
			  `display_order` int(10) unsigned NOT NULL DEFAULT '1',
			  `field_name` varchar(25) NOT NULL,
			  `field_type` enum('textbox','textarea','select','radio','checkbox','multiselect') NOT NULL DEFAULT 'textbox',
			  `field_choices` blob NOT NULL,
			  `match_type` enum('none','number','alphanumeric','email','url','regex','callback') NOT NULL DEFAULT 'none',
			  `match_regex` varchar(250) NOT NULL DEFAULT '',
			  `match_callback_class` varchar(75) NOT NULL DEFAULT '',
			  `match_callback_method` varchar(75) NOT NULL DEFAULT '',
			  `max_length` int(11) NOT NULL DEFAULT '0',
			  `required` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `hide_title` tinyint(3) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`field_id`),
			  KEY `form_id` (`form_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;		
		");
		$db->query("
			ALTER TABLE `lpsf_field`
			  ADD CONSTRAINT `lpsf_field_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `lpsf_form` (`form_id`);		
		");
		
		// lpsf_response
		$db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_response` (
			  `response_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `form_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned DEFAULT NULL,
			  `response_date` int(10) unsigned NOT NULL,
			  `ip_address` varchar(15) NOT NULL,
			  PRIMARY KEY (`response_id`),
			  KEY `form_id` (`form_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;		
		");		
		$db->query("ALTER TABLE `lpsf_response` ADD CONSTRAINT `lpsf_response_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `lpsf_form` (`form_id`);		");
		
		// lpsf_response_field
		$db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_response_field` (
			  `field_id` int(10) unsigned NOT NULL,
			  `response_id` int(10) unsigned NOT NULL,
			  `field_value` mediumtext NOT NULL,
			  PRIMARY KEY (`field_id`,`response_id`),
			  KEY `response_id` (`response_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;	
		");
		$db->query("
			ALTER TABLE `lpsf_response_field`
			  ADD CONSTRAINT `lpsf_response_field_ibfk_14` FOREIGN KEY (`field_id`) REFERENCES `lpsf_field` (`field_id`),
			  ADD CONSTRAINT `lpsf_response_field_ibfk_15` FOREIGN KEY (`response_id`) REFERENCES `lpsf_response` (`response_id`);		
		");
		
		return true;
	}
}