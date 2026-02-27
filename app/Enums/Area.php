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
			self::YILAN 	=> '宜蘭區',
			self::TCM		=> '桃竹苗區',
			self::CCT 		=> '中彰投區',
			self::YCN		=> '雲嘉南區',
			self::KAOHSIUNG => '大高雄區',
        };
    }
	
	public static function getLabelByValue($value) : string
	{
		#型別要一樣
		$value = intval($value);
		
		return match($value)
		{
			self::TAIPEI->value		=> self::TAIPEI->label(),
			self::YILAN->value 		=> self::YILAN->label(),
			self::TCM->value		=> self::TCM->label(),
			self::CCT->value 		=> self::CCT->label(),
			self::YCN->value		=> self::YCN->label(),
			self::KAOHSIUNG->value	=> self::KAOHSIUNG->label(),
			default => 'UNKNOW',
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
}
