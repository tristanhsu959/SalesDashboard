<?php

namespace App\Services;

use App\Repositories\PosRepository;
use App\Libraries\ShopLib;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;


class PosService
{
	private $_configKey 	= '';
	private $_data			= [];
    private $_repository;
	
	public function __construct(PosRepository $posRepository)
	{
		$this->_repository = $posRepository;
	}
	
	/* Fetch All POS DB data to Local (Excute once for initialize)
	 * @params: string (Match config key)
	 * @return: array
	 */
	public function fetch($configKey)
	{
		try
		{
			#新品目前似乎只有梁社漢有
			$this->_configKey = $configKey;
			
			#1. Get params fetch date
			$this->info('Get Params-----');
			$params = $this->_getParams();
			$this->info(json_encode($params));
						
			#2. Get POS DB data
			$this->info('Fetch data from POSDB -----');
			$posData = [];
			$posData = $this->_getDataFromPosDB($params);
			
			#3. Save data to local
			$this->info('Save Data to Local -----');
			$this->_repository->posToLocal($configKey, $posData);
			$this->info('initialize completed -----');
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
			
		}
	}
	
	/* 取Config設定及查詢時間區間參數
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	private function _getParams()
	{
		$params = [ 
			'stTime' 	=> '',
			'endTime'	=> '',
			'bgIds'		=> [],
			'bfIds'		=> [],
		];
		
		try
		{
			$config = config("web.new_release.products.{$this->_configKey}");
			
			$brand = data_get($config, 'brand');
						
			#計算initialize要取的時間, 以開賣日起算
			list($stTime, $endTime) = $this->_calcFetchTime($config['saleDate']);
			
			data_set($params, 'stTime', $stTime);
			data_set($params, 'endTime', $endTime);
			data_set($params, 'bgIds', data_get($config, 'ids.main'));
			data_set($params, 'bfIds', data_get($config, 'ids.mapping'));
				
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* 取查詢時間區間參數
	 * @params: string
	 * @return: array
	 */
	private function _calcFetchTime($saleDate)
	{
		try
		{
			if (empty($saleDate))
				throw new Exception('開賣日未設定');
			
			$stTime		= new Carbon($saleDate); #開賣日
			$endTime 	= Carbon::now()->subDay(); #取到前一天即可
			
			$stTime 	= $stTime->format('Y-m-d 00:00:00');
			$endTime 	= $endTime->format('Y-m-d 23:59:59');
			
			return [$stTime, $endTime];
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get main data & mapping data from POSDB
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromPosDB($params)
	{
		try
		{
			#Get main data first
			$mainData = $this->_repository->getBgSaleData($params['stTime'], $params['endTime'], $params['bgIds']);
			
			if (! empty($params['bfIds'])) #梁社漢新品時會有值
			{
				#取複合店Shop id
				$shopIdMapping 	= config('web.new_release.multiBrandShopidMapping');
				$shopIds 		= array_keys($shopIdMapping));
			
				$mappingData = $this->_repository->getBfSaleData($params['stTime'], $params['endTime'], $params['bfIds'], $shopIds);
				
				#避免未抓到資料的狀況
				if (! empty($mappingData))
				{
					#轉換對應的BG shop id
					$mappingData = $mappingData->map(function($item, $key) use ($shopIdMapping) {
						$item['SHOP_ID'] = $shopIdMapping[$item['SHOP_ID']];
						return $item;
					});
					
					$mainData = $mainData->merge($mappingData);
				}
			}
			
			/* 每筆訂單的資料格式
			["SHOP_ID" => "235001"
			  "QTY" => "1.0000"
			  "SALE_DATE" => "2025-12-19 17:13:11.000"
			  "SHOP_NAME" => "御廚中和直營店"
			]
			*/
			return $mainData;
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS DB資料失敗');
		}
	}
	
	
	
	
	
	
	
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	private function _outputReport($srcData)
	{
		try
		{
			#1.計算查詢範圍總天數 (use Date not DateTime)
			$startDate = new Carbon($this->_statistics['startDate']);
			$endDate = new Carbon($this->_statistics['endDate']);
			$diffDays = $startDate->diffInDays($endDate) + 1; 
			
			/*
			413001 => array:4 [▼
				"shopId" => "413001"
				"shopName" => "御廚台中霧峰店"
				"area" => 4
				"dayQty" => array:91 [▶]
			  ]
			*/
			
			#2.Build base data(所有資料By ShopId)
			$baseData = $this->_buildBaseData($srcData);
			
			#3.Filter By Area (By User Permission)
			$baseData = $this->_filterByAreaPermission($baseData);
			
			/* Statistics Start */
			#6.店別每日銷售
			$this->_statistics['shop'] = $this->_parsingByShop($baseData, $diffDays);
				
			#7.區域彙總
			$this->_statistics['area'] = $this->_parsingByArea($baseData, $diffDays);
				
			#8.當日銷售前10名 | 當日銷售後10名
			list($this->_statistics['top'], $this->_statistics['last']) = $this->_parsingByRanking($baseData, $this->_statistics['endDate']);
			
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
	
	/* 區域權限過濾
	 * @params: collection
	 * @return: array
	 */
	private function _filterByAreaPermission($baseData)
	{
		$userInfo = $this->getSigninUserInfo();
		$userAreaIds = $userInfo['area'];
			
		$baseData = Arr::reject($baseData, function ($item, $key) use($userAreaIds) {
			return ! in_array($item['area'], $userAreaIds);
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
