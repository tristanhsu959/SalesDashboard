<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum Functions : string
{
	case HOME					= 'home'; #non permission
	case USER					= 'user';
	case ROLE 					= 'role';
	case STORE_INFO 			= 'store_info'; #non permission
		
	#Product	
	case PRODUCT 				= 'product';
	case NEW_RELEASE_SETTING	= 'new_releases_setting';
	case SALES_SETTING			= 'sales_setting'; #deprecated
	case SALES_PRODUCT			= 'sales_product';
	case PURCHASE_PRODUCT		= 'purchase_product';
		
	#Bafang	
	case BF_NEW_RELEASE			= 'bafang:new_releases';
	case BF_SALES				= 'bafang:sales';
	case BF_DAILY_REVENUE		= 'bafang:daily_revenue';
	case BF_PURCHASE			= 'bafang:purchase';
	case BF_SHIPMENTS			= 'bafang:shipments';
	case BF_MONTHLY_FILLING		= 'bafang:monthly_filling';
		
	#Buygood	
	case BG_NEW_RELEASE			= 'buygood:new_releases';
	case BG_SALES				= 'buygood:sales';
	case BG_DAILY_REVENUE		= 'buygood:daily_revenue';
	case BG_PURCHASE			= 'buygood:purchase';
	case BG_SHIPMENTS			= 'buygood:shipments';
	
	#FjVeggie	
	case FJ_DAILY_REVENUE		= 'fjVeggie:daily_revenue';
	
	
	public function label() : string
    {
        return match ($this) 
		{
			self::HOME					=> '首頁',
			self::STORE_INFO			=> '門店資訊',
			self::USER					=> '帳號管理',
			self::ROLE 					=> '身份管理',
			self::PRODUCT 				=> '產品料號設定',
			self::NEW_RELEASE_SETTING	=> '新品設定',
			self::SALES_SETTING			=> '銷售設定',
			self::SALES_PRODUCT			=> '銷售產品設定',
			self::PURCHASE_PRODUCT		=> '出貨產品設定',
			
			#八方
			self::BF_NEW_RELEASE		=> '新品銷售',
			self::BF_SHIPMENTS 			=> '出貨總量查詢',
			self::BF_PURCHASE 			=> '出貨統計',
			self::BF_SALES 				=> '銷售統計',
			self::BF_DAILY_REVENUE		=> '門店營收',
			self::BF_MONTHLY_FILLING	=> '月初報表',
			
			#御廚
			self::BG_NEW_RELEASE 		=> '新品銷售',
			self::BG_SHIPMENTS 			=> '出貨總量查詢',
			self::BG_PURCHASE 			=> '出貨統計',
			self::BG_SALES 				=> '銷售統計',
			self::BG_DAILY_REVENUE		=> '門店營收',
			
			#芳珍
			self::FJ_DAILY_REVENUE		=> '門店營收',
        };
    }
	
	#key-value array
	public static function mapWithGroupKeys(): array
	{
		$list = [];
		foreach(self::cases() as $case)
		{
			if ($case->value > 0)
			{
				$prefix = '';
				
				if (Str::startsWith($case->value, 'bafang'))
					$prefix = '八方：';
				else if (Str::startsWith($case->value, 'buygood'))
					$prefix = '御廚：';
				else if (Str::startsWith($case->value, 'fjVeggie'))
					$prefix = '芳珍：';
					
				$list[$case->value] = Str::start($case->label(), $prefix);
			}
		}
		
		return $list;
	}
}
