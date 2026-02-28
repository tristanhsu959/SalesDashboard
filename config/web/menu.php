<?php

use App\Enums\MenuGroup;
use App\Enums\Brand;
use Illuminate\Support\Str;

#Menu Config (key與route要相同)
return [
	#Group (Enable List)
	MenuGroup::BAFANG->value => [
		[
			'name' 		=> '新品銷售',
			'code'		=> Str::replaceArray('?', [Brand::BAFANG->value], '?:new_releases'),
			'style' 	=> ['icon' => 'chart_data', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->value], '?.new_releases'),
		],
	],
	
	MenuGroup::BUYGOOD->value => [
		[
			'name' 		=> '新品銷售',
			'code'		=> Str::replaceArray('?', [Brand::BUYGOOD->value], '?:new_releases'),
			'style' 	=> ['icon' => 'chart_data', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->value], '?.new_releases'),
		],
		[
			'name' 		=> '進貨統計',
			'code'		=> Str::replaceArray('?', [Brand::BUYGOOD->value], '?:purchase'),
			'style' 	=> ['icon' => 'trolley', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->value], '?.purchase'),
		],
		[
			'name' 		=> '銷售統計',
			'code'		=> Str::replaceArray('?', [Brand::BUYGOOD->value], '?:sales'),
			'style' 	=> ['icon' => 'point_of_sale', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->value], '?.sales'),
		],
	],
	
	MenuGroup::MANAGE->value => [
		[
			'name' 		=> '帳號管理',
			'code'		=> 'user',
			'style' 	=> ['icon' => 'admin_panel_settings', 'color' => 'red-text'],
			'url' 		=> 'users', 
		],
		[
			'name' 		=> '身份管理',
			'code'		=> 'role',
			'style' 	=> ['icon' => 'admin_panel_settings', 'color' => 'red-text'],
			'url' 		=> 'roles', 
		],
	],
];
