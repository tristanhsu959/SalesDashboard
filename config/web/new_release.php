<?php

use App\Enums\Brand;

#新品
return [
	#New product items | BF=>八方 ｜BG=>梁社漢
	#梁社漢須多處理複合店, 要多取八方DB
	#不需含套餐，因已會拆成單品項
	'products' => [
		
		'porkRibs' => [
			'saleDate' => '2025-09-08', #發售日
			'saleEndDate' => NULL, #停售日
			'name' => '橙汁排骨',
			'ids' => [
				'main' => ['UC06000126', 'UC06000127', 'UC00000042', 'UC00000043'], #梁社漢
				'mapping' => ['PS02100021', 'PS02100022', 'PS02200047', 'PS02200048'], #八方(複合店)
			],
			'valueAdded' => '', #加值判別
			'brand' => Brand::BUYGOOD->value, #表示屬梁社漢新品
		],
		
		'tomatoBeef' => [
			'saleDate' => '2025-10-13',
			'saleEndDate' => NULL, #停售日
			'name' => '蕃茄牛三寶',
			'ids' => [
				'main' => ['UC07100017', 'UC07100018', 'UC03000024', 'UC03000025', 'UC06100105', 'UC06100106'], #梁社漢
				'mapping' => ['PS02600009', 'PS02600010', 'PS04000046', 'PS04000047', 'PS02400026', 'PS02400027'], #八方(複合店)
			],
			'valueAdded' => '', #加值判別
			'brand' => Brand::BUYGOOD->value, #表示屬梁社漢新品
		],
		
		'braisedPork' => [
			'saleDate' => '2025-12-16',
			'saleEndDate' => NULL, #停售日
			'name' => '主廚秘製滷肉飯',
			'ids' => [
				'main' => ['UC01000005', 'UC01000006', 'UC06100107', 'UC06100108'], #梁社漢
				'mapping' => ['PS02400028', 'PS02400029'], #八方(複合店)
			],
			'valueAdded' => '', #加值判別
			'brand' => Brand::BUYGOOD->value, #表示屬梁社漢新品
		],
		
		'eggTofu' => [
			'saleDate' => '2025-12-16',
			'saleEndDate' => NULL, #停售日
			'name' => '老皮嫩肉',
			'ids' => [
				'main' => ['UC04000050', 'UC04000051'], #梁社漢
				'mapping' => ['PS02100023', 'PS02100024'], #八方(複合店)
			],
			'valueAdded' => '', #加值判別
			'brand' => Brand::BUYGOOD->value, #表示屬梁社漢新品
		],
		
		'braisedGravy' => [
			'saleDate' => '2025-12-16',
			'saleEndDate' => NULL, #停售日
			'name' => '秘製滷肉汁',
			'ids' => [
				'main' => ['UC02300008'], #梁社漢
				'mapping' => ['PS02300011', 'PS02300012'], #八方(複合店)
			],
			'valueAdded' => '秘製滷肉汁', #加值判別
			'brand' => Brand::BUYGOOD->value, #表示屬梁社漢新品
		],
    ],
	
	#複合店=> 八方:梁社漢 / 梁社漢的訂單銷售會存至poserp(只有梁社漢有此狀況)
	'multiBrandShopidMapping' => [
		
		#shop id 八方=>梁社漢
		'0001' => '106003', 
		'0966' => '112004',

    ],
	
	#Pos DB mapping to local DB 
	'DbMapping' => [
		'porkRibs'		=> 'pork_ribs', 
		'tomatoBeef' 	=> 'tomato_beef', 
		'braisedPork' 	=> 'braised_pork',
		'eggTofu' 		=> 'egg_tofu',
		'braisedGravy' 	=> 'braised_gravy' ,
	],
];


/*

/*
主廚秘製滷肉飯 Braised Pork
UC01000005
UC01000006
UC06100107
UC06100108
PS02400028
PS02400029
這四個料號

老皮嫩肉 Egg Tofu
UC04000050
UC04000051
PS02100023
PS02100024

秘製滷肉汁
UC02300008
PS02300011
PS02300012
*/