<?php

namespace App\Services;

use App\Repositories\PurchaseRepository;
use App\Libraries\ShopLib;
use App\Libraries\ResponseLib;
use App\Traits\AuthorizationTrait;
use App\Traits\MenuTrait;
use App\Enums\Brand;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;


class PurchaseService
{
	use AuthorizationTrait;
	
	private $_statistics	= [];
    
	public function __construct(protected PurchaseRepository $_repository)
	{
		$this->_statistics = [
			'brand'		=> '',
			'shop' 		=> [],
			'area' 		=> [],
		];
	}
	
	/* 取新品銷售統計-入口
	 * @params: string
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function search($searchBrand, $searchStDate, $searchEndDate)
	{
		try
		{
			#目前暫只有梁社漢
			if ($searchBrand != Brand::BUYGOOD->value)
				throw new Exception('無品牌代碼');
			
			#1. Get data
			$srcData = [];
			$srcData = $this->_getBaseDataByBg($searchStDate, $searchEndDate);
			
			/*
			if ($searchBrand = Brand::BUYGOOD)
				$srcData = $this->_getBaseDataByBg($searchStDate, $searchEndDate);
			else
				$srcData = $this->_getBaseDataByBf($searchStDate, $searchEndDate);
			*/
			
			return $this->_outputReport($srcData);
			
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	
	/* Get buy good data
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _getBaseDataByBg($searchStDate, $searchEndDate)
	{
		try
		{
			/* Return format */
			/*
			array:8 [
			  "OrderDate" => "2026-01-08 00:00:00.000"
			  "AccNo" => "330U005"
			  "AccName" => "御廚桃園桃鶯直營店"
			  "ProductNo" => "2052"
			  "ProductName" => "紅蘿蔔絲(御)"
			  "Unit" => "包"
			  "Amount" => "1.00"
			  "Money" => "36.00"
			]
			*/
			
			$tsData = $this->_repository->getDataFromTS($searchStDate, $searchEndDate);
			$rlData = $this->_repository->getDataFromRL($searchStDate, $searchEndDate);	
			$result = array_merge($tsData, $rlData);
			
			$baseData = $this->_rebuildBaseData($result);
			
