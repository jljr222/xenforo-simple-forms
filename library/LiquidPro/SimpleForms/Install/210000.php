<?php

class LiquidPro_SimpleForms_Install_210000 extends LiquidPro_SimpleForms_Install_Abstract
{
	public function install(&$db)
	{
		// add lpsf_forum_form
		$db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_forum_form` (
			  `forum_form_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `forum_id` int(10) unsigned NOT NULL,
			  `form_id` int(10) unsigned NOT NULL,
			  `button_text` varchar(75) NOT NULL,
			  `replace_button` tinyint(3) unsigned NOT NULL,
			  PRIMARY KEY (`forum_form_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
		");
		
		// add Date to lpsf_field.field_type
		$db->query("ALTER TABLE `lpsf_field` MODIFY COLUMN `field_type` ENUM('textbox','textarea','select','radio','checkbox','multiselect','wysiwyg','date');");
		
		// change column lpsf_form.start_date, lpsf_form.end_date
		$db->query("ALTER TABLE `lpsf_form` CHANGE COLUMN `start_date` `start_date` mediumblob;");
		$db->query("ALTER TABLE `lpsf_form` CHANGE COLUMN `end_date` `end_date` mediumblob;");
		
		// convert timestamps to dates
		$forms = $db->fetchAll('SELECT * FROM `lpsf_form` WHERE `start_date` IS NOT NULL OR `end_date` IS NOT NULL');
		foreach ($forms as $form)
		{
			if ($form['start_date'] > 0) 
			{
				$startDate = array();
				$datetime = new DateTime('@' . $form['start_date']);
				$startDate['enabled'] = 'start';
				$startDate['ymd'] = $datetime->format('Y-m-d');
				$startDate['hh'] = '00';
				$startDate['mm'] = '00';
				$startDate['user_tz'] = 1;
				$startDate['timezone'] = '';
			}
			else
			{
				$startDate = array();
				$startDate['enabled'] = '';
				$startDate['ymd'] = '';
				$startDate['hh'] = '';
				$startDate['mm'] = '';
				$startDate['user_tz'] = 0;
				$startDate['timezone'] = '';
			}
			
			if ($form['end_date'] > 0)
			{
				$endDate = array();
				$datetime = new DateTime('@' . $form['end_date']);
				$endDate['enabled'] = 'end';
				$endDate['ymd'] = $datetime->format('Y-m-d');
				$endDate['hh'] = '00';
				$endDate['mm'] = '00';
				$endDate['user_tz'] = 1;
				$endDate['timezone'] = '';
			}
			else
			{
				$endDate = array();
				$endDate['enabled'] = '';
				$endDate['ymd'] = '';
				$endDate['hh'] = '';
				$endDate['mm'] = '';
				$endDate['user_tz'] = 0;
				$endDate['timezone'] = '';
			}
			
			$data = array();
			$data['start_date'] = serialize($startDate);
			$data['end_date'] = serialize($endDate);
			
			$db->update('lpsf_form',
				$data,
				$db->quoteInto('form_id = ?', $form['form_id'])
			);
		}
		
		return true;
	}
}