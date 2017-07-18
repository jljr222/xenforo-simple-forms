<?php

class LiquidPro_SimpleForms_Install_21909 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
        $this->addIndex('lpsf_forum_form', 'forum_id', array('forum_id'));

        return true;
    }
}