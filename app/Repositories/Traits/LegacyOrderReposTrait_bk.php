<?php

namespace App\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#舊訂貨系統
trait LegacyOrderReposTrait
{
	#MSSQL
	public function __construct()
	{
		
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByFactory($stDate, $endDate, $productNos)
	{
		$byStore = FALSE;
		$tp = $this->getTpExtraData($stDate, $endDate, $productNos, $byStore);
		$kh = $this->getKhExtraData($stDate, $endDate, $productNos, $byStore);
		
		$result = $tp->merge($kh)->toArray();
		return $result;
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByStore($stDate, $endDate, $productNos)
	{
		$byStore = TRUE;
		$tp = $this->getTpExtraData($stDate, $endDate, $productNos, $byStore);
		$kh = $this->getKhExtraData($stDate, $endDate, $productNos, $byStore);
		
		$tp = $tp->map(function($item, $key){
			$item['storeNo'] = 'TP' . $item['storeNo'];
			return $item;
		});
		
		$kh = $kh->map(function($item, $key){
			$item['storeNo'] = 'KH' . $item['storeNo'];
			return $item;
		});
		
		#要加上prefix才能對齊
		$result = $tp->merge($kh)->toArray();
		return $result;
	}
	
	/* 取TP資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getTpExtraData($stDate, $endDate, $productNos, $byStore)
	{
		$db = $this->connectOrderTP();
		$result = $this->_getExtraOrder($db, $stDate, $endDate, $productNos, $byStore, 'OrderItem');
		$resultOld = $this->_getExtraOrder($db, $stDate, $endDate, $productNos, $byStore, 'OrderItem_old');
		
		$result = $result->merge($resultOld);
		
		if ($byStore === FALSE) #by factory
		{
			$result = $result->map(function($item, $key){
				$item['factoryNo'] = 'TW_TP';
				return $item;
			});
		}
		
		return $result;
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getKhExtraData($stDate, $endDate, $productNos, $byStore)
	{
		$db = $this->connectOrderKH();
		$result = $this->_getExtraOrder($db, $stDate, $endDate, $productNos, $byStore, 'OrderItem');
		$resultOld = $this->_getExtraOrder($db, $stDate, $endDate, $productNos, $byStore, 'OrderItem_old');
		
		$result = $result->merge($resultOld);
		
		if ($byStore === FALSE) #by factory
		{
			$result = $result->map(function($item, $key){
				$item['factoryNo'] = 'TW_KH';
				return $item;
			});
		}
		
		return $result;
	}
	
	/* Build query string | 追加
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getExtraOrder($db, $stDate, $endDate, $productNos, $byStore, $table)
	{
		$exceptShopIds = ['10006000', '999111', '999999', '10007000', '1000', '1100000'];
		
		#union all有點慢
		$result = $db->table("{$table} as a")
				->fromRaw("{$table} as a WITH(NOLOCK)")
				->select('a.ProductNo as shortCode')
				->selectRaw('sum(a.Amount) as qty, LEFT(CAST(a.OrderDate AS DATE), 7) as expectedDate')
				->where('a.OrderDate', '>=', $stDate)
				->where('a.OrderDate', '<=', $endDate)
				->where('a.Ps', '!=', 'OMS') #追加會是空白
				->whereIn('a.ProductNo', $productNos)
				->where('a.Money', '>', 0)
				->whereNotIn('a.AccNo', $exceptShopIds)
				->when($byStore, function($query){
					$query->addSelect('a.AccNo as storeNo')
							->groupBy('a.AccNo');
				})
				->groupBy('a.ProductNo')
				->groupByRaw('LEFT(CAST(a.OrderDate AS DATE), 7)') 
				->get();
		
		return $result;
	}
	
	
	/******************** Get all extra data ********************/
	/* 取追加By store id
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getAllExtraDataByStoreId($brand, $stDate, $endDate, $storeId)
	{
		$byStore = FALSE;
		$tp = $this->getTpAllExtraData($stDate, $endDate, $productNos, $byStore);
		$kh = $this->getKhAllExtraData($stDate, $endDate, $productNos, $byStore);
		
		$result = $tp->merge($kh)->toArray();
		return $result;
	}
	
	/* 取TP資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getTpAllExtraData($stDate, $endDate, $productNos, $byStore)
	{
		$db = $this->connectOrderTP();
		$result = $this->_getExtraOrder($db, $stDate, $endDate, $productNos, $byStore, 'OrderItem');
		$resultOld = $this->_getExtraOrder($db, $stDate, $endDate, $productNos, $byStore, 'OrderItem_old');
		
		$result = $result->merge($resultOld);
		
		if ($byStore === FALSE) #by factory
		{
			$result = $result->map(function($item, $key){
				$item['factoryNo'] = 'TW_TP';
				return $item;
			});
		}
		
		return $result;
	}
	
	/* 取KH資料-僅追加(以防有獨立取資料的狀況, 故獨立出來)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getKhAllExtraData($stDate, $endDate, $productNos, $byStore)
	{
		$db = $this->connectOrderKH();
		$result = $this->_getExtraOrder($db, $stDate, $endDate, $productNos, $byStore, 'OrderItem');
		$resultOld = $this->_getExtraOrder($db, $stDate, $endDate, $productNos, $byStore, 'OrderItem_old');
		
		$result = $result->merge($resultOld);
		
		if ($byStore === FALSE) #by factory
		{
			$result = $result->map(function($item, $key){
				$item['factoryNo'] = 'TW_KH';
				return $item;
			});
		}
		
		return $result;
	}
	
	/* Build query string | 追加
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getAllExtraOrder($db, $stDate, $endDate, $productNos, $byStore, $table)
	{
		$exceptShopIds = ['10006000', '999111', '999999', '10007000', '1000', '1100000'];
		
		#union all有點慢
		$result = $db->table("{$table} as a")
				->fromRaw("{$table} as a WITH(NOLOCK)")
				->select('a.ProductNo as shortCode')
				->selectRaw('sum(a.Amount) as qty, LEFT(CAST(a.OrderDate AS DATE), 7) as expectedDate')
				->where('a.OrderDate', '>=', $stDate)
				->where('a.OrderDate', '<=', $endDate)
				->where('a.Ps', '!=', 'OMS') #追加會是空白
				->whereIn('a.ProductNo', $productNos)
				->where('a.Money', '>', 0)
				->whereNotIn('a.AccNo', $exceptShopIds)
				->when($byStore, function($query){
					$query->addSelect('a.AccNo as storeNo')
							->groupBy('a.AccNo');
				})
				->groupBy('a.ProductNo')
				->groupByRaw('LEFT(CAST(a.OrderDate AS DATE), 7)') 
				->get();
		
		return $result;
	}
}
