<?php

class LiquidPro_SimpleForms_ViewAdmin_Forum_SearchTitle extends XenForo_ViewAdmin_Base
{
	public function renderJson()
	{
		$results = array();
		foreach ($this->_params['forums'] AS $forum)
		{
			/*
			$title = $forum['title'] . ' (' . $forum['node_id'] . ')';
			$results[$title] = array(
				'title' => htmlspecialchars($title)
			);
			*/
			$results[$forum['node_id']] = array(
				'title' => htmlspecialchars($forum['title']) . ' (ID: ' . $forum['node_id'] . ')'
			);
		}
		
		return array(
			'results' => $results
		);
	}
}