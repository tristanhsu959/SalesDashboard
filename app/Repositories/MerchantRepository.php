<?php

namespace App\Repositories;

use App\Repositories\Traits\PosTrait;
use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Facades\DB;
use Exception;


class MerchantRepository extends Repository
{
	use PosTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取新品營收資料
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getSaleData($brand, $stDate, $endDate)
	{
		$configCode = $brand->code();
		$excepts = config("web.sales.shop.except.{$configCode}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else
			$db = $this->connectBGPosErp();
		
		$query = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->select('a.shopId')
				->selectRaw('CAST(a.saleDate AS DATE) as saleDate')
				->selectRaw('sum(a.price * a.qty + a.discount) as amount')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<=', $endDate)
				->whereNotIn('a.shopId', $excepts)
				->groupByRaw('a.shopId, CAST(a.saleDate AS DATE)')
				->get()
				->toArray();
		
		return $query;
	}
	
	/* 取營收資料 SALE00
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getSale00Data($brand, $stDate, $endDate, $shopType, $userAreaIds)
	{
		$configCode = $brand->code();
		$excepts = config("web.sales.shop.except.{$configCode}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else if ($brand == Brand::FJVEGGIE)
			$db = $this->connectFJPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		
		$query = $db
				->table('SALE00 as a')
				->fromRaw('SALE00 as a WITH(NOLOCK)')
				->join('SHOP00 as b', 'b.SHOP_ID', '=', 'a.SHOP_ID')
				->join('shop_kind as c', 'c.sk_id', '=', 'b.shop_kind')
				->select('a.SHOP_ID as shopId', 'b.SHOP_NAME as shopName', 'b.gid as areaId')
				->addSelect('c.sk_id as typeId', 'c.Sk_name as typeName')
				->selectRaw('CAST(a.SALE_DATE AS DATE) as saleDate, sum(a.TOT_SALES) as amount')
				->where('a.SALE_DATE', '>=', $stDate)
				->where('a.SALE_DATE', '<=', $endDate)
				->whereNotIn('a.SHOP_ID', $excepts)
				->whereIn('b.SHOP_KIND', $shopType)
				->whereIn('b.gid', $authAreaIds)
				->groupByRaw('a.SHOP_ID, b.SHOP_NAME, b.gid, c.sk_id, c.Sk_name, CAST(a.SALE_DATE AS DATE)')
				->get()
				->toArray();
		
		return $query;
	}
}
