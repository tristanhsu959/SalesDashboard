<?php

namespace App\Enums;

enum Functions : string
{
	case HOME					= 'home';
	case USER					= 'user';
	case ROLE 					= 'role';
		
	#System	
	case PRODUCT 				= 'product';
	case NEW_RELEASE_SETTING	= 'new_releases_setting';
	case SALES_SETTING			= 'sales_setting';
		
	#Bafang	
	case BF_NEW_RELEASE			= 'bafang:new_releases';
	case BF_PURCHASE			= 'bafang:purchase';
	case BF_SALES				= 'bafang:sales';
	case BF_TODAY_SALES			= 'bafang:today_sales';
		
	#Buygood	
	case BG_NEW_RELEASE			= 'buygood:new_releases';
	case BG_PURCHASE			= 'buygood:purchase';
	case BG_SALES				= 'buygood:sales';
	case BG_TODAY_SALES			= 'buygood:today_sales';
	
	
	public function label() : string
    {
        return match ($this) 
		{
			self::HOME					=> '首頁',
			self::USER					=> '帳號管理',
			self::ROLE 					=> '身份管理',
			self::PRODUCT 				=> '產品料號設定',
			self::NEW_RELEASE_SETTING	=> '新品設定',
			self::SALES_SETTING			=> '銷售設定',
			
			#八方
			self::BF_NEW_RELEASE		=> '新品銷售',
			self::BF_PURCHASE 			=> '進貨統計',
			self::BF_SALES 				=> '銷售統計',
			self::BF_TODAY_SALES		=> '本日營收',
			
			#御廚
			self::BG_NEW_RELEASE 		=> '新品銷售',
			self::BG_PURCHASE 			=> '進貨統計',
			self::BG_SALES 				=> '銷售統計',
			self::BG_TODAY_SALES		=> '本日營收',
        };
    }
}
