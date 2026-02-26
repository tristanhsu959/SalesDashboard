<?php

use App\Enums\RoleGroup;
use App\Enums\Operation;

#Products 
/*============ 新品銷售 ============*/
return [
	
	##### 八方
	'bafang' => [
		'beefShortRibs' => [
			'saleDate' => '2026-03-02', #發售日
			'saleEndDate' => NULL, #停售日
			'name' => '牛小排麵',
			'primaryIds' => [ 
				'UC06100109', 'UC06100110'
			],
		],
	],
	
	##### 御廚
	'buygood' => [
		'porkRibs' => [
			'saleDate' => '2025-09-08', 
			'saleEndDate' => NULL,
			'name' => '橙汁排骨',
			'primaryIds' => [
				'UC06000126', 'UC06000127', 'UC00000042', 'UC00000043'
			],
			'secondaryIds' => [
				'PS02100021', 'PS02100022', 'PS02200047', 'PS02200048'
			],
		],
		
		'tomatoBeef' => [
			'saleDate' => '2025-10-13',
			'saleEndDate' => NULL, 
			'name' => '蕃茄牛三寶',
			'primaryIds' => [
				'UC07100017', 'UC07100018', 'UC03000024', 'UC03000025', 'UC06100105', 'UC06100106'
			],
			'secondaryIds' => [
				'PS02600009', 'PS02600010', 'PS04000046', 'PS04000047', 'PS02400026', 'PS02400027'
			],
		],
		
		'eggTofu' => [
			'saleDate' => '2025-12-16',
			'saleEndDate' => NULL, 
			'name' => '老皮嫩肉',
			'primaryIds' => [
				'UC04000050', 'UC04000051'
			],
			'secondaryIds' => [
				'PS02100023', 'PS02100024'
			],
		],
		
		#滷肉飯要加滷汁一起算, 但config排程也會用到, 原設定不能動
		'braisedPork' => [
			'saleDate' => '2025-12-16',
			'saleEndDate' => NULL, #停售日
			'name' => '主廚秘製滷肉飯',
			'primaryIds' => [
				'UC01000005', 'UC01000006', 'UC06100107', 'UC06100108'
			],
			'secondaryIds' => [
				'PS02400028', 'PS02400029'
			],
		],
		
		'braisedGravy' => [
			'saleDate' => '2025-12-16',
			'saleEndDate' => NULL, #停售日
			'name' => '秘製滷肉汁',
			'primaryIds' => [
				'UC02300008'
			],
			'secondaryIds' => [
				'PS02300011', 'PS02300012'
			],
		],
		#組合 braisedPork + braisedGravy => TBD, 看能怎麼做???
		'porkGravy' => [
			'saleDate' => '2025-12-16',
			'saleEndDate' => NULL, #停售日
			'name' => '滷肉飯加滷汁',
			#ids, valueAdded條件不影響, 因排程是抓原來的config
			'ids' => [ 
				'main' => ['UC02300008'], #梁社漢
				'mapping' => ['PS02300011', 'PS02300012'], #八方(複合店)
			],
		],
		'beefShortRibs' => [
			'saleDate' => '2026-03-02',
			'saleEndDate' => NULL, 
			'name' => '牛小排飯',
			'primaryIds' => [
				'UC06100109', 'UC06100110'
			],
			'secondaryIds' => [
				'PS02300011', 'PS02300012'
			],
		],
	],
];
