<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\DailyRevenueRepository;
use App\Services\Traits\Sales\StoreServiceTrait;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
use App\Libraries\HelperLib;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

class DailyRevenueService
{
	use StoreServiceTrait;
	
	private $_statistics	= [];
	private $_brand			= NULL;
	private $_userAreaIds 	= [];
	private $_storeList		= [];
   
	public function __construct(protected DailyRevenueRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'shop' 			=> [],
			'area' 			=> [],
			'exportToken'	=> '', #export
		];
		
		$this->_storeList['all'] 	= [];
		$this->_storeList['active'] = [];
	}
	
	/* Parsing brand from url segment
	 * @params: string
	 * @return: string
	 */
	public function parsingBrand($segments)
	{
		$brand = $segments[0];
		return Brand::tryFromCode($brand);
	}
	
	/* Parsing function by brand
	 * @params: enums
	 * @return: string
	 */
	public function parsingFunction($brand)
	{
		return match ($brand) 
		{
			Brand::BAFANG	=> Functions::BF_DAILY_REVENUE, 
			Brand::BUYGOOD	=> Functions::BG_DAILY_REVENUE,
			Brand::FJVEGGIE	=> Functions::FJ_DAILY_REVENUE,
        };
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($brand, $searchStDate, $searchEndDate, $searchShopType, $searchShopName)
	{
		try
		{
			#Check cache
			$functions = $this->parsingFunction($brand);
			$searchEndDate = empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
			$cacheKey = HelperLib::buildCacheKey([$functions->value, $searchStDate, $searchEndDate, $searchShopType, $searchShopName]);
			
			#主要是for即時，故每次都query
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get daily revenue from cache');
				
				$statistics = Cache::get($cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get daily revenue from db');
				
				$this->_initStatistics($brand, $searchStDate, $searchEndDate);
				
				if (empty($this->_userAreaIds))
					throw new Exception('此帳號無區域瀏覽權限');
				
				#執行統計
				$this->_analysisStatisticsData($searchShopType, $searchShopName);
				
				#無值不cache
				if (! empty($this->_statistics['shop']) OR ! empty($this->_statistics['area']))
				{
					$this->_statistics['exportToken'] = bin2hex($cacheKey); #hex2bin
					Cache::put($cacheKey, $this->_statistics, now()->addMinutes(30));
				}
				
				return ResponseLib::initialize($this->_statistics)->success();
			}
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* 初始代統計資料
	 * @params: enums
	 * @params: array
	 * @return: array
	 */
	private function _initStatistics($brand, $searchStDate, $searchEndDate)
	{
		$this->_brand = $brand;
		
		#儲存統計結果所需資料(與頁面呈現有關)
		$this->_statistics['brandId']	= $brand->value; 
		$this->_statistics['startDate'] = (new Carbon($searchStDate))->format('Y-m-d'); 
		$this->_statistics['endDate'] 	= (new Carbon($searchEndDate))->format('Y-m-d');
		
		#區域權限
		$currentUser = AppManager::getCurrentUser();
		$this->_userAreaIds = $currentUser->roleArea; 
	}
	
	/* 取新品銷售統計
	 * @params: enums
	 * @params: array
	 * @return: array
	 */
	private function _analysisStatisticsData($searchShopType, $searchShopName)
	{
		try
		{
			#1. Prepare shop data
			$this->_shopList['all'] 	= $this->_getAllStores($this->_brand, $this->_userAreaIds, $searchShopType, $searchShopName);
			$this->_shopList['active'] 	= $this->_getActiveStores($this->_brand, $this->_userAreaIds, $searchShopType, $searchShopName);
			
			#2. Get POS data
			$saleData = $this->_getDataFromDB($searchShopType, $searchShopName);
			
			#3. Build base data
			#會有false的無效array, 用array_filter去除
			$baseData = $this->_buildBaseData(array_filter($saleData));
			unset($saleData);
			
			return $this->_outputReport($baseData);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	/* ====================== 主流程 End ====================== */
	
	/* Get main data & mapping data
	 * @params: enums
	 * @params: date
	 * @params: date
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromDB($shopType, $shopName)
	{
		try
		{
			$stDate		= (new Carbon($this->_statistics['startDate']))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($this->_statistics['endDate']))->format('Y-m-d 23:59:59');
			
			$saleData = $this->_repository->getSale00Data($this->_brand, $this->_userAreaIds, $stDate, $endDate, $shopType, $shopName);
			
			return $saleData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	
	
	/* 基底資料(DB已計算Sum)
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($saleData)
	{
		/*
		0 => array:8 [▼
		  "shopId" => "0035"
		  "shopName" => "0035中壢海華直營店"
		  "areaId" => 3
		  "areaName" => "桃竹苗區"
		  "shopType" => "1"
		  "shopTypeName" => "直營店"
		  "saleDate" => "2026-05-10"
		  "amount" => "18735.0000"
		]
		*/
		
		#即時營收取有效店家即可
		$saleData = $this->_filterDataByShop($this->_brand, $saleData);
		
		#改成Shop資料來自DB,避免閉店沒有關聯到(不用$groupShopList來取)
		$baseData = collect($saleData)->map(function($item, $key) {
			$item['shopType'] 		= $item['typeId'];
			$item['shopTypeName']	= $item['typeName'];
			$item['areaId'] 		= AreaLib::toId($item['areaId']);
			$item['areaName']		= (Area::tryFrom($item['areaId']))->label();
			
			unset($item['typeId'], $item['typeName']);
			
			return $item; 
		});
		
		#補全未有銷售的門店資料(closedown = 0)
		$saleShopIds = $baseData->pluck('shopId')->unique()->values()->toArray();
		$fillShops = $this->_getFillShop($saleShopIds);
		
		#重建
		$fillShops = $fillShops->map(function($item, $key) {
			$temp['shopId'] 		= $item['shopId'];
			$temp['shopName'] 		= $item['shopName'];
			$temp['shopType'] 		= $item['typeId'];
			$temp['shopTypeName']	= $item['typeName'];
			$temp['areaId'] 		= AreaLib::toId($item['areaId']);
			$temp['areaName']		= (Area::tryFrom($temp['areaId']))->label();
			$temp['saleDate'] 		= $this->_statistics['endDate'];
			$temp['amount'] 		= 0;
			
			return $temp;
		});
		
		$baseData = $baseData->merge($fillShops)->sortBy('areaId')->toArray();
		
		return $baseData;
	}
	
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($baseData)
	{
		try
		{
			#1.Header(共用)
			$dayList = $this->_buildDayList();
			
			#2.區域彙每日總營收
			$areaData = $this->_parsingByArea($baseData);
			$this->_buildOutputByArea($areaData, $dayList);
			
			#3.店別每日營收
			$shopData = $this->_parsingByShop($baseData);
			$this->_buildOutputByShop($shopData, $dayList);
			
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* 計算日期天數
	 * @params: 
	 * @return: array
	 */
	private function _buildDayList()
	{
		$st 		= Carbon::create($this->_statistics['startDate']);
		$end 		= Carbon::create($this->_statistics['endDate']);
		$period 	= CarbonPeriod::create($st, $end);
		
		$dateList = [];

		foreach ($period as $date) 
		{
			$date = $date->format('Y-m-d');
			$dateList[$date] = $date;
		}
		
		return $dateList;
	}
	
	
	/* 區域營收By Day
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByArea($baseData)
	{
		/*
		"areaId" => [
			"areaName" =>"大台北區"
			"shopCount" => 11
			"dayAmount" => [
				"2026-03-18" => 101
				"2026-03-19" => 22208
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
		
		#這裏也是By day
		$result = collect($baseData)->groupBy('areaId')->map(function($items, $key) {
			$temp['areaName']		= $items->pluck('areaName')->first();
			$temp['shopCount']		= $items->pluck('shopId')->unique()->count(); #店家數
			
			#整理Amount成Daily形式
			$temp['dayAmount'] = $items->groupBy('saleDate')->mapWithKeys(function($items, $date) {
				return [$date => round($items->pluck('amount')->sum())];
			})->toArray();
			
			return $temp;
		})->sortKeys()->toArray();
		
		#區域總計
		$result['total']['areaName'] 	= '總計'; 
		$result['total']['shopCount'] 	= collect($baseData)->pluck('shopId')->unique()->count(); 
		$result['total']['dayAmount'] 	= collect($baseData)->groupBy('saleDate')->mapWithKeys(function($items, $date) {
			return [$date => round($items->pluck('amount')->sum())];
		})->toArray();
		
		return $result;
	}
	
	/* 區域每日營收
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _buildOutputByArea($areaData, $dayList)
	{
		$prefix = [
			'areaName'	=> '區域', 
			'shopCount' => '門店數', 
		];
		
		$header = array_merge($prefix, $dayList);
		$this->_statistics['area']['header'] = $header;
		
		#'區域', '門店數'
		foreach($areaData as $key => $item)
		{
			$row = [];
			$row['areaName'] 	= $item['areaName'];	
			$row['shopCount'] 	= $item['shopCount'];
			
			foreach($dayList as $date)
			{
				$row[$date] = data_get($item, "dayAmount.{$date}", 0);
			}
			
			$this->_statistics['area']['data'][] = $row; 
		}
	}
	
	/* 店別每日營收
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByShop($baseData)
	{
		/* Output: 20260510改併成一個array,也方便export
		[
		330002 => [
			"shopName" => "御廚豐原向陽店"
			"areaName" => "中彰投區"
			"dayAmount" =>  [
				"2025-09-15" => 666.0
				"2025-09-14" => 777.0
			]
		]
		*/
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		#'區域', '門店代號', '門店名稱', '類型'
		$result = collect($baseData)->groupBy('shopId')->map(function($items, $key) {
			$temp['shopId'] 		= $items->pluck('shopId')->first();
			$temp['shopName'] 		= $items->pluck('shopName')->first();
			$temp['shopTypeName'] 	= $items->pluck('shopTypeName')->first();
			$temp['areaId'] 		= $items->pluck('areaId')->first();
			$temp['areaName'] 		= $items->pluck('areaName')->first();
			
			#整理Amount成Daily形式
			$temp['dayAmount'] = $items->mapWithKeys(function($item, $key){
				if (! empty($item['saleDate']))
					return [$item['saleDate'] => round($item['amount'])];
				else
					return [];
			})->toArray();
			
			return $temp; 
		})->sortBy('areaId')->toArray();
		
		$result['total']['shopName'] 	= '總計'; 
		$result['total']['shopTypeName']= ''; 
		$result['total']['shopId'] 		= ''; 
		$result['total']['areaName'] 	= ''; 
		$result['total']['dayAmount']	= collect($baseData)->groupBy('saleDate')->mapWithKeys(function($items, $date) {
				return [$date => round($items->pluck('amount')->sum())];
			})->toArray();
		
		return $result;
	}
	
	/* 店別每日營收
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _buildOutputByShop($shopData, $dayList)
	{
		$prefix = [
			'areaName'		=> '區域', 
			'shopId'		=> '門店代號', 
			'shopName'		=> '門店名稱', 
			'shopTypeName'	=> '類型',
		];
		
		$header = array_merge($prefix, $dayList);
		$this->_statistics['shop']['header']= $header;
		
		#'區域', '門店代號', '門店名稱', '類型'
		foreach($shopData as $key => $item)
		{
			$row = [];
			$row['areaName'] 	= $item['areaName'];	
			$row['shopId'] 		= $item['shopId'];
			$row['shopName']	= $item['shopName'];
			$row['shopTypeName']= $item['shopTypeName'];
			
			foreach($dayList as $date)
			{
				$row[$date] = data_get($item, "dayAmount.{$date}", 0);
			}
			
			$this->_statistics['shop']['data'][] = $row; 
		}
	}
	
	/* Export data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function export($token)
	{
		#取資料邏輯共用
		$cacheKey = hex2bin($token);
		
		if (! Cache::has($cacheKey))
			return ResponseLib::initialize()->fail('資料已過期，請重新查詢後下載'); #暫不做重查的動作
		
		$currentUser = AppManager::getCurrentUser();
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export daily revenue data-?'));
		
		try
		{
			$sourceData = Cache::get($cacheKey);
			
			#Build export data for sheets
			$export['區域彙總'] 		= $this->_buildExportArea($sourceData['area']);
			$export['店別明細'] 		= $this->_buildExportShop($sourceData['shop']);
			
			#Write export to file
#			$fileName = Str::replace(':', '_', $cacheKey); 
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['startDate'], $sourceData['endDate']], '?_門店營收_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			#$writer->openToBrowser($fileName);
			$writer->openToFile($filePath);
			
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($sheetName == '區域彙總') ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data);
					$writer->addRow($row);
				}
			}
			
			$writer->close();
			return ResponseLib::initialize($fileName)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize('檔案下載失敗，請重新查詢')->fail();
		}
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportArea($srcData)
	{
		$header 	= $srcData['header'];
		$areaData 	= $srcData['data'];
		
		$export = [];
		$export[] = $header;
		#$export[] = array_merge(['區域', '店家數'], $header);
		
		#每個product要一個sheet
		foreach($areaData as $key => $area)
		{
			if (empty($area))
				continue;
			
			$row = [];
			#$row[] = $area['areaName'];
			#$row[] = $area['shopCount'];
				
			#要按Header的順序
			foreach(array_keys($header) as $colKey)
			{
				$row[] = data_get($area, "{$colKey}", 0);
			}
			
			$export[] = $row;
		}
		
		return $export;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportShop($srcData)
	{
		$header 	= $srcData['header'];
		$shopData 	= $srcData['data'];
		
		$export[] = $header;
		#$export[] = array_merge(['區域', '門店代號', '門店名稱', '類型'], $header);
		
		foreach($shopData as $shopId => $shop)
		{
			$row = [];
			/* $row[] = $shop['areaName'];
			$row[] = $shopId;
			$row[] = $shop['shopName'];
			$row[] = $shop['shopTypeName']; */
			
			foreach(array_keys($header) as $colKey)
			{
				$row[] = data_get($shop, "{$colKey}", '');
			}
			
			$export[]= $row;
		}
		
		return $export;
	}
}
