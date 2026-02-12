<?php

namespace App\Repositories;

use Exception;

#新品:橙汁排骨/番茄牛三寶麵 => 邏輯相同 : 這裏改取自Local DB
class NewReleaseLocalRepository extends Repository
{
	#MSSQL
	public function __construct()
	{
		
	}
	
	/* Build query string | 新品:八方
	 * @params: string
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getBfDataFromDB($configKey, $stDate, $endDate, $userAreaIds)
	{
		/* 每筆訂單的資料格式
		["SHOP_ID" => "235001"
		  "QTY" => "1.0000"
		  "SALE_DATE" => "2025-12-19 17:13:11.000"
		  "SHOP_NAME" => "御廚中和直營店"
		]
		*/
		
		$tables = config("bafang.new_release.DbMapping.{$configKey}");
		
		#目前有取兩個table data的狀況
		if (is_array($tables))
		{
			$data = collect([]);
			
			foreach($tables as $table)
			{
				$temp = $this->_getData($table, $stDate, $endDate, $userAreaIds);
				$data = $data->merge($temp);
			}
			
			return $data;
		}
		else
			return $this->_getData($tables, $stDate, $endDate, $userAreaIds);
	}
	
	/* Build query string | 新品:梁社漢
	 * @params: string
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getBgDataFromDB($configKey, $stDate, $endDate, $userAreaIds)
	{
		/* 每筆訂單的資料格式
		["SHOP_ID" => "235001"
		  "QTY" => "1.0000"
		  "SALE_DATE" => "2025-12-19 17:13:11.000"
		  "SHOP_NAME" => "御廚中和直營店"
		]
		*/
		
		$tables = config("buygood.new_release.DbMapping.{$configKey}");
		
		#目前有取兩個table data的狀況
		if (is_array($tables))
		{
			$data = collect([]);
			
			foreach($tables as $table)
			{
				$temp = $this->_getData($table, $stDate, $endDate, $userAreaIds);
				$data = $data->merge($temp);
			}
			
			return $data;
		}
		else
			return $this->_getData($tables, $stDate, $endDate, $userAreaIds);
	}
	
	/* 因報表有組合兩個新品的狀況, 故另開function處理
	 * @params: string
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	private function _getData($table, $stDate, $endDate, $userAreaIds)
	{
		/* 每筆訂單的資料格式
		["SHOP_ID" => "235001"
		  "QTY" => "1.0000"
		  "SALE_DATE" => "2025-12-19 17:13:11.000"
		  "SHOP_NAME" => "御廚中和直營店"
		]
		*/
		
		$db = $this->connectSalesDashboard($table); #Local DB
		$query = $db
				->select('shopId', 'shopName', 'qty', 'saleDate', 'areaId')
				->where('saleDate', '>=', $stDate)
				->where('saleDate', '<=', $endDate)
				->whereIn('areaId', $userAreaIds)
				->orderBy('saleDate', 'DESC')
				->orderBy('shopId');
		#dd($query->toRawSql());
		return $query->get();
	}
}
