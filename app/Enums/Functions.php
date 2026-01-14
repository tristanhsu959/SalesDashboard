<?php

namespace App\Enums;

enum Functions : string
{
	case HOME	= 'home';
	case USER	= 'user';
	case ROLE 	= 'role';
	case BG_PORKRIBS = 'bg-porkRibs';
	
	public function label() : string
    {
        return match ($this) 
		{
			self::HOME		=> '首頁',
			self::USER		=> '帳號管理',
			self::ROLE 		=> '身份管理',
			self::BG_PORKRIBS => '御廚::新品銷售',
        };
    }
	
	public static function getByValue($value)
	{
		return match ($value) 
		{
			self::HOME->value			=> self::HOME,
			self::USER->value			=> self::USER,
			self::ROLE->value			=> self::ROLE,
			self::BG_PORKRIBS->value	=> self::BG_PORKRIBS,
        };
	}
}
