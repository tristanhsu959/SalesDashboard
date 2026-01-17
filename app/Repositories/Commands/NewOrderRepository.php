<?php

namespace App\Repositories\Commands;

use App\Repositories\Repository;
use App\Libraries\ShopLib;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

#新訂貨系統DB
class NewOrderRepository extends Repository
{
	#MSSQL
	public function __construct()
	{
		
	}
	
	/* 取訂貨資料-不分Brand
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	public function getOrderData($startDateTime, $endDateTime)
	{
		$db = $this->connectNewOrder('Order as a');
		$query = $db
				->select('a.No as orderNo', 'a.ExpectedDate', 'a.Money as orderAmount')
				->addSelect('g.No as productNo', 'g.Name as productName', 'h.Name as productType')
				->addSelect('b.UnitName as productUnit', 'b.Price as productPrice', 'b.Quantity as productQuantity', 'b.Money as productAmount')
				->addSelect('c.Name as storeName', 'c.No as storeNo', 'f.Name as storeType')
				->addSelect('d.name as area', 'e.Name as brand', 'e.No as brandNo')
				->join('OrderSub as b', function($join) {
					$join->on('b.OrderId', '=', 'a.Id')
							->on('b.Quantity', '>', 0);
				})
				->join('Store as c', 'c.Id', '=', 'a.StoreId')
				->join('Area as d', 'd.Id', '=', 'c.AreaId')
				->join('Brand as e', 'e.Id', '=', 'c.BrandId')
				->join('BusinessType as f', 'f.Id', '=', 'c.BusinessTypeId')
				->join('Product as g', 'g.Id', '=', 'b.ProductId')
				->join('ProductType as h', 'h.Id', '=', 'g.ProductTypeId')
				->where('a.ExpectedDate', '>=', $startDateTime)
				->where('a.ExpectedDate', '<=', $endDateTime)
				->where(function ($db) {
					$db->where('a.OperationCenterId', '=', 1);
						->orWhere('a.OperationCenterId', '=', 2);
				})
				->where('a.Money', '>', 0)
				
		Log::channel('commandLog')->info($query->toRawSql(), [ __class__, __function__, __line__]);
		
		return $query->get();
	}
	
	/* Insert order data to mariadb
	 * @params: string
	 * @params: array
	 * @return: boolean
	 */
	public function insertOrderToLocal($data)
	{
		/* 每筆訂單的資料格式
		["SHOP_ID" => "235001"
		  "QTY" => "1.0000"
		  "SALE_DATE" => "2025-12-19 17:13:11.000"
		  "SHOP_NAME" => "御廚中和直營店"
		]
		*/
		#Initialize前要先清空
		$table = $config = config("buygood.new_release.DbMapping.{$configKey}");
		
		$db = $this->connectSalesDashboard();
		$db->table($table)->truncate();
		
		foreach($posData as $row) #$data重覆了
		{
			$data['shopId']		= $row['SHOP_ID'];
			$data['shopName']	= $row['SHOP_NAME'];
			$data['areaId']		= ShopLib::getAreaIdByShopId($row['SHOP_ID']);
			$data['qty']		= floatval($row['QTY']);
			$data['saleDate']	= (new Carbon($row['SALE_DATE']))->format('Y-m-d');
			$data['updateAt'] 	= now()->format('Y-m-d H:i:s');
				
			$db->table($table)->insert($data);
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
		$table = $config = config("buygood.new_release.DbMapping.{$configKey}");
		
		$db = $this->connectSalesDashboard();
		$db->table($table)
			->where('saleDate', '>=', $stDate)
			->where('saleDate', '<=', $endDate)
			->delete();
		
		foreach($posData as $row)
		{
			$data['shopId']		= $row['SHOP_ID'];
			$data['shopName']	= $row['SHOP_NAME'];
			$data['areaId']		= ShopLib::getAreaIdByShopId($row['SHOP_ID']);
			$data['qty']		= intval($row['QTY']);
			$data['saleDate']	= (new Carbon($row['SALE_DATE']))->format('Y-m-d'); #只存至Date
			$data['updateAt'] 	= now()->format('Y-m-d H:i:s');
				
			$db->table($table)->insert($data);
		}
		
		return TRUE;
	}
}
