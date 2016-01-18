<?php

class LiquidPro_SimpleForms_Install_20 extends LiquidPro_SimpleForms_Install_Abstract
{
	public function install(&$db)
	{
		// get a list of foreign keys
		$foreignKeys = $db->fetchAll('
			SELECT
				*
			FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`
			WHERE `TABLE_SCHEMA` = DATABASE()
				AND `TABLE_NAME` LIKE \'lpsf_%\'
				AND `REFERENCED_TABLE_NAME` <> \'\'
		');
		
		// remove all foreign keys
		foreach ($foreignKeys as $foreignKey)
		{
			$db->query("ALTER TABLE `" . $foreignKey['TABLE_NAME'] . "` DROP FOREIGN KEY `" . $foreignKey['CONSTRAINT_NAME'] . "`");
		}
		
		// get a list of tables that are InnoDB
		$tables = $db->fetchAll('
			SELECT
				*
			FROM `INFORMATION_SCHEMA`.`TABLES`
			WHERE `TABLE_SCHEMA` = DATABASE()
				AND `TABLE_NAME` LIKE \'lpsf_%\'	
				AND `ENGINE` = \'InnoDB\'
		');
		
		// convert InnoDB tables to MyISAM
		foreach ($tables as $table)
		{
			$db->query("ALTER TABLE `" . $table['TABLE_NAME'] . "` ENGINE = 'MyISAM'");
		}
		
		$table = $this->describeTable('lpsf_field');
		
		// check to see if placeholder exists
		if (!array_key_exists('placeholder', $table))
		{
			$db->query("ALTER TABLE `lpsf_field` ADD COLUMN `placeholder` MEDIUMTEXT NOT NULL AFTER `default_value`;");
		}
		
		// add column lpsf_field.active
		if (!array_key_exists('active', $table))
		{
			$db->query("ALTER TABLE `lpsf_field` ADD COLUMN `active` TINYINT(3) NOT NULL DEFAULT 1 AFTER `placeholder`;");
		}
		
		// add column lpsf_field.type
		if (!array_key_exists('type', $table))
		{
			$db->query("ALTER TABLE `lpsf_field` ADD COLUMN `type` ENUM('user', 'template', 'global') NOT NULL DEFAULT 'user' AFTER `field_id`;");
		}
		
		// add column lpsf_field.parent_field_id
		if (!array_key_exists('parent_field_id', $table))
		{
			$db->query("ALTER TABLE `lpsf_field` ADD COLUMN `parent_field_id` int(10) unsigned AFTER `type`;");
		}

		// change column lpsf_field.form_id
		$db->query("ALTER TABLE `lpsf_field` CHANGE COLUMN `form_id` `form_id` int(10) unsigned;");
		
		$table = $this->describeTable('lpsf_form');
		
		// add column lpsf_form.css
		if (!array_key_exists('css', $table))
		{
			$db->query("ALTER TABLE `lpsf_form` ADD COLUMN `css` MEDIUMTEXT NOT NULL AFTER `end_date`;");
		}
		
		// create form destination table
		$db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_form_destination` (
			  `form_destination_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `form_id` int(10) unsigned NOT NULL,
			  `destination_id` int(10) unsigned NOT NULL,
			  `name` varchar(75) NOT NULL,
			  `active` tinyint(30) NOT NULL DEFAULT '1',
			  PRIMARY KEY (`form_destination_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
		");
		
		// migrate data over to form_destination
		// check to see if table has data in it, if it does... truncate it
		$rows = $db->fetchOne('SELECT COUNT(*) FROM `lpsf_form_destination`');
		if ($rows > 0)
		{
			$db->query("TRUNCATE TABLE `lpsf_form_destination`");
		}

		$db->query("
			INSERT INTO `lpsf_form_destination` (`form_id`, `destination_id`, `name`, `active`)
			SELECT DISTINCT
				`lpsf_form_destination_option`.`form_id`
				,`lpsf_destination_option`.`destination_id`
				,`lpsf_destination`.`name`
				,(
					SELECT
						CASE WHEN `sub_form_destination_option`.`option_value` = 'a:1:{s:7:\"enabled\";s:7:\"enabled\";}'
							THEN 1
							ELSE 0
						END
					FROM `lpsf_form_destination_option` AS `sub_form_destination_option`
					JOIN `lpsf_destination_option` AS `sub_destination_option`
						ON `sub_destination_option`.`option_id` = `sub_form_destination_option`.`option_id`
					WHERE
						`sub_form_destination_option`.`option_id` LIKE '%_enabled'
						AND `sub_form_destination_option`.`form_id` = `lpsf_form_destination_option`.`form_id`
						AND `sub_destination_option`.`destination_id` = `lpsf_destination_option`.`destination_id`
					LIMIT 1
				)
			FROM `lpsf_form_destination_option`
			JOIN `lpsf_destination_option`
				ON `lpsf_destination_option`.`option_id` = `lpsf_form_destination_option`.`option_id`
			JOIN `lpsf_destination`
				ON `lpsf_destination`.`destination_id` = `lpsf_destination_option`.`destination_id`
		");
		
		// add lpsf_form_destination_option.form_destination_id
		$table = $this->describeTable('lpsf_form_destination_option');
		if (!array_key_exists('form_destination_id', $table))
		{
			$db->query("ALTER TABLE `lpsf_form_destination_option` ADD COLUMN `form_destination_id` INT(10) UNSIGNED FIRST;");
		}
		
		// set the form_destination_id to NULL
		$rows = $db->fetchOne('SELECT COUNT(*) FROM `lpsf_form_destination_option` WHERE `form_destination_id` IS NOT NULL');
		if ($rows > 0)
		{
			$db->query("UPDATE `lpsf_form_destination_option` SET `form_destination_id` = NULL");
		}
		
		// populate lpsf_form_destination_option.form_destination_id
		$db->query("
			UPDATE `lpsf_form_destination_option` SET `form_destination_id` = (
			  SELECT
			    `lpsf_form_destination`.`form_destination_id`
			  FROM
			    `lpsf_form_destination`
			  WHERE
			    `lpsf_form_destination`.`form_id` = `lpsf_form_destination_option`.`form_id`
			    AND `lpsf_form_destination`.`destination_id` = (
			      SELECT
			        `lpsf_destination_option`.`destination_id`
			      FROM
			        `lpsf_destination_option`
			      WHERE
			        `lpsf_destination_option`.`option_id` = `lpsf_form_destination_option`.`option_id`
				  LIMIT 1
			    )
			  LIMIT 1
			);
		");
		
		// make lpsf_form_destination_option.form_destination_id not null
		$db->query("ALTER TABLE `lpsf_form_destination_option` CHANGE COLUMN `form_destination_id` `form_destination_id` INT(10) UNSIGNED NOT NULL;");
		
		// change primary key on lpsf_form_destination_option
		$db->query("
			ALTER TABLE `lpsf_form_destination_option` DROP PRIMARY KEY ,
			  ADD PRIMARY KEY (`form_destination_id`, `option_id`);
		");
		
		// drop column lpsf_form_destination_option.form_id
		$table = $this->describeTable('lpsf_form_destination_option');
		if (array_key_exists('form_id', $table))
		{
			$db->query("ALTER TABLE `lpsf_form_destination_option` DROP COLUMN `form_id`;");
		}
			
		// remove *_enabled destination options
		$db->query("DELETE FROM `lpsf_form_destination_option` WHERE `option_id` LIKE '%_enabled';");
		$db->query("DELETE FROM `lpsf_destination_option` WHERE `option_id` LIKE '%_enabled';");
		
		// add the thread_automatically_watch destination option
		$rows = $db->fetchOne("SELECT COUNT(*) FROM `lpsf_destination_option` WHERE `option_id` = ?", array('thread_automatically_watch'));
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
					'thread_automatically_watch'
					,1
					,130
					,'radio'
					,''
					,'a:3:{s:0:\"\";s:0:\"\";s:11:\"watch_email\";s:11:\"watch_email\";s:14:\"watch_no_email\";s:14:\"watch_no_email\";}'
					,'none'
					,''
					,''
					,''
					,0
					,1
					,0
				);
			");
		}
		
		// add a default value of not watching for all existing forms
		$rows = $db->fetchOne("SELECT COUNT(*) FROM `lpsf_form_destination_option` WHERE `option_id` = ?", array('thread_automatically_watch'));
		if ($rows > 0)
		{
			$db->query("DELETE FROM `lpsf_form_destination_option` WHERE `option_id` = ?", array('thread_automatically_watch'));
		}
		$db->query("
			INSERT INTO `lpsf_form_destination_option`(`option_id`, `option_value`, `form_destination_id`)
			SELECT
				'thread_automatically_watch'
				,''
				,`form_destination_id`
			FROM `lpsf_form_destination`
			WHERE `destination_id` = 1
		");
		
		// modify the conversation_recipient_user_id destination option
		$db->query("
			UPDATE `lpsf_destination_option` SET
				`format_params` = 'a:3:{s:10:\"data-acUrl\";s:21:\"users/search-username\";s:14:\"data-acDisplay\";s:8:\"username\";s:10:\"inputClass\";s:19:\"AutoCompleteGeneric\";}'
			WHERE option_id = 'conversation_recipient_user_id'
		");
		
		return true;
	}
}