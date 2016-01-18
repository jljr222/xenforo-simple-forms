<?php

class LiquidPro_SimpleForms_Install_21400 extends LiquidPro_SimpleForms_Install_Abstract
{
	public function install(&$db)
	{
		// add Rating to lpsf_field.field_type
		$db->query("ALTER TABLE `lpsf_field` MODIFY COLUMN `field_type` ENUM('textbox','textarea','select','radio','checkbox','multiselect','wysiwyg','date','rating');");
		$db->query("ALTER TABLE `lpsf_response` CHANGE `ip_address` `ip_address` VARCHAR(45) NOT NULL;");
		
		return true;
	}
}