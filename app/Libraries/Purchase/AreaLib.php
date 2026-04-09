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
			'1'		=> 	Area::TAIPEI->value,
			'2'		=> 	Area::TCM->value,
			'3'		=> 	Area::CCT->value,
			'4'		=> 	Area::YCN->value,
			'5'		=> 	Area::YILAN->value,
			'6'	 	=> 	Area::KAOHSIUNG->value,
			'A01'	=>  Area::TAIPEI->value,	
			'A02'	=>  Area::TCM->value,	
			'A03'	=>  Area::CCT->value,		
			'A04'	=>  Area::YCN->value,
			'A05'	=>  Area::KAOHSIUNG->value,
			'A06'	=>  Area::YILAN->value,
			default => 'N/A',
		};
	}
	
	#To Bafang shopgroup gid
	public static function toSalesAreaId($brand, $srcIds): array
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
				Area::TAIPEI->value		=> '1',
				Area::TCM->value		=> '2',
				Area::CCT->value 		=> '3',
				Area::YCN->value		=> '4',
				Area::YILAN->value 		=> '5',
				Area::KAOHSIUNG->value 	=> '6',
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
				Area::TAIPEI->value		=> 'A01',
				Area::TCM->value		=> 'A02',
				Area::CCT->value 		=> 'A03',
				Area::YCN->value		=> 'A04',
				Area::KAOHSIUNG->value 	=> 'A05',
				Area::YILAN->value 		=> 'A06',
				default => '0',
			};
			
		})->toArray();
	}
	
	#To Fj shopgroup gid(同bafang):toFjVeggieId
	public static function toFjVeggieId($srcIds): array
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
	}
}