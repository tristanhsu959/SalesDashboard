<?php

namespace App\Enums;

enum Functions : string
{
	case HOME	= 'home';
	case USER	= 'user';
	case ROLE 	= 'role';
	case BG_PORKRIBS 	= 'bg-porkRibs';
	case BG_TOMATOBEEF 	= 'bg-tomatoBeef';
	case BG_EGGTOFU 	= 'bg-eggTofu';
	case BG_PORKGRAVY	= 'bg-porkGravy';
	
	public function label() : string
    {
        return match ($this) 
		{
			self::HOME		=> '首頁',
			self::USER		=> '帳號管理',
			self::ROLE 		=> '身份管理',
			self::BG_PORKRIBS 	=> '橙汁排骨',
			self::BG_TOMATOBEEF => '蕃茄牛三寶',
			self::BG_EGGTOFU 	=> '老皮嫩肉',
			self::BG_PORKGRAVY 	=> '滷肉飯加滷汁',
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
			self::BG_TOMATOBEEF->value	=> self::BG_TOMATOBEEF,
			self::BG_EGGTOFU->value		=> self::BG_EGGTOFU,
			self::BG_PORKGRAVY->value	=> self::BG_PORKGRAVY,
        };
	}
}
