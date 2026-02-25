<?php

use App\Enums\RoleGroup;
use App\Enums\Operation;

#Menu Config (key與route要相同)
return [
	
	/*============ 新品銷售 ============*/
	##### 八方
	'bf-beefShortRibs' => [
		'brand'	=> 'bf', 
		'name'		=> '八方-牛小排麵',
		'url' 		=> 'bf/new_releases/beef_short_ribs', 
		'operation'	=> [
			Operation::READ
		],
	],
	
	##### 御廚
	'bg-porkRibs' => [
		'brand'	=> 'bg', 
		'name'		=> '御廚-橙汁排骨',
		'url' 		=> 'bg/new_releases/pork_ribs', 
		'operation'	=> [
			Operation::READ
		],
	],
	'bg-tomatoBeef' => [
		'brand'	=> 'bg', 
		'name' 		=> '御廚-番茄牛三寶麵',
		'url' 		=> 'bg/new_releases/tomato_beef',
		'operation'	=> [
			Operation::READ
		],
	],
	'bg-eggTofu' => [
		'brand'	=> 'bg', 
		'name' 		=> '御廚-老皮嫩肉',
		'url' 		=> 'bg/new_releases/egg_tofu',
		'operation'	=> [
			Operation::READ
		],
	],
	'bg-braisedPork'	=> [
		'brand'	=> 'bg', 
		'name' 		=> '御廚-主廚秘製滷肉飯',
		'url' 		=> 'bg/new_releases/braised_pork',
		'operation'	=> [
			Operation::READ
		],
	],
	'bg-eggTofu' => [
		'brand'	=> 'bg', 
		'name' 		=> '御廚-老皮嫩肉',
		'url' 		=> 'bg/new_releases/egg_tofu',
		'operation'	=> [
			Operation::READ
		],
	],
	'bg-braisedGravy' => [
		'brand'	=> 'bg', 
		'name' 		=> '御廚-秘製滷肉汁',
		'url' 		=> 'bg/new_releases/braised_gravy',
		'operation'	=> [
			Operation::READ
		],
	],
	#滷肉飯加滷汁 = braisedPork + braisedGravy
	'bg-porkGravy' => [
		'brand'	=> 'bg', 
		'name' 		=> '御廚-滷肉飯加滷汁',
		'url' 		=> 'bg/new_releases/pork_gravy',
		'operation'	=> [
			Operation::READ
		],
	],
	'bg-beefShortRibs' => [
		'brand'		=> 'bg', 
		'name'		=> '御廚-牛小排飯',
		'url' 		=> 'bg/new_releases/beef_short_ribs', 
		'operation'	=> [
			Operation::READ
		],
	],
	
	/*============ 進銷存報表 ============*/
	'bg-purchase' => [
		'brand'	=> 'bg', 
		'name'		=> '御廚-進貨統計',
		'url' 		=> 'bg/purchase', 
		'operation'	=> [
			Operation::READ
		],
	],
	'bg-sales' => [
		'brand'	=> 'bg', 
		'name'		=> '御廚-銷售統計',
		'url' 		=> 'bg/sales', 
		'operation'	=> [
			Operation::READ
		],
	],
	
	
	/*============ 權限管理 ============*/
	'user' => [
		'brand'		=> '', 
		'name' 		=> '帳號管理',
		'url' 		=> 'user', 
		'operation'	=> [
			Operation::READ, Operation::CREATE, Operation::UPDATE, Operation::DELETE, 
		],
	],
	'role' => [
		'brand'		=> '', 
		'name' 		=> '身份管理',
		'url' 		=> 'role',
		'operation'	=> [
			Operation::READ, Operation::CREATE, Operation::UPDATE, Operation::DELETE, 
		],
	],
];
