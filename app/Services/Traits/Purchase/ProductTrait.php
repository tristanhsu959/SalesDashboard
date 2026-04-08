<?php

namespace App\Services\Traits\Purchase;

use Illuminate\Support\Str;

/* nOrder Common */
trait ProductTrait
{
	/* 取對應的Group設定值
	 * @params: string
	 * @return: array
	 */
	public function getGroupByShortCode($code)
	{
		$groupConfig = config('web.purchase.product_type.groupPrefix');
		
		foreach($groupConfig as $config)
		{
			if (Str::startsWith($code, $config['pattern']))
			{
				$group['groupId'] 	= $config['id'];
				$group['groupName'] = $config['name'];
				
				return $group;
			}
		}
		
		return ['groupId' => '', 'groupName' => ''];
	}
	
}