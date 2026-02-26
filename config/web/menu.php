<?php

use App\Enums\Brand;
use Illuminate\Support\Str;

#Menu Config (key與route要相同)
return [
	#Group (Enable List)
	Brand::BAFANG->value => [
		[
			'name' 		=> '新品銷售',
			'code'		=> Str::of('new_releases')->start(Brand::BAFANG->value . ':')->toString(),
			'style' 	=> ['icon' => 'chart_data', 'color' => 'purple'],
			'url' 		=> 'home',#Str::of('new_releases')->start(Brand::BAFANG->value . '/')->toString(), 
			'type'		=> 'function',
		],
	],
	
	Brand::BUYGOOD->value => [
		[
			'name' 		=> '新品銷售',
			'code'		=> Str::of('new_releases')->start(Brand::BUYGOOD->value . ':')->toString(),
			'style' 	=> ['icon' => 'chart_data', 'color' => 'purple'],
			'url' 		=> Str::of('new_releases')->start(Brand::BUYGOOD->value . '/')->toString(),  
			'type'		=> 'function',
		],
		[
			'name' 		=> '進貨統計',
			'code'		=> Str::of('purchase')->start(Brand::BUYGOOD->value . ':')->toString(),
			'style' 	=> ['icon' => 'trolley', 'color' => 'teal'],
			'url' 		=> Str::of('purchase')->start(Brand::BUYGOOD->value . '/')->toString(), 
			'type'		=> 'function',
		],
		[
			'name' 		=> '銷售統計',
			'code'		=> Str::of('sales')->start(Brand::BUYGOOD->value . ':')->toString(),
			'style' 	=> ['icon' => 'trolley', 'color' => 'teal'],
			'url' 		=> Str::of('sales')->start(Brand::BUYGOOD->value . '/')->toString(),
			'type'		=> 'function',
		],
	],
	
	'manage' => [
		[
			'name' 		=> '帳號管理',
			'code'		=> 'user',
			'style' 	=> ['icon' => 'admin_panel_settings', 'color' => 'red'],
			'url' 		=> 'user', 
			'type'		=> 'function',
		],
		[
			'name' 		=> '身份管理',
			'code'		=> 'role',
			'style' 	=> ['icon' => 'admin_panel_settings', 'color' => 'red'],
			'url' 		=> 'role', 
			'type'		=> 'function',
		],
	],
];
