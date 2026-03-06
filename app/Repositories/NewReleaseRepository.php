<?php

namespace App\Repositories;

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
	public function getBgSaleData($startDateTime, $endDateTime, $productIds)
	{
		$db = $this->connectBGPosErp('SALE01 as a');
		$result = $this->_getSaleResult($db, $startDateTime, $endDateTime, $productIds);
		
		return $result;
	}
	
	/* 取Mapping資料 | 複合店情境 - BaFang
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getBfSaleData($startDateTime, $endDateTime, $productIds, $shopIds)
	{
		$db = $this->connectBFPosErp('SALE01 as a');
		$result = $this->_getSaleResult($db, $startDateTime, $endDateTime, $productIds, $shopIds);
		
		return $result;
	}
	
	/* Build query string | 新品:八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getSaleResult($db, $startDateTime, $endDateTime, $productIds, $shopIds = NULL)
	{
		$query = $db
				->select('a.SHOP_ID', 'a.QTY', 'b.SALE_DATE', 'c.SHOP_NAME')
				->join('SALE00 as b', function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				->join('SHOP00 as c', 'a.SHOP_ID', '=', 'c.SHOP_ID')
				->whereIn('a.PROD_ID', $productIds)
				->where('b.SALE_DATE', '>=', $startDateTime)
				->where('b.SALE_DATE', '<=', $endDateTime)
				->orderBy('b.SALE_DATE', 'DESC')
				->orderBy('a.SHOP_ID');
				
		if (! is_null($shopIds))
			$query->whereIn('a.SHOP_ID', $shopIds);
		
		return $query->get();
	}
}
