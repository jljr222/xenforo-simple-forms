<?php

class LiquidPro_SimpleForms_Install_210700 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
        $db->query("UPDATE `lpsf_destination_option` SET `evaluate_template` = 1 WHERE `option_id` = 'thread_poll'");
        
        return true;
    }
}