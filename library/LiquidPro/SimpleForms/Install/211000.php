<?php

class LiquidPro_SimpleForms_Install_211000 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
        // add hidden field type
        $db->query("ALTER TABLE `lpsf_field` MODIFY COLUMN `field_type` ENUM('textbox','textarea','select','radio','checkbox','multiselect','wysiwyg','date','rating','datetime','time','hidden');");
        
        // add lpsf_field.readonly
        $table = $this->describeTable('lpsf_field');
        if (!array_key_exists('readonly', $table))
        {
            $db->query("ALTER TABLE `lpsf_field` ADD COLUMN `readonly` tinyint(3) NOT NULL DEFAULT '0';");
        }
        
        return true;
    }
}