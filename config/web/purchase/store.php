<?php

use App\Enums\Brand;

#Store
return [
	'except' => [
		Brand::BAFANG->value => [
			'KH1100000', 'KH1100100', 'KH1688', 'KH16888', 'KH168888',
			'TP99999991', 'KH1034', 'KH99999991', 'TPB000123'
		],
		Brand::BUYGOOD->value => [
		],
	],
		
	
];
