<?php

use App\Enums\Brand;

#Store
return [
	Brand::BAFANG->value => [
		'except' => [
			'KH1100000', 'KH1100100', 'KH1688', 'KH16888', 'KH168888',
			'TP99999991', 'KH1034',
		]
	],
		
	Brand::BUYGOOD->value => [
		'except' => [
			
		]
	],
];
