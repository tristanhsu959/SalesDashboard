<?php

namespace App\Repositories;

use Exception;

class PurchaseRepository extends Repository
{
	#MSSQL
	public function __construct()
	{
		
	}
	
	/* 取屯山資料-BuyGood
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getDataFromTS($startDateTime, $endDateTime)
	{
		$db = $this->connectOrderTS('OrderList as a');
		$result = $this->_getPurchaseResult($db, $startDateTime, $endDateTime);
		
		return $result;
	}
	
	/* 取二崙資料-BuyGood
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getDataFromRL($startDateTime, $endDateTime)
	{
		$db = $this->connectOrderRL('OrderList as a');
		$result = $this->_getPurchaseResult($db, $startDateTime, $endDateTime);
		
		return $result;
	}
	
	/* Build query string | 八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getPurchaseResult($db, $startDateTime, $endDateTime)
	{
		$exceptShopIds = ['10006000', '999111', '999999'];
		
		$query = $db
				->select('a.OrderDate', 'a.AccNo', 'a.AccName', 'b.ProductNo', 'b.ProductName', 'b.Unit', 'b.Amount', 'b.Money' )
				->join('OrderItem as b', function($join) {
					$join->on('a.OrderNo', '=', 'b.OrderNo');
				})
				->whereNotIn('a.AccNo', $exceptShopIds)
				->where('a.OrderDate', '>=', $startDateTime)
				->where('a.OrderDate', '<=', $endDateTime);
				
		return $query->get()->toArray();
	}
}
