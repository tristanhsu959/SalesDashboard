<?php

namespace App\ViewModels;

use App\Services\UserService;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class MenuViewModel
{
	private $_service;
	private $_menu = [];
	
	public function __construct(Array $menu)
	{
		$this->_menu = $menu;
	}
	
	/* Get auth menu
	 * @params: 
	 * @return: array
	 */
	public function getMenu()
	{
		return $this->_menu;
	}
	
	/* Set current function active style
	 * @params: string
	 * @return: array
	 */
	public function activeActionStyle($segmentCode)
	{
		$segments 		= Request::segments();
		$segmentCode	= Str::snake($segmentCode);
		
		if (in_array($segmentCode, $segments))
			return 'active';
		
		return '';
	}
	
    #原新品style, 已廢棄
	/* Nav bar style
	 * @params: string
	 * @return: array
	 */
	public function getNavbarStyle()
	{
		$segments = Request::segments();
		$navStyle = '';
		
		if (empty($segments))
			return $navStyle;
		
		switch($segments[0])
		{
			case 'new_releases' :
				$navStyle = $segments[1];
				break;
		}
		
		return  Str::replace('_', '-', $navStyle);
	}
}