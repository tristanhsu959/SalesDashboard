<?php

namespace App\Repositories\Traits;

use App\Enums\Brand;
use App\Enums\Area;
use Illuminate\Support\Facades\DB;

/* POS DB Common */
trait PosTrait
{
	/* 取所有門店資料(有些門店目前可能已Close,故統計資料須抓全部的shop)
	 * @params: enums
	 * @params: array
	 * @return: array
	 */
	public function getShopList($brand, $userAreaIds = [])
	{
		$configCode = $brand->code();
		$excepts = config("web.sales.shop.except.{$configCode}");
		
		if ($brand == Brand::BAFANG)
		{
			$db = $this->connectBFPosErp();
			$authAreaIds = Area::toBafangId($userAreaIds);
		}
		else if ($brand == Brand::BUYGOOD)
		{
			$db = $this->connectBGPosErp();
			$authAreaIds = Area::toBuygoodId($userAreaIds);
		}
		else if ($brand == Brand::FJVEGGIE)
		{
			$db = $this->connectFJPosErp();
			$authAreaIds = Area::toFjVeggieId($userAreaIds);
		}
		else
			return [];
		
		$result = $db->table('SHOP00 as a')
			->join('shop_kind as b', 'b.sk_id', '=', 'a.shop_kind')
			->select('a.SHOP_ID as shopId', 'a.SHOP_NAME as shopName', 'a.gid as areaId', 'a.closedown')
			->addSelect('b.sk_id as typeId', 'b.Sk_name as typeName')
			#->where('a.closedown', '=', 0)
			->when(! empty($authAreaIds), function ($db) use ($authAreaIds) {
				$db->whereIn('a.gid', $authAreaIds);
			})
			->whereNotIn('a.SHOP_ID', $excepts)
			->orderBy('a.SHOP_ID')
			->get()
			->toArray();
	
		return $result;
	}
	
	/* 取Active門店資料(POS有在運作的才會在此Table)
	 * @params: enums
	 * @params: array
	 * @return: array
	 */
	public function getHptransShopList($brand, $userAreaIds)
	{
		$configCode = $brand->code();
		$excepts = config("web.sales.shop.except.{$configCode}");
		
		if ($brand == Brand::BAFANG)
		{
			$db = $this->connectBFPosErp();
			$authAreaIds = Area::toBafangId($userAreaIds);
		}
		else if ($brand == Brand::BUYGOOD)
		{
			$db = $this->connectBGPosErp();
			$authAreaIds = Area::toBuygoodId($userAreaIds);
		}
		else if ($brand == Brand::FJVEGGIE)
		{
			$db = $this->connectFJPosErp();
			$authAreaIds = Area::toFjVeggieId($userAreaIds);
		}
		else
			return [];
			
		$result = $db->table('hptrans_shop as h')
			->join('SHOP00 as a', 'a.SHOP_ID', '=', 'h.hptrs_shop')
			->join('shop_kind as b', 'b.sk_id', '=', 'a.shop_kind')
			->select('a.SHOP_ID as shopId', 'a.SHOP_NAME as shopName', 'a.gid as areaId')
			->addSelect('b.sk_id as typeId', 'b.Sk_name as typeName')
			->where('a.closedown', '=', 0)
			->when(! empty($authAreaIds), function ($db) use ($authAreaIds) {
				$db->whereIn('a.gid', $authAreaIds);
			})
			->whereNotIn('a.SHOP_ID', $excepts)
			->orderBy('a.SHOP_ID')
			->get()
			->toArray();
			
		return $result;
	}
}