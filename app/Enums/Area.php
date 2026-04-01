<?php

namespace App\Enums;

enum Area : int
{
    case TAIPEI		= 1;
	case YILAN 		= 2;
	case TCM 		= 3; #Taoyuan, Hsinchu, and Miaoli 
	case CCT 		= 4; #Taichung, Changhua, Nantou
	case YCN  		= 5; #Yunlin, Chiayi, Tainan
	case KAOHSIUNG  = 6; 
	
	public function label() : string
    {
        return match ($this) 
		{
			self::TAIPEI	=> '大台北區',
			self::YILAN 	=> '宜蘭區',		#此區可歸至大台北
			self::TCM		=> '桃竹苗區',
			self::CCT 		=> '中彰投區',
			self::YCN		=> '雲嘉南區',
			self::KAOHSIUNG => '大高雄區',
        };
    }
	
	public static function getAll() : array
	{
		$list = [];
		
		$list[] = self::TAIPEI->value;
		$list[] = self::YILAN->value;
		$list[] = self::TCM->value;
		$list[] = self::CCT->value;
		$list[] = self::YCN->value;
		$list[] = self::KAOHSIUNG->value;
		
		return $list;
	}
	
	public static function options(): array
	{
		return collect(self::cases())->mapWithKeys(function ($case) {
			return [$case->value => $case->label()];
		})->toArray();
	}
	
	#Bafang|Buygood shopgroup gid to brand id
	public static function toId($srcId): int
	{
		return match ($srcId) 
		{
			'1'		=> 	self::TAIPEI->value,
			'2'		=> 	self::TCM->value,
			'3'		=> 	self::CCT->value,
			'4'		=> 	self::YCN->value,
			'5'		=> 	self::YILAN->value,
			'6'	 	=> 	self::KAOHSIUNG->value,
			'A01'	=>  self::TAIPEI->value,	
			'A02'	=>  self::TCM->value,	
			'A03'	=>  self::CCT->value,		
			'A04'	=>  self::YCN->value,
			'A05'	=>  self::KAOHSIUNG->value,
			'A06'	=>  self::YILAN->value,
			default => 'N/A',
		};
	}
	
	#To Bafang shopgroup gid
	public static function toBafangId($srcIds): array
	{
		return collect($srcIds)->map(function ($value, int $key) {
			return match ($value) 
			{
				self::TAIPEI->value		=> '1',
				self::TCM->value		=> '2',
				self::CCT->value 		=> '3',
				self::YCN->value		=> '4',
				self::YILAN->value 		=> '5',
				self::KAOHSIUNG->value 	=> '6',
				default => '0',
			};
		})->toArray();
	}
	
	#To Buygood shopgroup gid
	public static function toBuygoodId($srcIds): array
	{
		return collect($srcIds)->map(function ($value, int $key) {
			return match ($value) 
			{
				self::TAIPEI->value		=> 'A01',
				self::TCM->value		=> 'A02',
				self::CCT->value 		=> 'A03',
				self::YCN->value		=> 'A04',
				self::KAOHSIUNG->value 	=> 'A05',
				self::YILAN->value 		=> 'A06',
				default => '0',
			};
		})->toArray();
	}
}
