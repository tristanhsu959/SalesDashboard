<?php

namespace App\Services;

use App\Repositories\NewReleaseRepository;
use App\Libraries\ShopLib;
use App\Libraries\ResponseLib;
use App\Traits\AuthorizationTrait;
use App\Traits\MenuTrait;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;


class NewReleaseService
{
	use AuthorizationTrait;
	
	private $_groupKey	= 'newRelease';
	private $_actionKey = '';
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
		#action key = config key
		$this->_actionKey = Str::camel($segment);
		return $this->_actionKey;
	}
	
	/* 取新品銷售統計-入口
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($configKey)
	{
		#20251216 要改成分兩段
		#1. 只取DB資料到Cache => 頁面及排程要共用的部份
		#2. Get data from cache, parsing data for output
		$this->_actionKey = $configKey;
		
		#取新品設定
		$config = config("web.newrelease.products.{$this->_actionKey}");
		$cacheEnable = config('web.newrelease.cacheEnable');
		
		$saleDate	= (new Carbon($config['saleDate']))->format('Y-m-d');
		$endDate   	= Carbon::now()->format('Y-m-d');
		
		#設定Cache
		$cacheKey = implode(':', [$this->_actionKey, $saleDate, $endDate]);
		
		$srcData = [];
		if ($cacheEnable && Cache::has($cacheKey))
		{
			Log::channel('webSysLog')->info('新品銷售：Get from cache', [ __class__, __function__]);
			$srcData = Cache::get($cacheKey);
		}
		else
		{
			Log::channel('webSysLog')->info('新品銷售：Get from DB', [ __class__, __function__]);
			#$srcData = $this->processStatistics($cacheKey);
			$response = $this->getDataFromDB($cacheKey);
			
			if ($response->status === FALSE)
				return $response;
			
			$srcData = $response->data;
		}
		
		return $this->_outputReport($srcData);
	}
	
	/* 取報表原始資料
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function getDataFromDB($cacheKey)
	{
		$data = [];
		
		try
		{
			#1.Build新品查詢參數
			list($productName, $saleDate, $saleEndDate, $startDateTime, $endDateTime, $productIds, $bfProductIds) = $this->_getParams();
			
			#2.這裏只取DB原始資料
			$data = $this->_getData($startDateTime, $endDateTime, $productIds, $bfProductIds);
			
			#3.Save to Cache(只存原始資料, 每次都要重新建報表, 以防權限變更)
			Cache::put($cacheKey, $data, now()->addMinutes(30));
			
			return ResponseLib::initialize($data)->success();
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($data)->fail($e->getMessage());
		}
	}
	
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	private function _outputReport($srcData)
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
			#1.建立參數
			list($productName, $saleDate, $saleEndDate, $startDateTime, $endDateTime, $productIds, $bfProductIds) = $this->_getParams();
			
			#2.計算查詢範圍總天數
			$startDate = new Carbon($startDateTime);
			$endDate = new Carbon($endDateTime);
			$diffDays = $startDate->diffInDays($endDate) + 1; 
			
			#3.Initialize
			$statistics['productName']	= $productName;
			$statistics['saleDate'] 	= (new Carbon($saleDate))->format('Y-m-d');
			$statistics['saleEndDate'] 	= (new Carbon($saleEndDate))->format('Y-m-d');
			$statistics['startDate'] 	= (new Carbon($startDateTime))->format('Y-m-d');
			$statistics['endDate'] 		= (new Carbon($endDateTime))->format('Y-m-d');
			
			#4.Build base data(所有資料By ShopId)
			$baseData = $this->_buildBaseData($srcData);
			
			#5.Filter By Area (By User Permission)
			$userInfo 		= $this->getSigninUserInfo();
			$userAreaIds 	= $userInfo['UserAreaId'];
			
			$baseData = Arr::reject($baseData, function ($item, $key) use($userAreaIds) {
					return ! in_array($item['area'], $userAreaIds);
			});
			
			/* Statistics Start */
			#6.店別每日銷售
			$statistics['shop'] = $this->_parsingByShop($baseData, $diffDays);
				
			#7.區域彙總
			$statistics['area'] = $this->_parsingByArea($baseData, $diffDays);
				
			#8.當日銷售前10名 | 當日銷售後10名
			list($statistics['top'], $statistics['last']) = $this->_parsingByRanking($baseData, $statistics['endDate']);
			
			return ResponseLib::initialize($statistics)->success();
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($statistics)->fail('解析報表資料發生錯誤');
		}
	}
	
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
	/* 取Config設定
	 * @params: string
	 * @return: array
	 */
	private function _getParams()
	{
		try
		{
			$config = config("web.newrelease.products.{$this->_actionKey}");
			
			$productName	= data_get($config, 'name');
			$saleDate		= data_get($config, 'saleDate'); #開賣日
			$saleEndDate	= data_get($config, 'saleEndDate', ''); #停售日
			
			#預防未來可能有查詢條件的狀況
			#$startDateTime 	= sprintf('%s %s', $saleDate, '00:00:00');
			$startDateTime 	= (new Carbon($saleDate))->format('Y-m-d 00:00:00');
			
			#取結束日
			$today = Carbon::now()->setTime(23, 59, 59, 0);
			
			if (empty($saleEndDate))
				$endDateTime = $today;
			else
			{
				$endDay = (new Carbon($saleEndDate))->setTime(23, 59, 59, 0);
				$endDateTime = $endDay->greaterThan($today) ? $today : $endDay;
				$endDateTime = $endDateTime->format('Y-m-d 23:59:59');
			}
			
			#testing
			#$startDateTime	= '2025/12/04 00:00:00'; #testing
			#$endDateTime   = '2025/12/05 23:59:59'; #testing 
				
			$brandCode		= data_get($config, 'brand');
			$productIds 	= data_get($config, 'ids.main');
			$bfProductIds 	= [];
			
			if ($brandCode === 'BG') #梁社漢複合店取值用
				$bfProductIds = data_get($config, 'ids.mapping');
			
			return [$productName, $saleDate, $saleEndDate, $startDateTime, $endDateTime, $productIds, $bfProductIds];
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			throw new Exception('讀取參數失敗');
		}
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
		try
		{
			#Get main data first
			$mainData = $this->_repository->getBgSaleData($startDateTime, $endDateTime, $productIds);
			
			if (! empty($bfProductIds)) #梁社漢新品時會有值
			{
				$shopIdMapping 	= config('web.newrelease.multiBrandShopidMapping');
				$bfShopIds 		= array_keys($shopIdMapping);
				
				$bfData	= $this->_repository->getBfSaleData($startDateTime, $endDateTime, $bfProductIds, $bfShopIds);
				
				#避免未抓到資料的狀況
				if (empty($bfData))
				{
					$bfData = $bfData->map(function($item, $key) use ($shopIdMapping) {
						$item['SHOP_ID'] = $shopIdMapping[$item['SHOP_ID']];
						return $item;
					});
					
					$mainData = $mainData->merge($bfData);
				}
			}
			
			return $mainData;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			throw new Exception('讀取DB資料失敗');
		}
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
				$temp['area'] 		= ShopLib::getAreaIdByShopId($temp['shopId']);
				
				$temp['dayQty'] = $item->mapToGroups(function($item, $key){ #group by date
					$dateKey = Str::before($item['SALE_DATE'], ' ');
					return [$dateKey => $item['QTY']];
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
		
		/* 已改為Area Id
		$result['大台北區'] 	= data_get($data, '大台北區');
		$result['宜蘭區'] 	= data_get($data, '宜蘭區');
		$result['桃竹苗區'] 	= data_get($data, '桃竹苗區');
		$result['中彰投區'] 	= data_get($data, '中彰投區');
		$result['雲嘉南區'] 	= data_get($data, '雲嘉南區');
		$result['大高雄區'] 	= data_get($data, '大高雄區');
		*/
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
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [[], []];
		
		$collection = collect($baseData);
		$result = $collection->map(function($item, $key) use($endDate) {
			$item['todayQty'] = intval(data_get($item, "dayQty.{$endDate}"));
			return $item;
		});
		
		$top = $result->sortByDesc('todayQty')->groupBy('todayQty')->take(10)->values()->toArray();
		$last = $result->sortBy('todayQty')->groupBy('todayQty')->take(10)->values()->toArray();
		
		return [$top, $last];
	}
	
	/* CRUD Permission Check for Page
	 * @params: int
	 * @return: boolean
	 */
	 public function getOperationPermission()
	 {
		try
		{
			return $this->allowOperationPermissionList($this->_groupKey, $this->_actionKey);
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return [];
		}
	 }
}
