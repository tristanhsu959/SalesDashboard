<?php

namespace App\Libraries;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class MenuLib
{
	/* All menu Groups - 取選單, 無Business logic
	 * @params: array
	 * @return: object
	 */
    public static function all()
    {
		$menu 		= [];
		$groups 	= config('web.menu.groups');
		$functions 	= config('web.menu.functions');
		
		foreach($groups as $key => $group)
		{
			$items = [];
			foreach($group['items'] as $itemKey)
			{
				$items[] = data_get($functions, $itemKey, '');
			}
			
			$group['items'] = $items;
			$menu[$key] = $group;
		}
		
		return $menu;
    }
	
	/* Functions
	 * @params: array
	 * @return: object
	 */
    public static function functions($key = NULL)
    {
		if (empty($key))
			return config('web.menu.functions');
		else
			return config("web.menu.functions.{$key}");
    }
}