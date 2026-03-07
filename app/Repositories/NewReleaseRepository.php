<?php

namespace App\Repositories;

use App\Enums\Brand;
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
				'rawData'
			)
			->select('shopId', 'saleDate')
			->selectRaw('SUM(qty) as totalQty')
			->groupBy('shopId', 'saleDate')
			->orderBy('saleDate', 'DESC')
			->orderByRaw('shopId ASC OPTION (FORCE ORDER, RECOMPILE)') 
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
		$erpNos = collect($erpNos)
			->map(fn($no) => "N'{$no}'")
			->implode(',');
	
		#只回傳query builder
		$query = $db
				->table('SALE00 as b')
				->fromRaw('SALE00 as b WITH(NOLOCK, INDEX(IX_SALE00_SALE_DATE))')
				->join(DB::raw('SALE01 as a WITH(NOLOCK)'), function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				
				->select('a.SHOP_ID as shopId', 'a.QTY as qty')
				->selectRaw('CAST(b.SALE_DATE AS DATE) as saleDate')
				->whereBetween('b.SALE_DATE', [$stDate, $endDate])
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereRaw("a.PROD_ID in ({$erpNos})");
					
					$db->when($tastes, function ($q) use ($tastes) {
						$tasteKeywords = array_map(fn($t) => "N'%{$t}%'", $tastes);
						$q->orWhereAny(['a.TASTE_MEMO'], 'like', $tasteKeywords);
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
