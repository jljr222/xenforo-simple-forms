<?php

class LiquidPro_SimpleForms_Install_210900 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
        $db->query("ALTER TABLE `lpsf_field` MODIFY COLUMN `field_type` ENUM('textbox','textarea','select','radio','checkbox','multiselect','wysiwyg','date','rating','datetime','time');");
        
        return true;
    }
}