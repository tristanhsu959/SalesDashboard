<?php

namespace App\Libraries;

use Illuminate\Support\Str;

class ShopLib
{
    public static function getAreaByShopId($shopId)
    {
		if (intval($shopId) >= 100000 and intval($shopId) <= 259999)
			return '大台北區';
		else if (intval($shopId) >= 260000 and intval($shopId) <= 270999)
			return '宜蘭區';
		else if (Str::startsWith($shopId, ['3']))
			return '桃竹苗區';
		else if (Str::startsWith($shopId, ['4', '5']))
			return '中彰投區';
		else if (Str::startsWith($shopId, ['6', '7']))
			return '雲嘉南區';
		else if (Str::startsWith($shopId, ['8', '9']))
			return '大高雄區';
		else
			return 'UNKNOW';
    }
}