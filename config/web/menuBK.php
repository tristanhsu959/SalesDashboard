<?php

use App\Enums\RoleGroup;
use App\Enums\Operation;

#Menu Config (key與route要相同)
return [
	
	'newRelease' => [
		'groupName' => '新品銷售',
		'groupCode' => '01', #permission code
		'groupIcon' => ['name' => 'chart_data', 'style' => 'purple'], #filled-icon 
		'groupType' => '',
		'items' => [
			'porkRibs' => [
				'actionCode'	=> '01', #permission code
				'segmentCode'	=> 'pork_ribs', #判別用
				'name' 			=> '橙汁排骨',
				'url' 			=> 'new_releases/pork_ribs', #from newproduct.config
				'operation'		=> [
					Operation::READ
				],
				
			],
			'tomatoBeef' => [
				'actionCode'	=> '02',
				'segmentCode'	=> 'tomato_beef',
				'name' 			=> '番茄牛三寶麵',
				'url' 			=> 'new_releases/tomato_beef',
				'operation'		=> [
					Operation::READ
				],
			],
			'braisedPork' => [
				'actionCode'	=> '04',
				'segmentCode'	=> 'braised_pork',
				'name' 			=> '主廚秘製滷肉飯',
				'url' 			=> 'new_releases/braised_pork',
				'operation'		=> [
					Operation::READ
				],
			],
			'eggTofu' => [
				'actionCode'	=> '08',
				'segmentCode'	=> 'egg_tofu',
				'name' 			=> '老皮嫩肉',
				'url' 			=> 'new_releases/egg_tofu',
				'operation'		=> [
					Operation::READ
				],
			],
			'braisedGravy' => [
				'actionCode'	=> '10',
				'segmentCode'	=> 'braised_gravy',
				'name' 			=> '秘製滷肉汁',
				'url' 			=> 'new_releases/braised_gravy',
				'operation'		=> [
					Operation::READ
				],
			]
			
		],
	],
	
	'authManager' => [
		'groupName' => '權限管理',
		'groupCode' => '02',
		'groupIcon' => ['name' => 'admin_panel_settings', 'style' => 'red'],
		'groupType' => RoleGroup::ADMIN->name,
		'items' => [
			'users' => [
				'actionCode'	=> '01',
				'segmentCode'	=> 'users',
				'name' 			=> '帳號管理',
				'url' 			=> 'users', 
				'operation'		=> [
					Operation::READ, Operation::CREATE, Operation::UPDATE, Operation::DELETE, 
				],
			],
			'roles' => [
				'actionCode'	=> '02',
				'segmentCode'	=> 'roles',
				'name' 			=> '身份管理',
				'url' 			=> 'roles',
				'operation'		=> [
					Operation::READ, Operation::CREATE, Operation::UPDATE, Operation::DELETE, 
				],
			]
		],
	],
	
];
