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
			'name' 		=> Functions::BF_NEW_RELEASE->label(), #新品銷售
			'code'		=> Functions::BF_NEW_RELEASE->value,
			'style' 	=> ['icon' => 'chart_data', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.new_releases'),
		],
		[
			'name' 		=> Functions::BF_SALES->label(), #銷售統計
			'code'		=> Functions::BF_SALES->value,
			'style' 	=> ['icon' => 'point_of_sale', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.sales'),
		],
		[
			'name' 		=> Functions::BF_DAILY_REVENUE->label(), #門店營收
			'code'		=> Functions::BF_DAILY_REVENUE->value,
			'style' 	=> ['icon' => 'paid', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.daily_revenue'),
		],
		[
			'name' 		=> Functions::BF_SHIPMENTS->label(), #出貨查詢
			'code'		=> Functions::BF_SHIPMENTS->value,
			'style' 	=> ['icon' => 'local_shipping', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.shipments'),
		],
		[
			'name' 		=> Functions::BF_MONTHLY_FILLING->label(), #月初報表
			'code'		=> Functions::BF_MONTHLY_FILLING->value,
			'style' 	=> ['icon' => 'summarize', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.monthly_filling'),
		],
	],
	
	MenuGroup::BUYGOOD->value => [
		[
			'name' 		=> Functions::BG_NEW_RELEASE->label(), #新品銷售
			'code'		=> Functions::BG_NEW_RELEASE->value,
			'style' 	=> ['icon' => 'chart_data', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.new_releases'),
		],
		[
			'name' 		=> Functions::BG_SALES->label(), #銷售統計
			'code'		=> Functions::BG_SALES->value,
			'style' 	=> ['icon' => 'point_of_sale', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.sales'),
		],
		/* [
			'name' 		=> Functions::BG_PURCHASE->label(), #進貨統計
			'code'		=> Functions::BG_PURCHASE->value,
			'style' 	=> ['icon' => 'trolley', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.purchase'),
		], */
		[
			'name' 		=> Functions::BG_DAILY_REVENUE->label(), #門店營收
			'code'		=> Functions::BG_DAILY_REVENUE->value,
			'style' 	=> ['icon' => 'paid', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.daily_revenue'),
		],
		[
			'name' 		=> Functions::BG_SHIPMENTS->label(), #出貨查詢
			'code'		=> Functions::BG_SHIPMENTS->value,
			'style' 	=> ['icon' => 'local_shipping', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.shipments'),
		],
		
	],
	
	MenuGroup::FJVEGGIE->value => [
		[
			'name' 		=> Functions::FJ_DAILY_REVENUE->label(), #門店營收
			'code'		=> Functions::FJ_DAILY_REVENUE->value,
			'style' 	=> ['icon' => 'paid', 'color' => 'light-green-text'],
			'url' 		=> Str::replaceArray('?', [Brand::FJVEGGIE->code()], '?.daily_revenue'),
		],
	],
	
	MenuGroup::PRODUCT->value => [
		[
			'name' 		=> Functions::PRODUCT->label(), #產品基本資料,
			'code'		=> Functions::PRODUCT->value,
			'style' 	=> ['icon' => 'barcode', 'color' => 'light-blue-text'],
			'url' 		=> 'products', 
		],
		[
			'name' 		=> Functions::NEW_RELEASE_SETTING->label(), #新品設定,
			'code'		=> Functions::NEW_RELEASE_SETTING->value,
			'style' 	=> ['icon' => 'fiber_new', 'color' => 'light-blue-text'],
			'url' 		=> 'new_release_setting', 
		],
		/* [
			'name' 		=> Functions::SALES_SETTING->label(), #銷售設定,
			'code'		=> Functions::SALES_SETTING->value,
			'style' 	=> ['icon' => 'settings_applications', 'color' => 'light-blue-text'],
			'url' 		=> 'sales_setting', 
		], */
		[
			'name' 		=> Functions::SALES_PRODUCT->label(), #銷售產品設定,
			'code'		=> Functions::SALES_PRODUCT->value,
			'style' 	=> ['icon' => 'washoku', 'color' => 'light-blue-text'],
			'url' 		=> 'sales_product', 
		],
		[
			'name' 		=> Functions::PURCHASE_PRODUCT->label(), #訂貨產品設定,
			'code'		=> Functions::PURCHASE_PRODUCT->value,
			'style' 	=> ['icon' => 'warehouse', 'color' => 'light-blue-text'],
			'url' 		=> 'purchase_product', 
		],
	],
	
	MenuGroup::MANAGE->value => [
		[
			'name' 		=> Functions::USER->label(), #帳號管理,
			'code'		=> Functions::USER->value,
			'style' 	=> ['icon' => 'admin_panel_settings', 'color' => 'red-text'],
			'url' 		=> 'users', 
		],
		[
			'name' 		=> Functions::ROLE->label(), #身份管理,
			'code'		=> Functions::ROLE->value,
			'style' 	=> ['icon' => 'how_to_reg', 'color' => 'red-text'],
			'url' 		=> 'roles', 
		],
	],
];
