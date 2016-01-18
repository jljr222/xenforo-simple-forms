<?php

class LiquidPro_SimpleForms_Install_21600 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
        // add lpsf_page
        $db->query("
			CREATE TABLE IF NOT EXISTS `lpsf_page` (
			  `page_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `form_id` int(10) unsigned NOT NULL,
              `page_number` tinyint(3) unsigned NOT NULL,
              `title` varchar(50) NOT NULL,
              `description` mediumtext,
			  PRIMARY KEY (`page_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
		");
        
        // populate each form with a single page
        $pageCount = $db->fetchOne('SELECT COUNT(*) FROM `lpsf_page`');
        if ($pageCount == 0)
        {
            $db->query("
                INSERT INTO `lpsf_page` (
                    `form_id`,
                    `page_number`,
                    `title`
                )
    			SELECT
    				`form_id`
                    ,1
                    ,''
    			FROM `lpsf_form`
            ");
        }
        
        // add lpsf_field.page_id column
        $table = $this->describeTable('lpsf_field');
        if (!array_key_exists('page_id', $table))
        {        
            $db->query("ALTER TABLE `lpsf_field` ADD COLUMN `page_id` int(10) unsigned;");
        }
        
        // populate the lpsf_field.page_id column
        $db->query("UPDATE `lpsf_field` SET `page_id` = (SELECT `page_id` FROM `lpsf_page` WHERE `lpsf_page`.`form_id` = `lpsf_field`.`form_id`)");
        
        // add lpsf_form.display_order column
        $table = $this->describeTable('lpsf_form');
        if (!array_key_exists('display_order', $table))
        {
            $db->query("ALTER TABLE `lpsf_form` ADD COLUMN `display_order` int(10) unsigned NOT NULL DEFAULT '1';");
        }

        // populate lpsf_form.display_order
        /* @var $formModel LiquidPro_SimpleForms_Model_Form */
        $formModel = XenForo_Model::create('LiquidPro_SimpleForms_Model_Form');
        
        $displayOrder = array();
        $displayOrderCount = 1;
        $forms = $formModel->getForms();
        foreach ($forms as $formId => $form)
        {
            $displayOrder[$displayOrderCount++] = $formId;
        }
        $formModel->massUpdateDisplayOrder($displayOrder);

        return true;
    }
}