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
}
