<?php

namespace App\Repositories;

use App\Enums\Brand;
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
	public function getNewItemOptions($brand)
	{
		$db = $this->connectSalesDashboard('new_item');
		$result = $db
			->select('newItemId as id', 'newItemName as name', 'newItemSaleDate as saleDate')
			->where('newItemBrand', '=', $brand)
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
	
	/* 取主資料-BuyGood
	 * @params: datetime
	 * @params: datetime
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
				$primaryQuery->when($dualBrandedQuery, function ($query, $dual) {
					return $query->unionAll($dual);
				}), 
				'a'
			)
			->join('SHOP00 b WITH(NOLOCK)', 'b.SHOP_ID', '=', 'a.shopId')
			->select('a.shopId', 'a.saleDate')
			->selectRaw('SUM(a.qty) as totalQty')
			->groupBy('a.shopId', 'a.saleDate')
			->orderBy('a.saleDate', 'DESC')
			->orderBy('a.shopId')
			->get();
	
		return $result;
	}
	
	/* Build query string | 八方,御廚
	 * @params: connection
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getPrimaryQuery($db, $stDate, $endDate, $erpNos, $tastes)
	{
		#只回傳query builder
		$query = $db
				->table('SALE00 as a')
				->fromRaw('SALE00 as a WITH(NOLOCK)')
				->join('SALE01 as b WITH(NOLOCK)', function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				
				->select('b.SHOP_ID as shopId', 'b.QTY as qty')
				->selectRaw('CAST(a.SALE_DATE AS DATE) as saleDate')
				->whereBetween('a.SALE_DATE', [$stDate, $endDate])
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereIn('b.PROD_ID', $erpNos);
					
					$db->when($tastes, function ($q) use ($tastes) {
						$tasteKeywords = array_map(fn($t) => "%{$t}%", $tastes);
						$q->orWhereAny(['b.TASTE_MEMO'], 'like', $tasteKeywords);
					});
				});
		
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
	private function _getDualBrandedQuery($stDate, $endDate, $erpNos, $tastes, $dualBrandedShopIds)
	{
		if (empty($dualBrandedShopIds))
			return NULL;
		
		$caseShopId = "CASE ";
		foreach ($dualBrandedShopIds as $bfId => $bgId) 
		{
			$caseShopId .= "WHEN a.SHOP_ID = '{$bfId}' THEN '{$bgId}' ";
		}
		$caseShopId .= "ELSE a.SHOP_ID END as shopId";

		$db = $this->connectBFPosErp();
		#只回傳query builder
		$query = $db
				->table('SALE00 as a')
				->fromRaw('SALE00 as a WITH(NOLOCK)')
				->join('SALE01 as b WITH(NOLOCK)', function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				
				->selectRaw($caseShopId)
				->select('b.QTY as qty')
				->selectRaw('CAST(a.SALE_DATE AS DATE) as saleDate')
				->whereBetween('a.SALE_DATE', [$stDate, $endDate])
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereIn('b.PROD_ID', $erpNos);
					
					$db->when($tastes, function ($q) use ($tastes) {
						$tasteKeywords = array_map(fn($t) => "%{$t}%", $tastes);
						$q->orWhereAny(['b.TASTE_MEMO'], 'like', $tasteKeywords);
					});
				})
				->whereIn('a.SHOP_ID', array_keys($dualBrandedShopIds));
		
		return $query;
	}
}
