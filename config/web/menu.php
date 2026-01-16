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
				'bg-porkRibs',  
				'bg-tomatoBeef',
				'bg-eggTofu',
				'bg-porkGravy'#滷肉飯要加滷汁一起算
				#'braisedPork',
				#'braisedGravy',
			],
		],
		[
			'name' 	=> '進銷存報表',
			'style' => ['icon' => 'trolley', 'color' => 'teal'], #filled-icon 
			'type'	=> [RoleGroup::USER->name],
			'items' => [ 		#Function code or key => use Str::camel to check segment
				'bg-purchase',
				'bg-sales',
			],
		],
		[
			'name'	=> '權限管理',
			'style' => ['icon' => 'admin_panel_settings', 'color' => 'red'],
			'type' 	=> [RoleGroup::ADMIN->name, RoleGroup::SUPERVISOR->name],
			'items' => [
				'user',
				'role',
			],
		],
	],
	
	#Function
	'functions' => [
		/*============ 新品銷售 ============*/
		'bg-porkRibs' => [
			#'code'		=> 'porkRibs', #判別用
			'name'		=> '御廚-橙汁排骨',
			'url' 		=> 'bg/new_releases/pork_ribs', 
			'operation'	=> [
				Operation::READ
			],
		],
		'bg-tomatoBeef' => [
			#'code'		=> 'tomatoBeef',
			'name' 		=> '御廚-番茄牛三寶麵',
			'url' 		=> 'bg/new_releases/tomato_beef',
			'operation'	=> [
				Operation::READ
			],
		],
		'bg-eggTofu' => [
			#'code'	=> 'eggTofu',
			'name' 		=> '御廚-老皮嫩肉',
			'url' 		=> 'bg/new_releases/egg_tofu',
			'operation'	=> [
				Operation::READ
			],
		],
		'bg-braisedPork'	=> [
			#'code'		=> 'braisedPork',
			'name' 		=> '御廚-主廚秘製滷肉飯',
			'url' 		=> 'bg/new_releases/braised_pork',
			'operation'	=> [
				Operation::READ
			],
		],
		'bg-eggTofu' => [
			#'code'	=> 'eggTofu',
			'name' 		=> '御廚-老皮嫩肉',
			'url' 		=> 'bg/new_releases/egg_tofu',
			'operation'	=> [
				Operation::READ
			],
		],
		'bg-braisedGravy' => [
			#'code'		=> 'braisedGravy',
			'name' 		=> '御廚-秘製滷肉汁',
			'url' 		=> 'bg/new_releases/braised_gravy',
			'operation'	=> [
				Operation::READ
			],
		],
		#滷肉飯加滷汁 = braisedPork + braisedGravy
		'bg-porkGravy' => [
			#'code'		=> 'porkGravy',
			'name' 		=> '御廚-滷肉飯加滷汁',
			'url' 		=> 'bg/new_releases/pork_gravy',
			'operation'	=> [
				Operation::READ
			],
		],
		
		/*============ 進銷存報表 ============*/
		'bg-purchase' => [
			#'code'		=> 'bg-purchase', #判別用
			'name'		=> '御廚-進貨統計',
			'url' 		=> 'bg/purchase', 
			'operation'	=> [
				Operation::READ
			],
		],
		'bg-sales' => [
			#'code'		=> 'sales', #判別用
			'name'		=> '御廚-銷售統計',
			'url' 		=> 'bg/sales', 
			'operation'	=> [
				Operation::READ
			],
		],
		
		
		/*============ 權限管理 ============*/
		'user' => [
			#'code'		=> 'users',
			'name' 		=> '帳號管理',
			'url' 		=> 'user', 
			'operation'	=> [
				Operation::READ, Operation::CREATE, Operation::UPDATE, Operation::DELETE, 
			],
		],
		'role' => [
			#'code'		=> 'roles',
			'name' 		=> '身份管理',
			'url' 		=> 'role',
			'operation'	=> [
				Operation::READ, Operation::CREATE, Operation::UPDATE, Operation::DELETE, 
			],
		],
			
	],
];
