<?php

use App\Enums\Brand;

#Product category
return [
	'productType' => [
		Brand::BAFANG->value => [
			'enabled' => [
				'A', 'A2', 'A3', 'B',
				'D', 'E', 'G'
			],
			'except' => [
				'F', 'H', 'I', 'Z'
			]
		],
		
		Brand::BUYGOOD->value => [
			'enabled' => [
				'A', 'A2', 'A3',
				'D', 'E', 'G'
			],
			'except' => [
				'F', 'H', 'I', 'Z'
			]
		],
	],
];

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


