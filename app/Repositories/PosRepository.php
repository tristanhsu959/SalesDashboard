<?php

namespace App\Repositories;

use App\Libraries\ShopLib;
use Exception;

#新品:橙汁排骨/番茄牛三寶麵 => 邏輯相同 : 20251217 Local另起repository替換
class PosRepository extends Repository
{
	#MSSQL
	public function __construct()
	{
		
	}
	
	/* 取主資料-BuyGood
	 * @params: start date
	 * @params: end date
	 * @params: product ids
	 * @return: collection
	 */
	public function getBgSaleData($startDateTime, $endDateTime, $productIds)
	{
		$db = $this->connectBGPosErp('SALE01 as a');
		$result = $this->_getSaleResult($db, $startDateTime, $endDateTime, $productIds);
		
		return $result;
	}
	
	/* 取Mapping資料 | 複合店情境 - BaFang
	 * @params: start date
	 * @params: end date
	 * @params: product ids
	 * @params: bafang shop id
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
	 * @params: product ids
	 * @params: bafang shop id
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
	
	/* Insert pos data to mariadb
	 * @params: string
	 * @params: array
	 * @return: boolean
	 */
	public function posToLocal($configKey, $posData)
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
	}
}
