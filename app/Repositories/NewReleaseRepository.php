<?php

namespace App\Repositories;

use App\Enums\Brand;
use App\Enums\Area;
use Illuminate\Support\Facades\DB;
use Exception;


class NewReleaseRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* 取啟用的新品設定
	 * @params: int
	 * @return: array
	 */
	public function getNewItemOptions($brandId)
	{
		$db = $this->connectSalesDashboard('new_item');
		$result = $db
			->select('newItemId as id', 'newItemName as name', 'newItemSaleDate as saleDate')
			->where('newItemBrand', '=', $brandId)
			->where('newItemStatus', '=', TRUE)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取新品設定相關條件
	 * @params: int
	 * @return: array
	 */
	public function getTasteById($id)
	{
		$db = $this->connectSalesDashboard('new_item');
		$result = $db
			->select('newItemTaste')
			->where('newItemId', '=', $id)
			->get()
			->first();
		
		return json_decode($result['newItemTaste'], TRUE);
	}
	
	/* 取新品設定相關條件
	 * @params: int
	 * @return: array
	 */
	public function getErpNoById($id)
	{
		$db = $this->connectSalesDashboard('new_item');
		$result = $db
			->select('erpNo', 'isPrimary')
			->join('product_no', 'parentId', '=', 'newItemProductId')
			->where('newItemId', '=', $id)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取門店資料
	 * @params: enums
	 * @params: array
	 * @return: array
	 */
	public function getShopList($brand, $userAreaIds)
	{
		$configCode = $brand->code();
		$excepts = config("web.shop.except.{$configCode}");
		
		if ($brand == Brand::BAFANG)
		{
			$db = $this->connectBFPosErp();
			$authAreaIds = Area::toBafangId($userAreaIds);
		}
		else
		{
			$db = $this->connectBGPosErp();
			$authAreaIds = Area::toBuygoodId($userAreaIds);
		}
			
		$result = $db->table('hptrans_shop as a')
			->join('SHOP00 as b', 'b.SHOP_ID', '=', 'a.hptrs_shop')
			->select('b.SHOP_ID as shopId', 'b.SHOP_NAME as shopName', 'b.gid as areaId')
			->where('b.closedown', '=', 0)
			->whereIn('b.gid', $authAreaIds)
			->whereNotIn('b.SHOP_ID', $excepts)
			->orderBy('b.SHOP_ID')
			->get()->toArray();
	
		return $result;
	}
	
	/* 取主資料
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes)
	{
		if ($brand == Brand::BAFANG)
		{
			$db = $this->connectBFPosErp();
			$dualBrandedShopIds = [];
		}
		else
		{
			#御廚才有複合店情境
			$db = $this->connectBGPosErp();
			$dualBrandedShopIds = config('web.shop.dualBrandedId');
		}
			
		$primaryQuery 		= $this->_getPrimaryQuery($db, $stDate, $endDate, $primaryIds, $tastes);
		$dualBrandedQuery 	= $this->_getDualBrandedQuery($stDate, $endDate, $secondaryIds, $tastes, $dualBrandedShopIds);
		
		#合併查詢(gid在八方及御廚定義不同)
		$result = $db->query() #建立一個乾淨的底層查詢
			->fromSub(
				$primaryQuery->when($dualBrandedQuery, function ($db, $dual) {
					return $db->unionAll($dual);
				}), 'a'
			)
			->join('SHOP00 as b', 'b.SHOP_ID', '=', 'a.shopId')
			->select('a.shopId', 'a.saleDate')
			->selectRaw('SUM(a.qty) as totalQty')
			->groupBy('a.shopId', 'a.saleDate')
			->orderBy('a.shopId')
			->get()->toArray();
	
		return $result;
	}
	
	/* Build query string | 八方,御廚
	 * @params: connection
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @return: query builder
	 */
	private function _getPrimaryQuery($db, $stDate, $endDate, $erpNos, $tastes)
	{
		/* $erpNos = collect($erpNos)
			->map(fn($no) => "N'{$no}'")
			->implode(','); */
		
		#只回傳query builder
		$query = $db
				->table('SALE01 as a')
				->fromRaw('SALE01 as a WITH(NOLOCK)')
				->join('SALE00 as b', function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				->select('a.SHOP_ID as shopId', 'a.QTY as qty')
				->selectRaw('CAST(b.SALE_DATE AS DATE) as saleDate')
				
				->where('b.SALE_DATE', '>=', $stDate)
				->where('b.SALE_DATE', '<=', $endDate)
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereExists(function ($db) use ($erpNos) {
						$db->select(DB::raw(1))
							->fromRaw('PRODUCT00 as p')
							->whereColumn('p.PROD_ID', 'a.PROD_ID')
							->whereIn('p.PROD_ID', $erpNos);
					})
					->when(! empty($tastes), function ($db) use ($tastes) {
						$tasteKeywords = array_map(fn($t) => "%{$t}%", $tastes);
						$db->orWhereAny(['a.TASTE_MEMO'], 'like', $tasteKeywords);
					});
				});
		
		return $query;
	}
	
	/* Build query string | 八方:只有複合店才有的情境
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: query builder
	 */
	private function _getDualBrandedQuery($stDate, $endDate, $erpNos, $tastes, $dualBrandedShopIds)
	{
		if (empty($dualBrandedShopIds))
			return FALSE;
		
		$caseShopId = "CASE ";
		foreach ($dualBrandedShopIds as $bfId => $bgId) 
		{
			$caseShopId .= "WHEN a.SHOP_ID = '{$bfId}' THEN '{$bgId}' ";
		}
		$caseShopId .= "ELSE a.SHOP_ID END as shopId";

		$db = $this->connectBFPosErp();
		
		$query = $db
				->table('SALE01 as a')
				->fromRaw('SALE01 as a WITH(NOLOCK)')
				->join('SALE00 as b WITH(NOLOCK)', function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				->selectRaw($caseShopId)
				->select('a.QTY as qty')
				->selectRaw('CAST(a.SALE_DATE AS DATE) as saleDate')
				->where('b.SALE_DATE', '>=', $stDate)
				->where('b.SALE_DATE', '<=', $endDate)
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereExists(function ($subQuery) use ($erpNos) {
						$subQuery->select(DB::raw(1))
							->from('PRODUCT00 as p')
							->whereColumn('p.PROD_ID', 'a.PROD_ID')
							->whereIn('p.PROD_ID', $erpNos);
					})
					->when($tastes, function ($q) use ($tastes) {
						$tasteKeywords = array_map(fn($t) => "%{$t}%", $tastes);
						$q->orWhereAny(['a.TASTE_MEMO'], 'like', $tasteKeywords);
					});
				})
				->whereIn('a.SHOP_ID', array_keys($dualBrandedShopIds));
		
		return $query;
	}
	
	/* 取主資料 By records (不計算反而比較快?)
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getSaleRecords($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes, $userAreaIds)
	{
		$primaryQuery 		= $this->_getPrimaryRecords($brand, $stDate, $endDate, $primaryIds, $tastes, $userAreaIds);
		$dualBrandedQuery 	= $this->_getDualBrandedRecords($brand, $stDate, $endDate, $secondaryIds, $tastes, $userAreaIds);
		
		#合併查詢(gid在八方及御廚定義不同, 這裏不處理)
		$result = $primaryQuery->merge($dualBrandedQuery)->toArray();
	
		return $result;
	}
	
	/* Build query string | 八方,御廚
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getPrimaryRecords($brand, $stDate, $endDate, $erpNos, $tastes, $userAreaIds)
	{
		/* $erpNos = collect($erpNos)
			->map(fn($no) => "N'{$no}'")
			->implode(','); */
		
		if ($brand == Brand::BAFANG)
		{
			$db = $this->connectBFPosErp();
			$authAreaIds = Area::toBafangId($userAreaIds);
		}
		else
		{
			$db = $this->connectBGPosErp();
			$authAreaIds = Area::toBuygoodId($userAreaIds);
		}
		
		#只回傳query builder
		$query = $db
				->table('z_sd_order as a')
				->fromRaw('z_sd_order as a WITH(NOLOCK)')
				->join('SHOP00 as c', 'c.SHOP_ID', '=', 'a.shopId')
				->select('a.shopId', 'a.qty')
				->selectRaw('CAST(a.saleDate AS DATE) as saleDate')
				
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<=', $endDate)
				->whereIn('c.gid', $authAreaIds)
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereIn('a.productId', $erpNos)
						->when(! empty($tastes), function ($db) use ($tastes) {
							$db->orWhere('a.taste', '=', 1);
						});
				})->get();
		
		/*
		$query = $db
				->table('SALE01 as a')
				->fromRaw('SALE01 as a WITH(NOLOCK)')
				->join('SALE00 as b', function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				->join('SHOP00 as c', 'c.SHOP_ID', '=', 'a.SHOP_ID')
				->select('a.SHOP_ID as shopId', 'a.QTY as qty')
				->selectRaw('CAST(b.SALE_DATE AS DATE) as saleDate')
				
				->where('b.SALE_DATE', '>=', $stDate)
				->where('b.SALE_DATE', '<=', $endDate)
				->whereIn('c.gid', $authAreaIds)
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereExists(function ($db) use ($erpNos) {
						$db->select(DB::raw(1))
							->fromRaw('PRODUCT00 as p')
							->whereColumn('p.PROD_ID', 'a.PROD_ID')
							->whereIn('p.PROD_ID', $erpNos);
					})
					->when(! empty($tastes), function ($db) use ($tastes) {
						$tasteKeywords = array_map(fn($t) => "%{$t}%", $tastes);
						$db->orWhereAny(['a.TASTE_MEMO'], 'like', $tasteKeywords);
					});
				})->get();
		*/		
		return $query;
	}
	
	/* Build query string | 八方:只有複合店才有的情境
	 * @params: connection
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getDualBrandedRecords($brand, $stDate, $endDate, $erpNos, $tastes, $userAreaIds)
	{
		if ($brand == Brand::BAFANG)
			return FALSE;
		
		
		$db = $this->connectBGPosErp();
		$authAreaIds = Area::toBuygoodId($userAreaIds);
		$dualBrandedShopIds = config('web.shop.dualBrandedId');
				
		$caseShopId = "CASE ";
		foreach ($dualBrandedShopIds as $bfId => $bgId) 
		{
			$caseShopId .= "WHEN a.SHOP_ID = '{$bfId}' THEN '{$bgId}' ";
		}
		$caseShopId .= "ELSE a.SHOP_ID END as shopId";

		$query = $db
				->table('SALE01 as a')
				->fromRaw('SALE01 as a WITH(NOLOCK)')
				->join('SALE00 as b WITH(NOLOCK)', function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				->join('SHOP00 as c', 'c.SHOP_ID', '=', 'a.SHOP_ID')
				->selectRaw($caseShopId)
				->select('a.QTY as qty')
				->selectRaw('CAST(b.SALE_DATE AS DATE) as saleDate')
				->where('b.SALE_DATE', '>=', $stDate)
				->where('b.SALE_DATE', '<=', $endDate)
				->whereIn('a.SHOP_ID', array_keys($dualBrandedShopIds))
				->whereIn('c.gid', $authAreaIds)
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereExists(function ($subQuery) use ($erpNos) {
						$subQuery->select(DB::raw(1))
							->from('PRODUCT00 as p')
							->whereColumn('p.PROD_ID', 'a.PROD_ID')
							->whereIn('p.PROD_ID', $erpNos);
					})
					->when($tastes, function ($q) use ($tastes) {
						$tasteKeywords = array_map(fn($t) => "%{$t}%", $tastes);
						$q->orWhereAny(['a.TASTE_MEMO'], 'like', $tasteKeywords);
					});
				})->get();
		
		return $query;
	}
}
