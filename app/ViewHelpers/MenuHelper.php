<?php

namespace App\ViewHelpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MenuHelper
{
	public function __construct()
	{
	}
	
	/* 當前功能(View判別顯示用)
	 * @params: string
	 * @return: array
	 */
	public function getCurrentActionStyle($segmentCode)
	{
		$segments = Request::segments();
		
		if (in_array($segmentCode, $segments))
			return 'active';
		
		return '';
	}
	
    /* View Style 
	public function getIconStyle($style = FALSE)
	{
		return $filled ? 'filled-icon' : '';
	}*/
	
	/* 已不用 */
	/*public function getActionActiveStyle($groupCode, $actionCode, $currentAction)
	{
		$code = $groupCode . $actionCode;
		
		return (hexdec($code) == hexdec($currentAction)) ? 'active' : '';
	}*/
}