<?php

class LiquidPro_SimpleForms_Install_21901 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
        $db->query("ALTER TABLE `lpsf_form` ADD COLUMN `header_html` mediumtext;");
        $db->query("ALTER TABLE `lpsf_form` ADD COLUMN `footer_html` mediumtext;");
        
        return true;
    }
}