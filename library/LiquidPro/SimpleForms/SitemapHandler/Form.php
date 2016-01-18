<?php

class LiquidPro_SimpleForms_SitemapHandler_Form extends XenForo_SitemapHandler_Abstract
{
    protected $_formModel;
    
    public function getRecords($previousLast, $limit, array $viewingUser)
    {
        $formModel = $this->_getFormModel();
        
        $forms = $formModel->getForms(array(
            'active' => true,
            'hide_from_list' => false,
            'start_date' => array(XenForo_Application::$time, '<='),
            'end_date' => array(XenForo_Application::$time, '>')
        ));
        ksort($forms);
        
        return $forms;
    }
    
    public function isIncluded(array $entry, array $viewingUser)
    {
        return true;
    }
    
    public function getData(array $entry)
    {
        $result = array(
            'loc' => XenForo_Link::buildPublicLink('canonical:forms', $entry),
            'priority' => 0.3
        );
    
        return $result;
    }
    
    public function isInterruptable()
    {
        return false;
    }
    
    /**
     * @return LiquidPro_SimpleForms_Model_Form
     */
    protected function _getFormModel()
    {
        if (!$this->_formModel)
        {
            $this->_formModel = XenForo_Model::create('LiquidPro_SimpleForms_Model_Form');
        }
        
        return $this->_formModel;
    }    
}