<?php

namespace App\Libraries\Sales;

use Illuminate\Support\Str;
use App\Enums\Brand;
use App\Enums\Area;

#Purchase order
class AreaLib
{
	#Bafang|Buygood area id to my area id
	public static function toId($srcId): int
	{
		return match ($srcId) 
		{
			1		=> 	Area::TAIPEI->value, #BF
			2		=> 	Area::TAIPEI->value, #BF
			3		=> 	Area::TAIPEI->value, #BF
			4		=> 	Area::TAIPEI->value,
			10002	=> 	Area::TAIPEI->value, #BF
			10003	=> 	Area::TAIPEI->value, #BF
			6		=> 	Area::TCM->value, #BF
			10004	=> 	Area::TCM->value, #BF
			7		=> 	Area::TCM->value,
			20		=> 	Area::TCM->value,
			9		=> 	Area::CCT->value, #BF
			21		=> 	Area::CCT->value, #BF
			10005	=> 	Area::CCT->value, #BF
			10		=> 	Area::CCT->value,
			22		=> 	Area::CCT->value,
			12		=> 	Area::YCN->value, #BF
			24		=> 	Area::YCN->value, #BF
			13		=> 	Area::YCN->value,
			25		=> 	Area::YCN->value,
			18		=> 	Area::YILAN->value, #BF
			15	 	=> 	Area::KAOHSIUNG->value, #BF
			27	 	=> 	Area::KAOHSIUNG->value, #BF
			16	 	=> 	Area::KAOHSIUNG->value,
			28	 	=> 	Area::KAOHSIUNG->value,
			19	 	=> 	Area::KAOHSIUNG->value, #BF
			30	 	=> 	Area::KAOHSIUNG->value, #BF
			default => 0,
		};
	}
	
	#To Bafang shopgroup gid
	public static function toPurchaseAreaId($brand, $srcIds): array
	{
		if ($brand == Brand::BAFANG)
			return self::toBafangId($srcIds);
		else if ($brand == Brand::BUYGOOD)
			return self::toBuygoodId($srcIds);
		else if ($brand == Brand::FJVEGGIE)
			return self::toFjVeggieId($srcIds);
		else
			return [];
	}
	
	#To Bafang shopgroup gid
	public static function toBafangId($srcIds): array
	{
		return collect($srcIds)->map(function ($value, int $key) {
			$value = intval($value);
			
			return match ($value) 
			{
				Area::TAIPEI->value		=> [1, 2, 3, 10002, 10003],
				Area::TCM->value		=> [6, 10004],
				Area::CCT->value 		=> [9, 21, 10005],
				Area::YCN->value		=> [12, 24],
				Area::YILAN->value 		=> [18],
				Area::KAOHSIUNG->value 	=> [15, 27, 19, 30],
				default => '0',
			};
			
		})->toArray();
	}
	
	#To Buygood shopgroup gid : toBuygoodId
	public static function toBuygoodId($srcIds): array
	{
		return collect($srcIds)->map(function ($value, int $key) {
			$value = intval($value);
			
			return match ($value) 
			{
				Area::TAIPEI->value		=> [4],
				Area::TCM->value		=> [7, 20],
				Area::CCT->value 		=> [10, 22],
				Area::YCN->value		=> [13, 25],
				Area::KAOHSIUNG->value 	=> [16, 28],
				#Area::YILAN->value 		=> [],
				default => '0',
			};
			
		})->toArray();
	}
	
	#To Fj shopgroup gid(同bafang):toFjVeggieId
	/* public static function toFjVeggieId($srcIds): array
	{
		return collect($srcIds)->map(function ($value, int $key) {
			$value = intval($value);
			
			return match ($value) 
			{
				Area::TAIPEI->value		=> '1',
				Area::TCM->value		=> '2',
				Area::CCT->value 		=> '3',
				Area::YCN->value		=> '4',
				Area::YILAN->value 		=> '5',
				Area::KAOHSIUNG->value 	=> '6',
				default => '0',
			};
			
		})->toArray();
	} */
}