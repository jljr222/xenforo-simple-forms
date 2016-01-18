<?php

class LiquidPro_SimpleForms_ViewAdmin_Form_ExportResponses extends XenForo_ViewAdmin_Base
{
	public function renderRaw()
	{
		$this->_response->setHeader('Content-type', 'application/octet-stream', true);
		$this->setDownloadFileName($this->_params['fileName']);

		$this->_response->setHeader('Content-Length', $this->_params['fileSize'], true);
		$this->_response->setHeader('X-Content-Type-Options', 'nosniff');
		
		return $this->_params['file'];
	}
}