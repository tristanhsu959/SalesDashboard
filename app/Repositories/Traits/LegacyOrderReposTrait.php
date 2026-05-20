<?php

namespace App\Repositories\Traits;

use Illuminate\Support\Facades\DB;

#舊訂貨系統
trait LegacyOrderReposTrait
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
	public function getExtraDataFromLegacy($stDate, $endDate, $productNos)
	{
		$result = $this->getTpExtraData($stDate, $endDate, $productNos);
		$result = $this->getKhExtraData($stDate, $endDate, $productNos);
		
		return $result;
	}
	
	/* 取TP資料-僅追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getTpExtraData($stDate, $endDate, $productNos)
	{
		$db = $this->connectOrderTP();
		$result = $this->_getExtraData($db, $stDate, $endDate, $productNos);
		
		return $result;
	}
	
	/* 取KH資料-僅追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getKhExtraData($stDate, $endDate, $productNos)
	{
		$db = $this->connectOrderKH();
		$result = $this->_getExtraData($db, $stDate, $endDate, $productNos);
		
		return $result;
	}
	
	/* Build query string | 八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getExtraData($db, $stDate, $endDate, $productNos)
	{
		$exceptShopIds = ['10006000', '999111', '999999', '10007000','1000','1100000'];
		
		$queryPrimary = $db->table('OrderItem as a')
				->fromRaw('OrderItem as a WITH(NOLOCK)')
				->where('a.OrderDate', '>=', $stDate)
				->where('a.OrderDate', '<=', $endDate)
				->where('a.Ps', '!=', 'OMS') #追加會是空白
				->whereIn('a.ProductNo', $productNos)
				->where('a.Money', '>', 0)
				->whereNotIn('a.AccNo', $exceptShopIds)
				->groupBy('a.OrderDate', 'a.AccNo', 'a.ProductNo')
				->select('a.OrderDate as expectedDate', 'a.AccNo as storeNo', 'a.ProductNo as shortCode')
				->selectRaw('sum(a.Amount)')
				->ddRawSql();
				
		return $query->get()->toArray();
	}
}
