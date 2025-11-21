<?php

namespace App\ViewHelpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AppHelper
{
	public function __construct()
	{
	}
	
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