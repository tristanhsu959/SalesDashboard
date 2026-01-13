<?php

namespace App\Enums;

enum Functions : string
{
	case HOME	= 'home';
	case USER	= 'user';
	case ROLE 	= 'role';
	
	public function label() : string
    {
        return match ($this) 
		{
			self::HOME		=> '首頁',
			self::USER		=> '帳號管理',
			self::ROLE 		=> '身份管理',
        };
    }
}
