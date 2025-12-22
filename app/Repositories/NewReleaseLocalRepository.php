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
	
	/* Build query string | 新品:八方/梁社漢共用
	 * @params: string
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getDataFromDB($configKey, $stDate, $endDate, $userAreaIds)
	{
		/* 每筆訂單的資料格式
		["SHOP_ID" => "235001"
		  "QTY" => "1.0000"
		  "SALE_DATE" => "2025-12-19 17:13:11.000"
		  "SHOP_NAME" => "御廚中和直營店"
		]
		*/
		
		$table = $config = config("web.new_release.DbMapping.{$configKey}");
		
		$db = $this->connectSaleDashboard($table); #Local DB
		$query = $db
				->select('shopId', 'shopName', 'qty', 'saleDate', 'areaId')
				->where('saleDate', '>=', $stDate)
				->where('saleDate', '<=', $endDate)
				->whereIn('areaId', $userAreaIds)
				->orderBy('saleDate', 'DESC')
				->orderBy('shopId');
		
		return $query->get();
	}
}
