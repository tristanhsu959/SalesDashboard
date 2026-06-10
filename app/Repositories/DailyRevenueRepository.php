<?php

namespace App\Repositories;

use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;


class DailyRevenueRepository extends Repository
{
	public function __construct()
	{
		
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
		
		$isToday = Carbon::parse($stDate)->isToday() && Carbon::parse($endDate)->isToday();
		
		#芳珍沒有sd_sale00
		if ($isToday && $brand != Brand::FJVEGGIE)
			return $this->getFromSdSale00($db, $authAreaIds, $stDate, $endDate, $shopType, $shopName, $excepts);
		else
			return $this->getFromSale00($db, $authAreaIds, $stDate, $endDate, $shopType, $shopName, $excepts);
	}
	
	/* 取營收資料By today only
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getFromSdSale00($db, $authAreaIds, $stDate, $endDate, $shopType, $shopName, $excepts)
	{
		$result = $db
				->table(DB::raw('z_sd_sale00 as a WITH(NOLOCK)'))
				->join(DB::raw('SHOP00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.shopId')
				->join(DB::raw('shop_kind as c WITH(NOLOCK)'), 'c.sk_id', '=', 'b.SHOP_KIND')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<', $endDate)
				->whereIn('b.SHOP_KIND', $shopType)
				->whereIn('b.gid', $authAreaIds)
				->when(! empty($shopName), function ($query) use ($shopName) {
					$query->whereAny(['b.SHOP_NAME'], 'like', "%{$shopName}%");
				})
				->whereNotIn('a.shopId', $excepts)
				->select('a.shopId', 'b.SHOP_NAME as shopName', 'c.Sk_name as typeName', 'b.gid as areaId', 'a.saleDate')
				->selectRaw('sum(a.amount) as amount')
				->selectRaw('sum(a.totalSales) as totalSales')
				->selectRaw('sum(a.totalExtra) as totalExtra')
				->selectRaw('sum(a.totalDischarge) as totalDischarge')
				->groupBy('a.shopId', 'b.SHOP_NAME', 'c.Sk_name', 'b.gid', 'a.saleDate')
				->get()
				->toArray(); 
		
		return $result;
	}
	
	/* 取營收資料 By all time range
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getFromSale00($db, $authAreaIds, $stDate, $endDate, $shopType, $shopName, $excepts)
	{
		#Group會變超慢, 改為由PHP計算
		$subQuery = $db
				->table(DB::raw('SALE00 as a WITH(NOLOCK)'))
				->where('a.SALE_DATE', '>=', $stDate)
				->where('a.SALE_DATE', '<', $endDate)
				->where('a.STATUS', '=', 2) #3:作廢不計入
				->select('a.SALE_ID', 'a.SHOP_ID');
					
		$result = $db
				->table(DB::raw('SALE00 as a WITH(NOLOCK)'))
				->joinSub($subQuery, 'orders', function($join){
					$join->on('orders.SALE_ID', '=', 'a.SALE_ID')
						->on('orders.SHOP_ID', '=', 'a.SHOP_ID');
				})
				->join(DB::raw('SHOP00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.SHOP_ID')
				->join(DB::raw('shop_kind as c WITH(NOLOCK)'), 'c.sk_id', '=', 'b.SHOP_KIND')
				->whereIn('b.gid', $authAreaIds)
				->whereIn('b.SHOP_KIND', $shopType)
				->when(! empty($shopName), function ($query) use ($shopName) {
					$query->WhereAny(['b.SHOP_NAME'], 'like', "%{$shopName}%");
				})
				->whereNotIn('a.SHOP_ID', $excepts)
				->select('a.SHOP_ID as shopId', 'b.SHOP_NAME as shopName', 'c.Sk_name as typeName', 'b.gid as areaId')
				->selectRaw('CAST(a.SALE_DATE AS DATE) as saleDate')
				->selectRaw('sum(a.amount) as amount')
				->selectRaw('sum(a.TOT_SALES) as totalSales')
				->selectRaw('sum(a.TOT_EXTRA) as totalExtra')
				->selectRaw('sum(a.TOT_DISCHARGE) as totalDischarge')
				->groupBy('a.SHOP_ID', 'b.SHOP_NAME', 'c.Sk_name', 'b.gid', DB::raw('CAST(a.SALE_DATE AS DATE)'))
				->get()
				->toArray();
		
		return $result; 
	}
	
	
	/* 取營收客單統計資料By Month
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getDataByAverageOrderValue($brand, $userAreaIds, $stDate, $endDate, $shopType)
	{
		$configCode = $brand->code();
		$excepts = config("web.sales.shop.except.{$configCode}");
		
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		
		$result = $db
				->table(DB::raw('SHOP00 as a WITH(NOLOCK)'))
				->join(DB::raw('SALE00 as b WITH(NOLOCK)'), 'b.SHOP_ID', '=', 'a.SHOP_ID')
				#->join(DB::raw('shop_kind as c WITH(NOLOCK)'), 'c.sk_id', '=', 'a.SHOP_KIND')
				->whereIn('a.gid', $authAreaIds)
				->whereIn('a.SHOP_KIND', $shopType)
				->whereNotIn('a.SHOP_ID', $excepts)
				->where('b.STATUS', '=', 2) #3:作廢不計入
				->where('b.SALE_DATE', '>=', $stDate)
				->where('b.SALE_DATE', '<', $endDate)
				#->select('a.SHOP_ID as shopId', 'c.Sk_name as typeName', 'a.gid as areaId')
				->select('a.SHOP_ID as shopId', 'a.SHOP_KIND as shopKind', 'a.gid as areaId')
				->selectRaw('DATEADD(month, DATEDIFF(month, 0, b.SALE_DATE), 0) as saleDate')
				->selectRaw('count(a.SHOP_ID) as visitors')
				->selectRaw('sum(b.amount) as amount')
				->selectRaw('sum(b.TOT_SALES) as totalSales')
				->selectRaw('sum(b.TOT_EXTRA) as totalExtra')
				->selectRaw('sum(b.TOT_DISCHARGE) as totalDischarge')
				->groupBy('a.SHOP_ID', 'a.SHOP_KIND', 'a.gid', DB::raw('DATEADD(month, DATEDIFF(month, 0, b.SALE_DATE), 0)'))#->ddRawSql();
				->get()
				->toArray();
		
		return $result; 
	}
	
}
