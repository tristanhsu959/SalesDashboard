<?php

use App\Enums\MenuGroup;
use App\Enums\Brand;
use App\Enums\Functions;
use Illuminate\Support\Str;

#Menu Config (key與route要相同)
return [
	#Group (Enable List)
	MenuGroup::BAFANG->value => [
		[
			'name' 		=> Functions::BF_NEW_RELEASE->label(), #'新品銷售',
			'code'		=> Functions::BF_NEW_RELEASE->value,
			'style' 	=> ['icon' => 'chart_data', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.new_releases'),
		],
	],
	
	MenuGroup::BUYGOOD->value => [
		[
			'name' 		=> Functions::BG_NEW_RELEASE->label(), #'新品銷售',
			'code'		=> Functions::BG_NEW_RELEASE->value,
			'style' 	=> ['icon' => 'chart_data', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.new_releases'),
		],
		[
			'name' 		=> Functions::BG_PURCHASE->label(), #'進貨統計',
			'code'		=> Functions::BG_PURCHASE->value,
			'style' 	=> ['icon' => 'trolley', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.purchase'),
		],
		[
			'name' 		=> Functions::BG_SALES->label(), #'銷售統計',
			'code'		=> Functions::BG_SALES->value,
			'style' 	=> ['icon' => 'point_of_sale', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.sales'),
		],
	],
	
	MenuGroup::SYSTEM->value => [
		[
			'name' 		=> Functions::PRODUCT->label(), #'產品設定',
			'code'		=> Functions::PRODUCT->value,
			'style' 	=> ['icon' => 'barcode', 'color' => 'light-blue-text'],
			'url' 		=> 'products', 
		],
	],
	
	MenuGroup::MANAGE->value => [
		[
			'name' 		=> Functions::USER->label(), #'帳號管理',
			'code'		=> Functions::USER->value,
			'style' 	=> ['icon' => 'admin_panel_settings', 'color' => 'red-text'],
			'url' 		=> 'users', 
		],
		[
			'name' 		=> Functions::ROLE->label(), #'身份管理',
			'code'		=> Functions::ROLE->value,
			'style' 	=> ['icon' => 'admin_panel_settings', 'color' => 'red-text'],
			'url' 		=> 'roles', 
		],
	],
];