			return $baseData;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取DB資料失敗');
		}
	}
	
	/* Rebuild data format
	 * @params: array
	 * @return: array
	 */
	private function _rebuildBaseData($source)
	{
		/* 重整資料格式, 取區域
		[
			"orderData" => "2026-01-08"
			"shopId" => "106004"
			"shopName" => "御廚大安一直營店"
			"productNo" => "4402"
			"productName" => "八方御露油膏"
			"unit" => "罐"
			"quantity" => "1.00"
			"amount" => "170.00"
			"areaId" => 1
			"area" => "大台北區"
		]
		*/
		
		$baseData = [];
		
		foreach($source as $row)
		{
			$item = [];
			$item['orderDate']	= Str::before($row['OrderDate'], ' ');
			$item['shopId']		= Str::replace('U', '', $row['AccNo']);;
			$item['shopName'] 	= $row['AccName'];
			$item['productNo'] 	= $row['ProductNo'];
			$item['productName']= $row['ProductName'];
			$item['unit'] 		= $row['Unit'];
			$item['quantity'] 	= $row['Amount'];
			$item['amount'] 	= $row['Money'];
			$item['areaId']		= ShopLib::getAreaIdByShopId($item['shopId']); #過濾用
			$item['area']		= ShopLib::getAreaByShopId($item['shopId']);
			
			$baseData[] = $item;
		}
		
		return $baseData;
	}
	
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($srcData)
	{
		try
		{
			#1.Build base data(所有資料By ShopId)
			$baseData = $this->_buildBaseData($srcData);
			
			#3.Filter By Area (By User Permission)
			$baseData = $this->_filterByAreaPermission($baseData);
			dd($baseData);
			/* Statistics Start */
			#6.店別每日銷售
			// $this->_statistics['shop'] = $this->_parsingByShop($baseData, $diffDays);
				
			#7.區域彙總
			// $this->_statistics['area'] = $this->_parsingByArea($baseData, $diffDays);
				
			return ResponseLib::initialize($this->_statistics)->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize($this->_statistics)->fail('解析報表資料發生錯誤');
		}
	}
	
	/* 先分組成可共用的基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($sourceData)
	{
		/* 重整資料格式, 取區域
		[
			"orderDate" => "2026-01-08"
			"shopId" => "330005"
			"shopName" => "御廚桃園桃鶯直營店"
			"areaId" => 1
			"area" => "桃竹苗區"
			"products" => array:43 [
				2052 => array:5 [
					"productNo" => "2052"
					"productName" => "紅蘿蔔絲(御)"
					"unit" => "包"
					"quantity" => 2.0
					"amount" => 72.0
				], ...
			]
		]
		*/
		$sourceData = collect($sourceData);
		$result = $sourceData->groupBy('shopId')
			->map(function($item, $key) {
				$temp['orderDate'] 	= $item->pluck('orderDate')->get(0);
				$temp['shopId'] 	= $item->pluck('shopId')->get(0);
				$temp['shopName'] 	= $item->pluck('shopName')->get(0);
				$temp['areaId'] 	= $item->pluck('areaId')->get(0);
				$temp['area'] 		= $item->pluck('area')->get(0);
				
				$temp['products'] 	= $item->groupBy('productNo')
					->map(function($item, $key){
						$temp['productNo'] 		= $item->pluck('productNo')->get(0);
						$temp['productName'] 	= $item->pluck('productName')->get(0);
						$temp['unit'] 			= $item->pluck('unit')->get(0);
						$temp['quantity'] 		= $item->sum('quantity');
						$temp['amount'] 		= $item->sum('amount');
						$item = $temp;
						return $item;
					})->toArray();
				
				$item = $temp;	
				return $item;
				
			})->toArray();
			
		#全轉成array回傳
		return $result;
	}
	
	/* 區域權限過濾
	 * @params: collection
	 * @return: array
	 */
	private function _filterByAreaPermission($baseData)
	{
		$userInfo = $this->getSigninUserInfo();
		$userAreaIds = $userInfo['area'];
		
		$baseData = Arr::reject($baseData, function ($item, $key) use($userAreaIds) {
			return ! in_array($item['areaId'], $userAreaIds);
		});
		
		return $baseData;
	}
	
	
	/* 店別每日銷售
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByShop($baseData, $diffDays)
	{
		/* 
		[
		330002 => [
		  "shopId" => "330002"
		  "shopName" => "御廚桃園中山東店"
		  "area" => "桃竹苗區"
		  "dayQty" =>  [
			"2025-09-15" => 6.0
			"2025-09-14" => 7.0
			]
		  "totalQty" => 13.0
		  "totalAvg" => 6.5
		]
		*/
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		#基本資料已有, 只要再計算=>銷售總量|平均銷售數量
		$result = Arr::map($baseData, function($value, $key) use($diffDays) {
			
			$value['totalQty'] = array_sum($value['dayQty']); #所有日銷售量總和/店
			$value['totalAvg'] = round($value['totalQty'] / $diffDays, 1); #銷售量總和
			return $value;
		});
		
		$result = Arr::sort($result);
		
		return $result;
	}
	
	/* 區域彙總
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByArea($baseData, $diffDays)
	{
		/*
		"area" => [
			"大台北區" => [
				"shopCount" => 101
				"totalQty" => 22208
				"avgDayQty" => 965.6
				"avgShopQty" => 219.9
				"avgDayShopQty" => 9.6
			]
			"大高雄區" => array:5 []
			"宜蘭區" => array:5 []
			"中彰投區" => array:5 []
			"雲嘉南區" => array:5 []
			"桃竹苗區" => array:5 []
		]
		*/
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		$collection = collect($baseData);
		$data = $collection->groupBy('area')->map(function($item, $key) use($diffDays) {
			$temp['shopCount']		= count($item); #店家數
			$temp['totalQty'] 		= intval($item->pluck('dayQty')->flatten()->sum()); #區域銷售總量
			$temp['avgDayQty'] 		= round($temp['totalQty'] / $diffDays, 1); #區域平均日銷售量: 區域銷售總量/天數
			$temp['avgShopQty'] 	= round($temp['totalQty'] / $temp['shopCount'], 1); #區域每店平均銷量: 區域銷售總量/店家數
			$temp['avgDayShopQty'] 	= round($temp['totalQty'] / $diffDays / $temp['shopCount'], 1); #區域每店平均日銷量: 區域銷售總量/店家數/天數
			
			return $temp;
		})->toArray();
		
		#重排區域的順序以保持顯示一致(系統跑會依抓到資料的順序)
		$result['大台北區'] 	= data_get($data, Area::TAIPEI->value, []);
		$result['宜蘭區'] 	= data_get($data, Area::YILAN->value, []);
		$result['桃竹苗區'] 	= data_get($data, Area::TCM->value, []);
		$result['中彰投區'] 	= data_get($data, Area::CCT->value, []);
		$result['雲嘉南區'] 	= data_get($data, Area::YCN->value, []);
		$result['大高雄區'] 	= data_get($data, Area::KAOHSIUNG->value, []);
		
		$result['total']['shopCount'] 	= collect($data)->pluck('shopCount')->sum(); 
		$result['total']['totalQty'] 	= collect($data)->pluck('totalQty')->sum();
		$result['total']['avgDayQty'] 	= collect($data)->pluck('avgDayQty')->sum();
		$result['total']['avgShopQty'] 	= round($result['total']['totalQty'] / $result['total']['shopCount'], 1); #totalQty / shopCount
		$result['total']['avgDayShopQty']	= round($result['total']['avgDayQty'] / $result['total']['shopCount'], 1); #avgDayQty / shopCount
		
		return $result;
	}
	
	/* 當日銷售前10名
	 * @params: array
	 * @params: date
	 * @return: array
	 */
	private function _parsingByRanking($baseData, $endDate)
	{
		/* 以銷售量來group shop
		[
		29 => [
			0 => [
				"shopId" => "103001"
				"shopName" => "御廚民生承德直營店"
				"area" => "大台北區"
				"dayQty" => array:5 [▶]
				"todayQty" => 29
			]
		]
		*/
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [[], []];
		
		#排名是依最後一天的值
		$collection = collect($baseData);
		$result = $collection->map(function($item, $key) use($endDate) {
			$item['todayQty'] = intval(data_get($item, "dayQty.{$endDate}"));
			return $item;
		});
		
		$top = $result->sortByDesc('todayQty')->groupBy('todayQty')->take(10)->values()->toArray();
		$last = $result->sortBy('todayQty')->groupBy('todayQty')->take(10)->values()->toArray();
		
		return [$top, $last];
	}
	
	/* ===== 暫廢棄 ===== */
	/* CRUD Permission Check for Page
	 * @params: int
	 * @return: boolean
	 *
	 public function getOperationPermission()
	 {
		try
		{
			return $this->allowOperationPermissionList($this->_groupKey, $this->_configKey);
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return [];
		}
	 }*/
	 
	/* ===== 原Business Login, 流程已變更 - 暫廢棄 ===== */
	/* 取新品銷售統計-主流程
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	/*public function processStatistics($cacheKey)
	{
		#initialize
		$statistics = [
			'productName' 	=> '',
			'saleDate' 		=> '',
			'saleEndDate' 	=> '',
			'startDate' 	=> '',
			'endDate' 		=> '',
			'shop' 	=> [],
			'area' 	=> [],
			'top' 	=> [],
			'last' 	=> [],
		];
			
			
		try 
		{
			#1.取新品參數 & Initialize 
			list($productName, $saleDate, $saleEndDate, $startDateTime, $endDateTime, $productIds, $bfProductIds) = $this->_getParams();
			
			$statistics['productName']	= $productName;
			$statistics['saleDate'] 	= (new Carbon($saleDate))->format('Y-m-d');
			$statistics['saleEndDate'] 	= (new Carbon($saleEndDate))->format('Y-m-d');
			$statistics['startDate'] 	= (new Carbon($startDateTime))->format('Y-m-d');
			$statistics['endDate'] 		= (new Carbon($endDateTime))->format('Y-m-d');
			
			#取查詢範圍總天數
			$startDate = new Carbon($startDateTime);
			$endDate = new Carbon($endDateTime);
			$diffDays = $startDate->diffInDays($endDate) + 1; 
			
			#2.Get data from DB(不同品牌DB不同)
			$data = $this->_getData($startDateTime, $endDateTime, $productIds, $bfProductIds);
			
			#3.Parsing to base data(所有資料By ShopId)
			$baseData = $this->_buildBaseData($data);
			
			/* Statistics Start *
			#4.店別每日銷售
			$statistics['shop'] = $this->_parsingByShop($baseData, $diffDays);
			
			#5.區域彙總
			$statistics['area'] = $this->_parsingByArea($baseData, $diffDays);
			
			#6.當日銷售前10名 | 當日銷售後10名
			list($statistics['top'], $statistics['last']) = $this->_parsingByRanking($baseData, $statistics['endDate']);
			
			#7.Save to Cache
			Cache::put($cacheKey, $statistics, now()->addMinutes(30));
			
			return ResponseLib::initialize($statistics)->success();
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($statistics)->fail($e->getMessage());
		}
	}*/
	/*==============================================================================*/

}
