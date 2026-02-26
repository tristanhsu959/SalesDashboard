<?php

namespace App\Enums;

enum Brand : string
{
	case BAFANG		= 'bafang';
	case BUYGOOD 	= 'buygood';
    
	public function label() : string
    {
        return match ($this) 
		{
			self::BAFANG	=> '八方',
			self::BUYGOOD	=> '御廚',
		};
    }
	
	public static function getByValue($value)
	{
		return match ($value) 
		{
			self::BAFANG->value		=> self::BAFANG,
			self::BUYGOOD->value	=> self::BUYGOOD,
        };
	}
}
