<?php

namespace App\Manager\Repositories;

use App\Repositories\Repository;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;

#舊訂貨系統
class LegacyRepository  extends Repository
{
	
	
	/* 取TP資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getTpExtraData($stDate, $endDate, $productCodes)
	{
		$db = $this->connectOrderTP();
		$result = $this->_getExtraOrder($db, 'OrderItem', $stDate, $endDate, $productCodes);
		$resultOld = $this->_getExtraOrder($db, 'OrderItem_old', $stDate, $endDate, $productCodes);
		
		return $result->merge($resultOld);
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getKhExtraData($stDate, $endDate, $productCodes)
	{
		$db = $this->connectOrderKH();
		$result = $this->_getExtraOrder($db, 'OrderItem', $stDate, $endDate, $productCodes);
		$resultOld = $this->_getExtraOrder($db, 'OrderItem_old', $stDate, $endDate, $productCodes);
		
		return $result->merge($resultOld);
	}
	
	/* 取TP資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getTsExtraData($stDate, $endDate, $productCodes)
	{
		$db = $this->connectOrderTS();
		$result = $this->_getExtraOrder($db, 'OrderItem', $stDate, $endDate, $productCodes);
		$resultOld = $this->_getExtraOrder($db, 'OrderItem_old', $stDate, $endDate, $productCodes);
		
		return $result->merge($resultOld);
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getRlExtraData($stDate, $endDate, $productCodes)
	{
		$db = $this->connectOrderRL();
		$result = $this->_getExtraOrder($db, 'OrderItem', $stDate, $endDate, $productCodes);
		$resultOld = $this->_getExtraOrder($db, 'OrderItem_old', $stDate, $endDate, $productCodes);
		
		return $result->merge($resultOld);
	}
	
	/* Build query string | 追加
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getExtraOrder($db, $table, $stDate, $endDate, $productNos)
	{
		$exceptShopIds = ['10006000', '999111', '999999', '10007000', '1000', '1100000'];
		
		#union all有點慢
		$result = $db->table("{$table} as a")
				->fromRaw("{$table} as a WITH(NOLOCK)")
				->select('a.ProductNo as shortCode', 'a.ProductName as productName')
				->addSelect('a.AccNo as storeNo', 'a.OrderDate as expectedDate')
				->addSelect('a.Amount as qty', 'a.Money as amount')
				#->selectRaw('sum(a.Amount) as qty, sum(a.Money) as amount')
				#->selectRaw('LEFT(CAST(a.OrderDate AS DATE), 7) as expectedDate')
				->where('a.OrderDate', '>=', $stDate)
				->where('a.OrderDate', '<', $endDate)
				->where('a.Ps', '!=', 'OMS') #追加會是空白
				->when(($productNos !== FALSE), function($query) use($productNos){
					$query->whereIn('a.ProductNo', $productNos);
				})
				->where('a.Money', '>', 0)
				->whereNotIn('a.AccNo', $exceptShopIds)
				#->groupBy('a.ProductNo', 'a.ProductName', 'a.AccNo', 'a.ProductName')
				#->groupByRaw('LEFT(CAST(a.OrderDate AS DATE), 7)') 
				->get();
				
		return $result;
	}
}
