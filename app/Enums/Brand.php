<?php

namespace App\Enums;

enum Brand : int
{
	case BAFANG		= 1;
	case BUYGOOD 	= 2;
    
	public function label() : string
    {
        return match ($this) 
		{
			self::BAFANG	=> '八方',
			self::BUYGOOD	=> '御廚',
		};
    }
	
	public function code()
	{
		return match ($this) 
		{
			self::BAFANG	=> 'bafang',
			self::BUYGOOD	=> 'buygood',
        };
	}
}
