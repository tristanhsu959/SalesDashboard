<?php

namespace App\Enums;

enum MenuGroup : int
{
	case BAFANG		= 1;
    case BUYGOOD 	= 2;
	case SYSTEM		= 90;
	case MANAGE		= 99;
	
	public function label() : string
    {
        return match ($this) 
		{
			self::BAFANG	=> '八方',
			self::BUYGOOD 	=> '御廚',
			self::SYSTEM 	=> '系統設定',
			self::MANAGE 	=> '權限管理',
        };
    }
}
