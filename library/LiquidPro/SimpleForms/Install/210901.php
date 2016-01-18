<?php

class LiquidPro_SimpleForms_Install_210901 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
        // add lpsf_form.header_html column
        $table = $this->describeTable('lpsf_form');
        if (!array_key_exists('header_html', $table))
        {
            $db->query("ALTER TABLE `lpsf_form` ADD COLUMN `header_html` mediumtext;");
        }
        
        // add lpsf_form.footer_html column
        if (!array_key_exists('footer_html', $table))
        {
            $db->query("ALTER TABLE `lpsf_form` ADD COLUMN `footer_html` mediumtext;");
        }
        
        return true;
    }
}