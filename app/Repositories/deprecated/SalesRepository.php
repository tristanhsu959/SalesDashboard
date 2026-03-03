<?php

namespace App\Repositories;

use Exception;
use Log;

class SalesRepository extends Repository
{
	#MSSQL
	public function __construct()
	{
		
	}
	
	/* 取主資料-BuyGood
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function getBgSaleData($startDateTime, $endDateTime)
	{
		$db = $this->connectBGPosErp('SALE01 as a');
		$result = $this->_getSaleResult($db, $startDateTime, $endDateTime);
		
		return $result;
	}
	
	/* 取Mapping資料 | 複合店情境 - BaFang =>如何知道所有有在複合店的料號??
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	public function getBfSaleData($startDateTime, $endDateTime, $productIds, $valueAdded, $shopIds)
	{
		$db = $this->connectBFPosErp('SALE01 as a');
		$result = $this->_getSaleResult($db, $startDateTime, $endDateTime, $productIds, $valueAdded, $shopIds);
		
		return $result;
	}
	
	/* Build query string | 新品:八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	private function _getSaleResult($db, $startDateTime, $endDateTime, $productIds = NULL, $valueAdded = NULL, $shopIds = NULL)
	{
		$exceptShopIds = ['000030'];
		
		$query = $db
				->select('a.SHOP_ID', 'a.PROD_ID', 'a.QTY', 'a.SALE_PRICE', 'a.ITEM_DISC', 'a.TASTE_MEMO', 'b.SALE_DATE', 'c.SHOP_NAME', 'd.PROD_NAME1')
				->join('SALE00 as b', function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				->join('SHOP00 as c', 'a.SHOP_ID', '=', 'c.SHOP_ID')
				->join('PRODUCT00 as d', 'd.PROD_ID', '=', 'a.PROD_ID')
				->where('b.SALE_DATE', '>=', "{$startDateTime} 00:00:00")
				->where('b.SALE_DATE', '<=', "{$endDateTime} 23:59:59")
				->where('a.SALE_PRICE', '>', 0)
				->whereNotIn('c.SHOP_ID', $exceptShopIds);
				/*->orderBy('b.SALE_DATE', 'DESC')
				->orderBy('a.SHOP_ID');*/
		
		if (! is_null($shopIds))
			$query->whereIn('a.SHOP_ID', $shopIds);
	
		Log::channel('appServiceLog')->info($query->toRawSql(), [ __class__, __function__, __line__]);
		
		return $query->get();
	}
}
