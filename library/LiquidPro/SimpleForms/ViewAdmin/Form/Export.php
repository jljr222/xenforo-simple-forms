<?php

class LiquidPro_SimpleForms_ViewAdmin_Form_Export extends XenForo_ViewAdmin_Base
{
	public function renderXml()
	{
		$this->setDownloadFileName('form-' . $this->_params['form']['form_id'] . '.xml');
		return $this->_params['xml']->saveXml();
	}
}