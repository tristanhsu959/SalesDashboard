<?php

namespace App\Enums;

enum Area : int
{
    case ALL		= 1;
	case TAIPEI		= 2;
	case YILAN 		= 3;
	case TCM 		= 4; #Taoyuan, Hsinchu, and Miaoli 
	case CCT 		= 5; #Taichung, Changhua, Nantou
	case YCN  		= 6; #Yunlin, Chiayi, Tainan
	case KAOHSIUNG  = 7; 
	
	public function label() : string
    {
        return match ($this) 
		{
			self::ALL		=> '全區',
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
			self::ALL->value		=> self::ALL->label(),
			self::TAIPEI->value		=> self::TAIPEI->label(),
			self::YILAN->value 		=> self::YILAN->label(),
			self::TCM->value		=> self::TCM->label(),
			self::CCT->value 		=> self::CCT->label(),
			self::YCN->value		=> self::YCN->label(),
			self::KAOHSIUNG->value	=> self::KAOHSIUNG->label(),
			default => 'UNKNOW',
		};
	}
}
