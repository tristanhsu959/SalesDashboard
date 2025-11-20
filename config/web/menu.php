<?php

use App\Enums\Operation;

#Menu Config
return [
	
	[
		'groupName' => '新品銷售',
		'groupCode' => '01', #permission code
		'groupIcon' => ['name' => 'chart_data', 'filled' => FALSE],
		'items' => [
			[
				'actionCode'	=> '01', #permission code
				'segmentCode'	=> 'pork_ribs', #判別用
				'name' 			=> '橙汁排骨',
				'url' 			=> 'new_releases/pork_ribs', #from newproduct.config
				'operation'		=> [
					Operation::READ
				],
				
			],
			[
				'actionCode'	=> '02',
				'segmentCode'	=> 'tomato_beef',
				'name' 			=> '番茄牛三寶麵',
				'url' 			=> 'new_releases/tomato_beef',
				'operation'		=> [
					Operation::READ
				],
			]
			#chickenfillet
		],
	],
	
	[
		'groupName' => '權限管理',
		'groupCode' => '02',
		'groupIcon' => ['name' => 'chart_data', 'filled' => FALSE],
		'items' => [
			[
				'actionCode'	=> '01',
				'segmentCode'	=> 'users',
				'name' 			=> '帳號管理',
				'url' 			=> 'users', 
				'operation'		=> [
					Operation::READ, Operation::CREATE, Operation::UPDATE, Operation::DELETE, 
				],
			],
			[
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
