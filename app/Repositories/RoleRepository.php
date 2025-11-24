<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Exception;
use App\Exceptions\DBException;

class RoleRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Get Roles Data from DB
	 * @params: 
	 * @return: collection
	 */
	public function getList()
	{
		try
		{
			$db = $this->connectSaleDashboard('Role');
			
			$result = $db
				->select('RoleId', 'RoleName', 'RoleGroup')
				->get();
				
			return $result;
		}
		catch(Exception $e)
		{
			throw new DBException('讀取DB發生錯誤', $e->getMessage(), __class__, __function__);
			return FALSE;
		}
	}
	
	
	
	
	
	/* 取Mapping資料 | 複合店情境 - BaFang
	 * @params: start date
	 * @params: end date
	 * @params: brand code
	 * @return: collection
	 */
	public function getBfSaleData($startDateTime, $endDateTime, $productIds, $shopIds)
	{
		$db = $this->connectBFPosErp('SALE01 as a');
		$result = $this->_getSaleResult($db, $startDateTime, $endDateTime, $productIds, $shopIds);
		
		return $result;
	}
	
	/* Build query string | 新品:八方/梁社漢共用
	 * @params: start date
	 * @params: end date
	 * @params: brand code
	 * @return: collection
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
