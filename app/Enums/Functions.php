<?php

namespace App\Enums;

enum Functions : string
{
	case HOME				= 'home';
	case USER				= 'user';
	case ROLE 				= 'role';
	#System
	case PRODUCT 			= 'product';
	case NEW_ITEM 			= 'new_item';
	#Bafang
	case BF_NEW_RELEASE		= 'bafang:new_releases';
	#Buygood
	case BG_NEW_RELEASE		= 'buygood:new_releases';
	case BG_PURCHASE		= 'buygood:purchase';
	case BG_SALES			= 'buygood:sales';
	
	
	public function label() : string
    {
        return match ($this) 
		{
			self::HOME				=> '首頁',
			self::USER				=> '帳號管理',
			self::ROLE 				=> '身份管理',
			self::PRODUCT 			=> '產品基本資料',
			self::NEW_ITEM 			=> '新品設定',
			#八方
			self::BF_NEW_RELEASE	=> '新品銷售',
			#御廚
			self::BG_NEW_RELEASE 	=> '新品銷售',
			self::BG_PURCHASE 		=> '進貨統計',
			self::BG_SALES 			=> '銷售統計',
        };
    }
}
