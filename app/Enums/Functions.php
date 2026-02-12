<?php

namespace App\Enums;

enum Functions : string
{
	case HOME	= 'home';
	case USER	= 'user';
	case ROLE 	= 'role';
	
	case BF_BEEFSHORTRIBS	= 'bf-beefShortRibs';
	
	case BG_PORKRIBS 		= 'bg-porkRibs';
	case BG_TOMATOBEEF 		= 'bg-tomatoBeef';
	case BG_EGGTOFU 		= 'bg-eggTofu';
	case BG_PORKGRAVY		= 'bg-porkGravy';
	case BG_PURCHASE		= 'bg-purchase';
	case BG_SALES			= 'bg-sales';
	case BG_BEEFSHORTRIBS	= 'bg-beefShortRibs';
	
	
	public function label() : string
    {
        return match ($this) 
		{
			self::HOME		=> '首頁',
			self::USER		=> '帳號管理',
			self::ROLE 		=> '身份管理',
			#八方
			self::BF_BEEFSHORTRIBS	=> '牛小排麵',
			#御廚
			self::BG_PORKRIBS 	=> '橙汁排骨',
			self::BG_TOMATOBEEF => '蕃茄牛三寶',
			self::BG_EGGTOFU 	=> '老皮嫩肉',
			self::BG_PORKGRAVY 	=> '滷肉飯加滷汁',
			self::BG_BEEFSHORTRIBS	=> '牛小排飯',
			self::BG_PURCHASE 	=> '進貨統計',
			self::BG_SALES 		=> '銷售統計',
        };
    }
	
	public static function getByValue($value)
	{
		return match ($value) 
		{
			self::HOME->value				=> self::HOME,
			self::USER->value				=> self::USER,
			self::ROLE->value				=> self::ROLE,
			#八方
			self::BF_BEEFSHORTRIBS->value	=> self::BF_BEEFSHORTRIBS,
			#御廚
			self::BG_PORKRIBS->value		=> self::BG_PORKRIBS,
			self::BG_TOMATOBEEF->value		=> self::BG_TOMATOBEEF,
			self::BG_EGGTOFU->value			=> self::BG_EGGTOFU,
			self::BG_PORKGRAVY->value		=> self::BG_PORKGRAVY,
			self::BG_BEEFSHORTRIBS->value	=> self::BG_BEEFSHORTRIBS,
			
			self::BG_PURCHASE->value		=> self::BG_PURCHASE,
			self::BG_SALES->value			=> self::BG_SALES,
        };
	}
}
