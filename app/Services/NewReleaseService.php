<?php

namespace App\Services;

use App\Repositories\NewReleaseRepository;
use App\Libraries\ShopLib;
use App\Libraries\ResponseLib;
use App\Traits\MenuTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;


class NewReleaseService
{
	use MenuTrait;
	
	private $_repository;
    
	public function __construct(NewReleaseRepository $newReleaseRepository)
	{
		$this->_repository = $newReleaseRepository;
	}
	
	/* Transfer url segment to new releast config key
	 * @params: string
	 * @return: array
	 */
	public function convertConfigKey($segment)
	{
		return Str::camel($segment);
	}
	
	/* 取新品銷售統計-入口
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($configKey)
	{
		#取新品設定
		$config = config("web.newrelease.products.{$configKey}");
		
		$saleDate	= (new Carbon($config['saleDate']))->format('Y-m-d');
		$endDate   	= Carbon::now()->format('Y-m-d');
		
		#設定Cache
		$cacheKey = implode(':', [$configKey, $saleDate, $endDate]);
		
		if (Cache::has($cacheKey))
		{
			Log::channel('webLog')->info('get from cache');
			return Cache::get($cacheKey);
		}
		else
		{
			Log::channel('webLog')->info('get from db');
			return $this->processStatistics($configKey, $cacheKey);
		}
	}
	
	/* 取新品銷售統計-主流程
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function processStatistics($configKey, $cacheKey)
	{
		try 
		{
			#initialize
			$statistics = [
				'productName' => '',
				'saleDate' => '',
				'startDate' => '',
				'endDate' => '',
				'shop' => [],
				'area' => [],
				'top' => [],
				'last' => []
			];
			
			#1.取新品參數 & Initialize 
			list($productName, $saleDate, $startDateTime, $endDateTime, $productIds, $bfProductIds) = $this->_getParams($configKey);
			$statistics['productName'] = $productName;
			$statistics['saleDate'] = (new Carbon($saleDate))->format('Y-m-d');
			$statistics['startDate'] = (new Carbon($startDateTime))->format('Y-m-d');
			$statistics['endDate'] = (new Carbon($endDateTime))->format('Y-m-d');
			
			#取查詢範圍總天數
			$startDate = new Carbon($startDateTime);
			$endDate = new Carbon($endDateTime);
			$diffDays = $startDate->diffInDays($endDate) + 1; 
			
			#2.Get data from DB(不同品牌DB不同)
			$data = $this->_getData($startDateTime, $endDateTime, $productIds, $bfProductIds);
			
			#3.Parsing to base data
			$baseData = $this->_buildBaseData($data);
			
			
			/* Statistics Start */
			#4.店別每日銷售
			$statistics['shop'] = $this->_parsingByShop($baseData, $diffDays);
			
			#5.區域彙總
			$statistics['area'] = $this->_parsingByArea($baseData, $diffDays);
			
			#6.當日銷售前10名 | 當日銷售後10名
			list($statistics['top'], $statistics['last']) = $this->_parsingByRanking($baseData, $statistics['endDate']);
			
