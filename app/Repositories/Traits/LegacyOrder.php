<?php

namespace App\Repositories\Traits;

use Illuminate\Support\Facades\DB;

#舊訂貨系統
class LegacyOrderTrait
{
	#MSSQL
	public function __construct()
	{
		
	}
	
	/* 取TP資料-僅追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getTpExtraDataByCode($stDate, $endDate, $codes)
	{
		$db = $this->connectOrderTP();
		$result = $this->_getPurchaseResult($db, $stDate, $endDate, $codes);
		
		return $result;
	}
	
	/* 取KH資料-僅追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getKhExtraDataByCode($startDateTime, $endDateTime)
	{
		$db = $this->connectOrderKH();
		$result = $this->_getPurchaseResult($db, $startDateTime, $endDateTime);
		
		return $result;
	}
	
	/* Build query string | 八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getPurchaseResult($db, $startDateTime, $endDateTime, $codes)
	{
		#shipment:	getOrderDataByProductId($brand, $stDate, $endDate, $productIds, $userAreaIds)
		#montly:	getOrderDataByStore($brand, $stDate, $endDate, $productIds, $userAreaIds)
		$exceptShopIds = ['10006000', '999111', '999999', '10007000','1000','1100000'];
		
		$first = DB::table('users')
            ->whereNull('first_name');

// 第二個查詢並合併
$users = DB::table('users')
            ->whereNull('last_name')
            ->union($first)
            ->get();
			
		$query = $db
				->table($table)
				->select('a.ProductNo', 'b.Amount', 'b.Money' )
				->join('OrderItem as b', function($join) {
					$join->on('a.OrderNo', '=', 'b.OrderNo');
				})
				->whereNotIn('a.AccNo', $exceptShopIds)
				->where('a.orderdate', '>=', $startDateTime)
				->where('a.orderdate', '<=', $endDateTime);
				->whereIn('a.ProductNo', $codes)
				
		return $query->get()->toArray();
	}
}
