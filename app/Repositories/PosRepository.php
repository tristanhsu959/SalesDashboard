<?php

namespace App\Repositories;

use App\Libraries\ShopLib;
use Illuminate\Support\Facades\Log;
use Exception;

#新品:橙汁排骨/番茄牛三寶麵 => 邏輯相同 : 20251217 Local另起repository替換
class PosRepository extends Repository
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
	public function getBgSaleData($startDateTime, $endDateTime, $productIds, $valueAdded)
	{
		$db = $this->connectBGPosErp('SALE01 as a');
		$result = $this->_getSaleResult($db, $startDateTime, $endDateTime, $productIds, $valueAdded);
		
		return $result;
	}
	
	/* 取Mapping資料 | 複合店情境 - BaFang
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
	private function _getSaleResult($db, $startDateTime, $endDateTime, $productIds, $valueAdded, $shopIds = NULL)
	{
		$query = $db
				->select('a.SHOP_ID', 'a.QTY', 'b.SALE_DATE', 'c.SHOP_NAME')
				->join('SALE00 as b', function($join) {
					$join->on('a.SHOP_ID', '=', 'b.SHOP_ID')
							->on('a.SALE_ID', '=', 'b.SALE_ID');
				})
				->join('SHOP00 as c', 'a.SHOP_ID', '=', 'c.SHOP_ID')
				->where('b.SALE_DATE', '>=', $startDateTime)
				->where('b.SALE_DATE', '<=', $endDateTime)
				->where(function ($db) {
					$db->whereIn('a.PROD_ID', $productIds);
					
					if (! empty($valueAdded))
						$db->orWhereLike('TASTE_Memo', "%{$valueAdded}%");	
				})
				
				->orderBy('b.SALE_DATE', 'DESC')
				->orderBy('a.SHOP_ID');
		
		if (! is_null($shopIds))
			$query->whereIn('a.SHOP_ID', $shopIds);
		
		Log::channel('commandLog')->error($query->toRawSql(), [ __class__, __function__, __line__]);
		
		return $query->get();
	}
	
	/* Insert pos data to mariadb by initialize
	 * @params: string
	 * @params: array
	 * @return: boolean
	 */
	public function insertPosToLocal($configKey, $posData)
	{
		/* 每筆訂單的資料格式
		["SHOP_ID" => "235001"
		  "QTY" => "1.0000"
		  "SALE_DATE" => "2025-12-19 17:13:11.000"
		  "SHOP_NAME" => "御廚中和直營店"
		]
		*/
		#Initialize前要先清空
		$table = $config = config("web.new_release.DbMapping.{$configKey}");
		
		$db = $this->connectLocalSalesDashboard();
		$db->table($table)->truncate();
		
		foreach($posData as $data)
		{
			$data['shopId']		= $data['SHOP_ID'];
			$data['shopName']	= $data['SHOP_NAME'];
			$data['areaId']		= ShopLib::getAreaIdByShopId($data['SHOP_ID']);
			$data['qty']		= $data['QTY'];
			$data['saleDate']	= (new Carbon($data['SALE_DATE']))->format('Y-m-d');
			$data['updateAt'] 	= now()->format('Y-m-d H:i:s');
				
			$db->insert($data);
		}
		
		return TRUE;
	}
	
	/* Insert pos data to mariadb by initialize
	 * @params: string
	 * @params: array
	 * @params: date
	 * @params: date
	 * @return: boolean
	 */
	public function updatePosToLocal($configKey, $posData, $stDate, $endDate)
	{
		/* 每筆訂單的資料格式
		["SHOP_ID" => "235001"
		  "QTY" => "1.0000"
		  "SALE_DATE" => "2025-12-19 17:13:11.000"
		  "SHOP_NAME" => "御廚中和直營店"
		]
		*/
		#Initialize前要先清空
		$table = $config = config("web.new_release.DbMapping.{$configKey}");
		
		$db = $this->connectLocalSalesDashboard();
		$db->table($table)
			->where('saleDate', '>=', $stDate)
			->where('saleDate', '<=', $endDate)
			->delete();
		
		foreach($posData as $data)
		{
			$data['shopId']		= $data['SHOP_ID'];
			$data['shopName']	= $data['SHOP_NAME'];
			$data['areaId']		= ShopLib::getAreaIdByShopId($data['SHOP_ID']);
			$data['qty']		= $data['QTY'];
			$data['saleDate']	= (new Carbon($data['SALE_DATE']))->format('Y-m-d'); #只存至Date
			$data['updateAt'] 	= now()->format('Y-m-d H:i:s');
				
			$db->insert($data);
		}
		
		return TRUE;
	}
}