			$result = ResponseLib::initialize($statistics)->success()->get();
			#save to Cache
			Cache::put($cacheKey, $result, now()->addMinutes(10));
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('webLog')->error($e->getMessage());
			return ResponseLib::initialize($statistics)->fail($e->getMessage())->get();
		}
	}
	
	/*==============================================================================*/
	/* 取Config設定
	 * @params: string
	 * @return: array
	 */
	private function _getParams($configKey)
	{
		$config = config("web.newrelease.products.{$configKey}");
		
		$productName	= data_get($config, 'name');
		$saleDate		= data_get($config, 'saleDate'); #開賣日
		
		#預防未來可能有查詢條件的狀況
		$startDateTime 	= sprintf('%s %s', $saleDate, '00:00:00');
		$endDateTime   	= Carbon::now()->setTime(23, 59, 59, 0)->toDateTimeString();
			
		//$startDateTime	= '2025/11/06 00:00:00'; #testing
		//$endDateTime   	= '2025/11/06 23:59:59'; #testing 
			
		$brandCode		= data_get($config, 'brand');
		$productIds 	= data_get($config, 'ids.main');
		$bfProductIds 	= [];
		
		if ($brandCode === 'BG') #梁社漢複合店取值用
			$bfProductIds = data_get($config, 'ids.mapping');
		
		return [$productName, $saleDate, $startDateTime, $endDateTime, $productIds, $bfProductIds];
	}
	
	/* Get main data & mapping data
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: array => product ids of BF
	 * @return: collection
	 */
	private function _getData($startDateTime, $endDateTime, $productIds, $bfProductIds)
	{
		#Get main data first
		$mainData = $this->_repository->getBgSaleData($startDateTime, $endDateTime, $productIds);
		
		if (! empty($bfProductIds)) #梁社漢新品時會有值
		{
			$shopIdMapping 	= config('web.newrelease.multiBrandShopidMapping');
			$bfShopIds 		= array_keys($shopIdMapping);
			
			$bfData	= $this->_repository->getBfSaleData($startDateTime, $endDateTime, $bfProductIds, $bfShopIds);
			$bfData = $bfData->map(function($item, $key) use ($shopIdMapping) {
				$item->SHOP_ID = $shopIdMapping[$item->SHOP_ID];
				return $item;
			});
			
			$mainData = $mainData->merge($bfData);
		}
		
		return $mainData;
	}
	
	/* 先分組成可共用的基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($sourceData)
	{
		$result = $sourceData->groupBy('SHOP_ID') #group by shop id
			->map(function($item, $key){
				
				
				$temp['shopId'] 	= $item->pluck('SHOP_ID')->get(0);
				$temp['shopName'] 	= $item->pluck('SHOP_NAME')->get(0);
				$temp['area'] 		= ShopLib::getAreaByShopId($temp['shopId']);
				
				$temp['dayQty'] 	= $item->mapToGroups(function($item, $key){ #group by date
					$dateKey = Str::before($item->SALE_DATE, ' ');
					return [$dateKey => $item->QTY];
				})->map(function($item, $key){
					return $item->sum();
				})->toArray();
				
				$item = $temp; #use $temp避免source被改寫
				return $item;
			})->toArray();
		
		#全轉成array回傳
		return $result;
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
		#基本資料已有, 只要再計算=>銷售總量|平均銷售數量
		$result = Arr::map($baseData, function($value, $key) use($diffDays) {
			
			$value['totalQty'] = array_sum($value['dayQty']); #所有日銷售量總和/店
			$value['totalAvg'] = round($value['totalQty'] / $diffDays, 1); #銷售量總和/ 
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
		$result['大台北區'] 	= data_get($data, '大台北區');
		$result['宜蘭區'] 	= data_get($data, '宜蘭區');
		$result['桃竹苗區'] 	= data_get($data, '桃竹苗區');
		$result['中彰投區'] 	= data_get($data, '中彰投區');
		$result['雲嘉南區'] 	= data_get($data, '雲嘉南區');
		$result['大高雄區'] 	= data_get($data, '大高雄區');
		
		$result['total']['shopCount'] 	= collect($data)->pluck('shopCount')->sum(); 
		$result['total']['totalQty'] 	= collect($data)->pluck('totalQty')->sum();
		$result['total']['avgDayQty'] 	= collect($data)->pluck('avgDayQty')->sum();
		$result['total']['avgShopQty'] 	= round($result['total']['totalQty'] / $result['total']['shopCount'], 1); #totalQty / shopCount
		$result['total']['avgDayShopQty']	= round($result['total']['avgDayQty'] / $result['total']['shopCount'], 1); #avgDayQty / shopCount
		
		return $result;
	}
	
	/* 當日銷售前10名
	 * @params: array
	 * @params: string
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
		$collection = collect($baseData);
		$result = $collection->map(function($item, $key) use($endDate) {
			$item['todayQty'] = intval(data_get($item, "dayQty.{$endDate}"));
			return $item;
		});
		
		$top = $result->sortByDesc('todayQty')->groupBy('todayQty')->take(10)->values();
		$last = $result->sortBy('todayQty')->groupBy('todayQty')->take(10)->values();
		
		return [$top, $last];
	}
}
