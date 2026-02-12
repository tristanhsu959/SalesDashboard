<?php

use App\Enums\Brand;

#八方新品
return [
	#不需含套餐，因已會拆成單品項
	'products' => [
		
		'beefShortRibs' => [
			'saleDate' => '2026-03-02',
			'saleEndDate' => NULL, #停售日
			'name' => '牛小排麵',
			#ids, valueAdded條件不影響, 因排程是抓原來的config
			'ids' => [ 
				'main' => ['UC06100109', 'UC06100110'], #梁社漢
				'mapping' => ['', ''], #八方(複合店)
			],
			'valueAdded' => '', #加值判別
			'brand' => Brand::BAFANG->value, #表示屬八方新品
		],
    ],
	
	#複合店=> 只有梁社漢有
	'multiBrandShopidMapping' => [
    ],
	
	#Pos DB mapping to local DB 
	'DbMapping' => [
		'beefShortRibs' => 'bf_beef_short_ribs',
	],
];

