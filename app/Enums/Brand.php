<?php

namespace App\Enums;

enum Brand : string
{
	case BAFANG		= 'BF';
	case BUYGOOD 	= 'BG';
    
	public function label() : string
    {
        return match ($this) 
		{
			self::BAFANG	=> '八方',
			self::BUYGOOD	=> '梁社漢',
		};
    }
}
