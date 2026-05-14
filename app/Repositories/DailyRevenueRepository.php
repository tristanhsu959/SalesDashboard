<?php

namespace App\Repositories;

use App\Repositories\Traits\PosRepositoryTrait;
use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Facades\DB;
use Exception;


class DailyRevenueRepository extends Repository
{
	use PosRepositoryTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取新品營收資料(From zs_sd_order)
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
	
	/* 取營收資料 SALE00/有sum處理過
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getSale00Data($brand, $userAreaIds, $stDate, $endDate, $shopType, $shopName)
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
		
		#Group會變超慢, 改為由PHP計算
		$result = $db
				->table('SALE00 as a')
				->fromRaw('SALE00 as a WITH(NOLOCK)')
				->join(DB::raw('SHOP00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.SHOP_ID')
				->join(DB::raw('shop_kind as c WITH(NOLOCK)'), 'c.sk_id', '=', 'b.shop_kind')
				->where('a.SALE_DATE', '>=', $stDate)
				->where('a.SALE_DATE', '<=', $endDate)
				->whereNotIn('a.SHOP_ID', $excepts)
				->where('a.STATUS', '=', 2) #3:作廢不計入
				->whereIn('b.SHOP_KIND', $shopType)
				->whereIn('b.gid', $authAreaIds)
				->when($authAreaIds, function ($query, $authAreaIds) {
					return $query->whereIn('b.gid', $authAreaIds);
				})
				->when(! empty($shopName), function ($query) use ($shopName) {
					$query->WhereAny(['b.SHOP_NAME'], 'like', "%{$shopName}%");
				})
				->select('a.SHOP_ID as shopId', 'b.SHOP_NAME as shopName', 'c.Sk_name as typeName', 'b.gid as areaId', 'a.SALE_DATE as saleDate')
				->selectRaw('a.amount')
				->get()
				->toArray();
		
		return $result;
		
		/* $subQuery = $db->table(DB::raw('SHOP00 as shop WITH(NOLOCK)'))
				->join(DB::raw('shop_kind as stype WITH(NOLOCK)'), 'stype.sk_id', '=', 'shop.shop_kind')
				->whereIn('shop.gid', $authAreaIds)
				->whereIn('shop.SHOP_KIND', $shopType)
				->whereNotIn('shop.SHOP_ID', $excepts)
				->when(! empty($shopName), function ($query) use ($shopName) {
					$query->WhereAny(['shop.SHOP_NAME'], 'like', "%{$shopName}%");
				})
				->select('shop.SHOP_ID as shopId', 'shop.SHOP_NAME as shopName', 'shop.gid as areaId')
				->addSelect('shop.SHOP_KIND as typeId', 'stype.Sk_name as typeName');
				
		$result = $db->table('SALE00 as a')
				->fromRaw('SALE00 as a WITH(NOLOCK)')
				->joinSub($subQuery, 'shop', function ($join) {
					$join->on('shop.shopId', '=', 'a.SHOP_ID');
				})
				->select('a.SHOP_ID as shopId')
				->selectRaw('DATEADD(day, DATEDIFF(day, 0, a.SALE_DATE), 0) as saleDate')
				#->selectRaw('SUM(a.amount) as amount')
				->addSelect('a.amount')
				->where('a.SALE_DATE', '>=', $stDate)
				->where('a.SALE_DATE', '<=', $endDate)
				->where('a.STATUS', 2)
				#->groupByRaw('a.SHOP_ID, DATEADD(day, DATEDIFF(day, 0, a.SALE_DATE), 0)')
				->toRawSql();
				#->get()
				#->toArray();
			dd($result); */	
		
		/* $result = $db
				->table('SALE00 as a')
				->fromRaw('SALE00 as a WITH(NOLOCK)')
				->join(DB::raw('SHOP00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.SHOP_ID')
				->join(DB::raw('shop_kind as c WITH(NOLOCK)'), 'c.sk_id', '=', 'b.shop_kind')
				->select('a.SHOP_ID as shopId', 'b.SHOP_NAME as shopName', 'b.gid as areaId')
				->addSelect('c.sk_id as typeId', 'c.Sk_name as typeName')
				->selectRaw('DATEADD(day, DATEDIFF(day, 0, a.SALE_DATE), 0) as saleDate') #yyyy-mm-dd 00:00:00, 用cast會破壞索引
				->selectRaw('sum(a.amount) as amount')
				->where('a.SALE_DATE', '>=', $stDate)
				->where('a.SALE_DATE', '<=', $endDate)
				#->whereNotIn('a.SHOP_ID', $excepts)
				->whereIn('b.SHOP_KIND', $shopType)
				->where('a.STATUS', '=', 2) #3:作廢不計入
				->whereIn('b.gid', $authAreaIds)
				# ->when($authAreaIds, function ($query, $authAreaIds) {
				#	return $query->whereIn('b.gid', $authAreaIds);
				#})
				->when(! empty($shopName), function ($query) use ($shopName) {
					$query->WhereAny(['b.SHOP_NAME'], 'like', "%{$shopName}%");
				})
				->groupByRaw('a.SHOP_ID, b.SHOP_NAME, b.gid, c.sk_id, c.Sk_name, DATEADD(day, DATEDIFF(day, 0, a.SALE_DATE), 0)')
				->get()
				->toArray(); */
		
		
	}
}
