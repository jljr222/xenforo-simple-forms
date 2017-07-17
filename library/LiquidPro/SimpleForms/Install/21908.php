<?php

class LiquidPro_SimpleForms_Install_21908 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
        // remove the local key
        $dataRegistry = new XenForo_Model_DataRegistry();
        $dataRegistry->delete('lpsf_localkey');

        return true;
    }
}