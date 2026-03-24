<?php

namespace App\Repositories;

use App\Repositories\Traits\OrderTrait;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Facades\DB;
use Exception;


class ShipmentsRepository extends Repository
{
	use OrderTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取產品設定
	 * @params: int
	 * @return: array
	 */
	public function getProductWithType($brandId)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Product as a')
			->join('ProductType as b', 'b.Id', '=', 'a.ProductTypeId')
			->join('Stocks as c', 'c.ProductId', '=', 'a.Id')
			->select('a.OldNo as productNo', 'a.Name as productName', 'b.No as catNo', 'b.Name as catName')
			->where('a.OldNo', '!=', '')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as a1')
					->whereColumn('a1.Id', 'a.OperationCenterId')
					->whereIn('a1.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as c1')
					->whereColumn('c1.Id', 'c.BrandId')
					->where('c1.No',  $this->getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as c2')
					->whereColumn('c2.Id', 'c.FactoryId')
					->whereIn('c2.No',  $this->getFactoryNo($brandId));
			})
			->whereNotIn('b.No', config("web.shipments.productType.{$brandId}.except"))
			->groupBy('a.OldNo', 'a.Name', 'b.No', 'b.Name')
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取新品設定相關條件(暫保留)
	 * @params: int
	 * @return: array
	 */
	public function getNameById($id)
	{
		$db = $this->connectSalesDashboard('new_release_setting');
		$result = $db
			->select('releaseName')
			->where('releaseId', '=', $id)
			->get()
			->first();
		
		return $result['releaseName'];
	}
	
	/* 取新品設定相關條件(暫保留)
	 * @params: int
	 * @return: array
	 */
	public function getTasteById($id)
	{
		$db = $this->connectSalesDashboard('new_release_setting');
		$result = $db
			->select('releaseTaste')
			->where('releaseId', '=', $id)
			->get()
			->first();
		
		return json_decode($result['releaseTaste'], TRUE);
	}
	
	public function getSettingById($id)
	{
		$db = $this->connectSalesDashboard('new_release_setting');
		$result = $db
			->select('releaseName', 'releaseTaste')
			->where('releaseId', '=', $id)
			->where('releaseStatus', '=', TRUE)
			->get()
			->first();
		
		if (empty($result))
			return FALSE;
		
		$result['releaseTaste'] = json_decode($result['releaseTaste'], TRUE);
		
		return $result;
	}
	
	/* 取新品設定相關條件
	 * @params: int
	 * @return: array
	 */
	public function getErpNoById($id)
	{
		$db = $this->connectSalesDashboard();
		$result = $db
			->table('new_release_product as a')
			->select('b.erpNo', 'b.isPrimary')
			->join('product_no as b', 'b.parentId', '=', 'a.productId')
			->where('a.parentId', '=', $id)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取主資料 By records 
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes, $userAreaIds)
	{
		$primaryData 		= $this->_getPrimaryData($brand, $stDate, $endDate, $primaryIds, $tastes, $userAreaIds);
		$dualBrandedData	= $this->_getDualBrandedData($brand, $stDate, $endDate, $secondaryIds, $tastes, $userAreaIds);
		
		#合併查詢(gid在八方及御廚定義不同, 這裏不處理)
		$result = $primaryData->merge($dualBrandedData)->toArray();
		
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
	private function _getPrimaryData($brand, $stDate, $endDate, $erpNos, $tastes, $userAreaIds)
	{
		/* $erpNos = collect($erpNos)
			->map(fn($no) => "N'{$no}'")
			->implode(','); */
			
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
		
		$query = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join('SHOP00 as c', 'c.SHOP_ID', '=', 'a.shopId')
				#->select('a.shopId', 'a.qty')
				#->selectRaw('CAST(a.saleDate AS DATE) as saleDate')
				->select('a.shopId')
				->selectRaw('CAST(a.saleDate AS DATE) as saleDate, sum(a.qty) as qty')
				
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<=', $endDate)
				->whereIn('c.gid', $authAreaIds)
				->whereNotIn('a.shopId', $excepts)
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereIn('a.productId', $erpNos)
						->when(! empty($tastes), function ($db) use ($tastes) {
							$db->orWhereAny(['a.taste'], 'like', $tastes);
						});
				})
				->groupByRaw('a.shopId, CAST(a.saleDate AS DATE)')
				->get();
		
		return $query;
	}
	
	/* Build query string | 只有御廚才有複合店才有的情境, 需去八方取御廚的資料
	 * @params: connection
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getDualBrandedData($brand, $stDate, $endDate, $erpNos, $tastes, $userAreaIds)
	{
		if ($brand == Brand::BAFANG)
			return FALSE;
		
		$db = $this->connectBFPosErp();
		$authAreaIds = Area::toBafangId($userAreaIds);
		$dualBrandedShopIds = config('web.shop.dualBrandedId');
				
		/* $caseShopId = "CASE ";
		foreach ($dualBrandedShopIds as $bfId => $bgId) 
		{
			$caseShopId .= "WHEN a.SHOP_ID = '{$bfId}' THEN '{$bgId}' ";
		}
		$caseShopIdcaseShopId .= "ELSE a.SHOP_ID END as shopId"; */
		
		$query = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join('SHOP00 as c', 'c.SHOP_ID', '=', 'a.shopId')
				#->select('a.shopId', 'a.qty')
				#->selectRaw('CAST(a.saleDate AS DATE) as saleDate')
				->select('a.shopId')
				->selectRaw('CAST(a.saleDate AS DATE) as saleDate, sum(a.qty) as qty')
				
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<=', $endDate)
				->whereIn('c.gid', $authAreaIds)
				->whereIn('a.shopId', array_keys($dualBrandedShopIds))
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereIn('a.productId', $erpNos)
						->when(! empty($tastes), function ($db) use ($tastes) {
							$db->orWhereAny(['a.taste'], 'like', $tastes);
						});
				})
				->groupByRaw('a.shopId, CAST(a.saleDate AS DATE)')
				->get();
		
		#轉換shop id
		$query = $query->map(function($item, $key) use($dualBrandedShopIds) {
			$item['shopId'] = $dualBrandedShopIds[$item['shopId']];
			return $item;
		});
		
		return $query;
	}
	
	/* Deprecated */
	/* 取主資料
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 *
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
	 *
	private function _getPrimaryQuery($db, $stDate, $endDate, $erpNos, $tastes)
	{
		/* $erpNos = collect($erpNos)
			->map(fn($no) => "N'{$no}'")
			->implode(','); *
		
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
	 *
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
	*/
	
}
