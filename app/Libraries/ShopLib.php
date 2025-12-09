<?php

namespace App\Libraries;

use Illuminate\Support\Str;
use App\Enums\Area;

class ShopLib
{
    public static function getAreaByShopId($shopId)
    {
		if (intval($shopId) >= 100000 and intval($shopId) <= 259999)
			return Area::TAIPEI->label(); #'大台北區'
		else if (intval($shopId) >= 260000 and intval($shopId) <= 270999)
			return Area::YILAN->label(); #'宜蘭區'
		else if (Str::startsWith($shopId, ['3']))
			return Area::TCM->label(); #'桃竹苗區'
		else if (Str::startsWith($shopId, ['4', '5']))
			return Area::CCT->label(); #'中彰投區'
		else if (Str::startsWith($shopId, ['6', '7']))
			return Area::YCN->label(); #'雲嘉南區'
		else if (Str::startsWith($shopId, ['8', '9']))
			return Area::KAOHSIUNG->label(); #'大高雄區'
		else
			return 'UNKNOW';
    }
}