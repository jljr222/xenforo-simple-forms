<?php

class LiquidPro_SimpleForms_Install_210601 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
        // delete orphaned lpsf_response_field rows
        $db->query("
			DELETE FROM `lpsf_response_field`
            WHERE `response_id` NOT IN (
                SELECT
                    `response_id`
                FROM `lpsf_response`
            )
		");
        
        return true;
    }
}