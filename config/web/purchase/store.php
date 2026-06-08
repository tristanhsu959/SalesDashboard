<?php

use App\Enums\Brand;

#Store
return [
	'except' => [
		Brand::BAFANG->value => [
			'KH1100000', 'KH1100100', 'KH1688', 'KH16888', 'KH168888',
			'TP99999991', 'KH1034', 'KH99999991', 'TPB000123', '4030007'
		],
		Brand::BUYGOOD->value => [
			'TS10006000', 'TS999111', 'RLbg999', 'RL1002'
		],
	],
		
	#蘿蔔店:特別要處理的店(蘿蔔 => 八方)
	'lbSpecialStore'=> [
		'TP11100152' => 'TP11100071',
		'TP11200112' => 'TP11200051',
	],
];
