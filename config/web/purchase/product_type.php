<?php

use App\Enums\Brand;

#Product category
return [
	'typeNo' => [
		'enabled' => [
			Brand::BAFANG->value => [
				'A', 'A2', 'A3', 'B',
				'D', 'E', 'F', 'G'
			],
			
			Brand::BUYGOOD->value => [
				'A', 'A2', 'A3',
				'D', 'E', 'F', 'G'
			],
		],
		'except' => [
			Brand::BAFANG->value => [
				'H', 'I', 'Z'
			],
		
			Brand::BUYGOOD->value => [
				'H', 'I', 'Z'
			]
		]
	],
	'shortCode' => [
		#use for like
		'enabled' => [
			'0%', '2%', '3%', '4%', '5%', '6%', '7%', '8%', '9%',
		],
		'except' => [
			
		]
	],
	'groupPrefix' => [
		[
			'id'		=> 1,
			'name' 		=> '餡類',
			'pattern' 	=> ['00']
		],
		[
			'id'		=> 2,
			'name' 		=> '皮類',
			'pattern' 	=> ['01']
		],
		[
			'id'		=> 3,
			'name' 		=> '麵類',
			'pattern' 	=> ['03']
		],
		[
			'id'		=> 11,
			'name' 		=> '主食類',
			'pattern' 	=> ['22']
		],
		[
			'id'		=> 12,
			'name' 		=> '小菜',
			'pattern' 	=> ['50', '52', '53', '9961']
		],
		[
			'id'		=> 13,
			'name' 		=> '飲品類',
			'pattern' 	=> ['02']
		],
		[
			'id'		=> 21,
			'name' 		=> '菜類',
			'pattern' 	=> ['56', '99']
		],
		[
			'id'		=> 22,
			'name' 		=> '湯料',
			'pattern' 	=> ['20', '30', '32', '42']
		],
		[
			'id'		=> 31,
			'name' 		=> '麵醬料',
			'pattern' 	=> ['34', '36']
		],
		[
			'id'		=> 32,
			'name' 		=> '調味類',
			'pattern' 	=> ['44']
		],
		[
			'id'		=> 91,
			'name' 		=> '乾貨',
			'pattern' 	=> ['37', '39', '40', '43', '45', '80']
		],
		[
			'id'		=> 99,
			'name' 		=> '其它',
			'pattern' 	=> ['9019', '9020']
		],
		[
			'id'		=> 92,
			'name' 		=> '雜項',
			'pattern' 	=> ['6', '7', '82', '90', '95']
		],
	],
];

/* Short code
餡類					00xx
皮類					01xx
飲料					02xx
麵類					03xx
冷藏類				30xx 32xx 34xx 36xx 37xx 39xx 50xx 52xx 53xx - filter out
生鮮					56xx
冷凍類				20xx 
乾貨類				40xx 42xx 43xx 44xx 46xx - filter out
美食類				22xx
雜項類（制服、菜單）	6xxx 7xxx - filter out
五金類				9xxx - filter out

===
餡類					00xx
皮類					01xx
飲料					02xx
麵類					03xx
湯料					20xx 30xx 42xx
醬包					34xx 3601
麵醬					36xx (除3601)
粉					40xx
調味料				42xx 44xx
湯包					44xx
小菜					50xx 52xx
*/

/* BF b.No not in ('F', 'Z', 'H', 'I')
1 : A A2 A3 B
2 : D E G
3 : F H I Z
餡類					A
皮類					A2
餡皮類-2				A3
麵類					B
冷藏類				D
冷凍類				E
乾貨類				F
美食類				G
雜項類（制服、菜單）	H
五金類				I
退貨單條類(不列印)		I
供應商出貨			Z
*/

#--------
/* BG ProducType AEGJDFZHI
b.No not in ('F', 'Z', 'H', 'I')
1 : A A2 A3
2 : D E G 
3 : J F(有錯誤類別) Z H I

餡類					A
皮類					A2
主食類				A3
冷藏類				D
冷凍類				E
乾貨類				F
美食類				G
雜項類（制服、菜單）	H
五金類				I
生鮮菜類				J
供應商出貨			Z
*/


