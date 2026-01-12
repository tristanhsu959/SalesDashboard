<?php

use App\Enums\RoleGroup;
use App\Enums\Operation;

#Menu Config (key與route要相同)
return [
	#Group (不直接與function關聯)
	'groups' => [
		[
			'name' 	=> '新品銷售',
			'style' => ['icon' => 'chart_data', 'color' => 'purple'], #filled-icon 
			'type'	=> [RoleGroup::USER->name],
			'items' => [ 		#Function code or key => use Str::camel to check segment
				'porkRibs',  
				'tomatoBeef',
				'eggTofu',
				#'braisedPork',
				#'braisedGravy',
				'porkGravy'#滷肉飯要加滷汁一起算
			],
		],
		[
			'name' 	=> '進銷存報表',
			'style' => ['icon' => 'trolley', 'color' => 'teal'], #filled-icon 
			'type'	=> [RoleGroup::USER->name],
			'items' => [ 		#Function code or key => use Str::camel to check segment
				'purchaseBg',  
			],
		],
		[
			'name'	=> '權限管理',
			'style' => ['icon' => 'admin_panel_settings', 'color' => 'red'],
			'type' 	=> [RoleGroup::ADMIN->name, RoleGroup::SUPERVISOR->name],
			'items' => [
				'users',
				'roles',
			],
		],
	],
	
	#Function
	'functions' => [
		/* 進銷存報表 */
		'purchaseBg' => [
			'code'		=> 'purchaseBg', #判別用
			'name'		=> '梁社漢-進貨',
			'url' 		=> 'purchase/bg', 
			'operation'	=> [
				Operation::READ
			],
		],
		/* 新品銷售 */
		'porkRibs' => [
			'code'		=> 'porkRibs', #判別用
			'name'		=> '橙汁排骨',
			'url' 		=> 'new_releases/pork_ribs', 
			'operation'	=> [
				Operation::READ
			],
		],
		'tomatoBeef' => [
			'code'		=> 'tomatoBeef',
			'name' 		=> '番茄牛三寶麵',
			'url' 		=> 'new_releases/tomato_beef',
			'operation'	=> [
				Operation::READ
			],
		],
		'eggTofu' => [
			'code'	=> 'eggTofu',
			'name' 		=> '老皮嫩肉',
			'url' 		=> 'new_releases/egg_tofu',
			'operation'	=> [
				Operation::READ
			],
		],
		'braisedPork'	=> [
			'code'		=> 'braisedPork',
			'name' 		=> '主廚秘製滷肉飯',
			'url' 		=> 'new_releases/braised_pork',
			'operation'	=> [
				Operation::READ
			],
		],
		'eggTofu' => [
			'code'	=> 'eggTofu',
			'name' 		=> '老皮嫩肉',
			'url' 		=> 'new_releases/egg_tofu',
			'operation'	=> [
				Operation::READ
			],
		],
		'braisedGravy' => [
			'code'		=> 'braisedGravy',
			'name' 		=> '秘製滷肉汁',
			'url' 		=> 'new_releases/braised_gravy',
			'operation'	=> [
				Operation::READ
			],
		],
		#滷肉飯加滷汁 = braisedPork + braisedGravy
		'porkGravy' => [
			'code'		=> 'porkGravy',
			'name' 		=> '滷肉飯加滷汁',
			'url' 		=> 'new_releases/pork_gravy',
			'operation'	=> [
				Operation::READ
			],
		],
		/* 權限管理 */
		'users' => [
			'code'		=> 'users',
			'name' 		=> '帳號管理',
			'url' 		=> 'users', 
			'operation'	=> [
				Operation::READ, Operation::CREATE, Operation::UPDATE, Operation::DELETE, 
			],
		],
		'roles' => [
			'code'		=> 'roles',
			'name' 		=> '身份管理',
			'url' 		=> 'roles',
			'operation'	=> [
				Operation::READ, Operation::CREATE, Operation::UPDATE, Operation::DELETE, 
			],
		],
			
	],
];
