<?php

use App\Enums\Brand;

#Menu Config (key與route要相同)
return [
	#Group (Enable List)
	Brand::BAFANG->value => [
		[
			'name' 		=> '新品銷售',
			'style' 	=> ['icon' => 'chart_data', 'color' => 'purple'], #filled-icon 
			'isManage'	=> FALSE,
			'items' => [ 		#Function code or key => use Str::camel to check segment
				'beefShortRibs',
			],
		],
	],
	
	Brand::BUYGOOD->value => [
		[
			'name' 		=> '新品銷售',
			'style' 	=> ['icon' => 'chart_data', 'color' => 'purple'], #filled-icon 
			'isManage'	=> FALSE,
			'items' => [ 		#Function code or key => use Str::camel to check segment
				'porkRibs',  
				'tomatoBeef',
				'eggTofu',
				'porkGravy',	#滷肉飯要加滷汁一起算
				'beefShortRibs',
			],
		],
		[
			'name' 	=> '進銷存報表',
			'style' => ['icon' => 'trolley', 'color' => 'teal'], #filled-icon 
			'isManage'	=> FALSE,
			'items' => [ 		#Function code or key => use Str::camel to check segment
				'purchase',
				'sales',
			],
		],
	],
	
	'manage' => [
		[
			'name'		=> '權限管理',
			'style' 	=> ['icon' => 'admin_panel_settings', 'color' => 'red'],
			'isManage'	=> TRUE,
			'items' => [
				'user',
				'role',
			],
		],
	],
];
