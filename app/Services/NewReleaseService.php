<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\NewReleaseRepository;
use App\Libraries\ShopLib;
use App\Libraries\ResponseLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

class NewReleaseService
{
	private $_statistics	= [];
   
	public function __construct(protected NewReleaseRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'productName'	=> '', #export
			'exportToken'	=> '', #export
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'dayHeader'		=> [],
			'shop' 			=> [],
			'area' 			=> [],
			'top' 			=> [],
			'last' 			=> [],
		];
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
	 * @params: string
	 * @return: string
	 */
	public function parsingFunction($brand)
	{
		return match ($brand) 
		{
			Brand::BAFANG	=> Functions::BF_NEW_RELEASE, 
			Brand::BUYGOOD	=> Functions::BG_NEW_RELEASE,
        };
	}
	
	/* 取新品設定by brand
	 * @params: int
	 * @return: string
	 */
	public function getNewItemOptions($brandId)
	{
		$result = $this->_repository->getNewItemOptions($brandId);
		$result = collect($result)->keyBy('id')->all();
		
		return $result;
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchNewItemId, $searchStDate, $searchEndDate)
	{
		try
		{
			#Check cache
			$functions = $this->parsingFunction($brand);
			$cacheKey = implode(':', [$functions->value, $searchNewItemId, $searchStDate, $searchEndDate]);
			
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get new release data from cache');
				$statistics = Cache::get($cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get new release data from db');
				
				$this->_statistics['brandId'] =	$brand->value; 
				$this->_statistics['exportToken'] = bin2hex($cacheKey); #hex2bin
				$response = $this->_analysisStatisticsData($brand, $searchNewItemId, $searchStDate, $searchEndDate);
				
				#成功
				Cache::put($cacheKey, $this->_statistics, now()->addMinutes(60));
				return ResponseLib::initialize($this->_statistics)->success();
			}
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* 取新品銷售統計
	 * @params: enums
	 * @params: integer
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _analysisStatisticsData($brand, $searchNewItemId, $searchStDate, $searchEndDate)
	{
		try
		{
			#1. Calc time
			$stDate		= (new Carbon($searchStDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($searchEndDate))->format('Y-m-d 23:59:59');
			
			#儲存頁面計算天數用日期
			$this->_statistics['startDate'] = (new Carbon($searchStDate))->format('Y-m-d'); #這裏只存日期
			$this->_statistics['endDate'] 	= (new Carbon($searchEndDate))->format('Y-m-d');
			
			#2. Get params
			list($productName, $primaryIds, $secondaryIds, $tastes) = $this->_getParams($searchNewItemId);
			$this->_statistics['productName'] = $productName;
			
			$currentUser = AppManager::getCurrentUser();
			$userAreaIds = $currentUser['roleArea']; #
					
			#3. Get all shops with area permission
			$shopList = $this->_getShopList($brand, $userAreaIds);
			
			#4. Get POS data
			$saleData = $this->_getDataFromDB($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes, $userAreaIds);
			
			#5. Build base data
			#會有false的無效array, 用array_filter去除
			$baseData = $this->_buildBaseData($shopList, array_filter($saleData));
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
	
	
	/* 取ErpNo及條件
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _getParams($searchNewItemId)
	{
		try
		{
			$productName = $this->_repository->getNameById($searchNewItemId);
			$tastes = $this->_repository->getTasteById($searchNewItemId);
			$result = $this->_repository->getErpNoById($searchNewItemId);
			
			$result = collect($result)->groupBy('isPrimary');
			$primaryIds		= $result[1]->pluck('erpNo')->toArray();
			$secondaryIds 	= empty($result[0]) ? [] : $result[0]->pluck('erpNo')->toArray();
			
			return [$productName, $primaryIds, $secondaryIds, $tastes];
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析查詢參數發生錯誤');
		}
	}
	
	/* 取店家並過濾區域權限
	 * @params: collection
	 * @return: array
	 */
	private function _getShopList($brand, $userAreaIds)
	{
		try
		{
			#會Filter區域權限
			$shopList = $this->_repository->getShopList($brand, $userAreaIds);
		
			return $shopList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料發生錯誤');
		}
	}
	
	/* Get main data & mapping data
	 * @params: date
	 * @params: date
	 * @params: array
	 * @params: array => product ids of BF
	 * @return: array
	 */
	private function _getDataFromDB($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes, $userAreaIds)
	{
		try
		{
			$cacheKey = implode(':', [$brand->code(), $stDate, $endDate]);
		
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get sales data from cache');
				return Cache::get($cacheKey);
			}
			else
			{
				$saleData = $this->_repository->getSaleRecords($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes, $userAreaIds);
				#$saleData = $this->_repository->getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes);
				Cache::put($cacheKey, $saleData, now()->addMinutes(120));
				Log::channel('appServiceLog')->info('Get sales data from DB');
				
				return $saleData;
			}
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS DB資料失敗');
		}
	}
	
	
	
	/* 先分組成可共用的基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($shopList, $saleData)
	{
		#要改成所有店家統計,以門店為基礎的資料
		$groupSaleData = collect($saleData)->groupBy('shopId');
		
		$baseData = collect($shopList)->groupBy('shopId')->map(function($item, $key) use($groupSaleData) {
			$temp['shopId']		= $item->pluck('shopId')->first();
			$temp['shopName'] 	= $item->pluck('shopName')->first();
			$temp['areaId'] 	= Area::toId($item->pluck('areaId')->first());
			
			$shopSalesData = data_get($groupSaleData, $temp['shopId'], FALSE);
			
			if ($shopSalesData)
			{
				$temp['dayQty'] = $shopSalesData->mapWithKeys(function($item, $key){
					return [$item['saleDate'] => $item['qty']];
				})->toArray();
			}
			else
				$temp['dayQty'] = [];
			
			return $temp; 
		})->toArray();
		
		return $baseData;
		
		/*
		[
		330002 => [
		  "shopId" => "330002"
		  "shopName" => "御廚桃園中山東店"
		  "area" => 1
		  "dayQty" =>  [
			"2025-09-15" => 6.0
			"2025-09-14" => 7.0
			]
		]
		*/
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
			#1.計算查詢範圍總天數 (use Date not DateTime)
			$this->_statistics['dayHeader'] = $this->_buildDayHeader();
			$totalDays = count($this->_statistics['dayHeader']);
			
			#2.店別每日銷售
			$this->_statistics['shop'] = $this->_parsingByShop($baseData, $totalDays);
				
			#3.區域彙總
			$this->_statistics['area'] = $this->_parsingByArea($baseData, $totalDays);
							
			#4.當日銷售前10名
			#5.當日銷售後10名
			list($this->_statistics['top'], $this->_statistics['last']) = $this->_parsingByRanking($baseData, $this->_statistics['endDate']);
			
			/***** Statistics End *****/
			return TRUE;
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
	private function _buildDayHeader()
	{
		$st 		= Carbon::create($this->_statistics['startDate']);
		$end 		= Carbon::create($this->_statistics['endDate']);
		$period 	= CarbonPeriod::create($st, $end);
		
		$dateList = [];

		foreach ($period as $date) 
		{
			$dateList[] = $date->format('Y-m-d');
		}
		
		return $dateList;
	}
	
	
	
	/* 店別每日銷售
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByShop($baseData, $totalDays)
	{
		/* Output
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
		$result = Arr::map($baseData, function($item, $key) use($totalDays) {
			$item['totalQty'] = array_sum($item['dayQty']); #銷售量總和
			$item['totalAvg'] = round($item['totalQty'] / $totalDays, 1); #平均銷售數量:銷售量總和/天數
			return $item;
		});
		
		$result = Arr::sort($result);
		
		return $result;
	}
	
	/* 區域彙總
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByArea($baseData, $totalDays)
	{
		/*
		"areaId" => [
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
		$result = $collection->groupBy('areaId')->map(function($item, $key) use($totalDays) {
			$temp['shopCount']		= count($item); #店家數
			$temp['totalQty'] 		= intval($item->pluck('dayQty')->flatten()->sum()); #區域銷售總量
			$temp['avgDayQty'] 		= round($temp['totalQty'] / $totalDays, 1); 		#區域平均日銷售量: 區域銷售總量/天數
			$temp['avgShopQty'] 	= round($temp['totalQty'] / $temp['shopCount'], 1); 		#區域每店平均銷量: 區域銷售總量/店家數
			$temp['avgDayShopQty'] 	= round($temp['totalQty'] / $totalDays / $temp['shopCount'], 1); 	#區域每店平均日銷量: 區域銷售總量/店家數/天數
			
			return $temp;
		})->sortKeys()->toArray();
		
		#重排區域的順序以保持顯示一致(系統跑會依抓到資料的順序)
		/* $result['大台北區'] 	= data_get($data, Area::TAIPEI->value, []);
		$result['宜蘭區'] 	= data_get($data, Area::YILAN->value, []);
		$result['桃竹苗區'] 	= data_get($data, Area::TCM->value, []);
		$result['中彰投區'] 	= data_get($data, Area::CCT->value, []);
		$result['雲嘉南區'] 	= data_get($data, Area::YCN->value, []);
		$result['大高雄區'] 	= data_get($data, Area::KAOHSIUNG->value, []);
		 */
		
		$result['total']['shopCount'] 	= collect($result)->pluck('shopCount')->sum(); 
		$result['total']['totalQty'] 	= collect($result)->pluck('totalQty')->sum();
		$result['total']['avgDayQty'] 	= collect($result)->pluck('avgDayQty')->sum();
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->displayName, $cacheKey], '[?]Export new release data-?'));
		
		try
		{
			$sourceData = Cache::get($cacheKey);
			
			#Build export data for sheets
			$export['區域彙總'] 		= $this->_buildExportArea($sourceData['area']);
			$export['店別明細'] 		= $this->_buildExportShop($sourceData['dayHeader'], $sourceData['shop']);
			$export['當日銷售前10名'] = $this->_buildExportRanking($sourceData['endDate'], $sourceData['top']);
			$export['當日銷售後10名']	= $this->_buildExportRanking($sourceData['endDate'], $sourceData['last']);
			
			#Write export to file
#			$fileName = Str::replace(':', '_', $cacheKey); 
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['productName'], $sourceData['startDate'], $sourceData['endDate']], '?_新品_?_?_?.xlsx');
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
	private function _buildExportArea($areaData)
	{
		$export[] = ['區域', '店家數', '銷售總量', '平均日銷售量', '每店平均銷量', '每店平均日銷量'];
		
		foreach($areaData as $areaId => $data)
		{
			$areaName = ($areaId == 'total') ? 'Total' : Area::tryFrom(intval($areaId))->label();
			
			$row = [];
			$row[] = $areaName;
			$row[] = $data['shopCount'];
			$row[] = $data['totalQty'];
			$row[] = $data['avgDayQty'];
			$row[] = $data['avgShopQty'];
			$row[] = $data['avgDayShopQty'];
			
			$export[]= $row;
		}
		
		return $export;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportShop($header, $shopData)
	{
		$export[] = array_merge(['區域', '門店代號', '門店名稱'], $header, ['銷售總量', '平均銷售數量']);
		
		foreach($shopData as $data)
		{
			$areaName = Area::tryFrom(intval($data['areaId']))->label();
			
			$row = [];
			$row[] = $areaName;
			$row[] = $data['shopId'];
			$row[] = $data['shopName'];
			
			foreach($header as $date)
			{
				$row[] = data_get($data, "dayQty.{$date}", 0);
			}
			
			$row[] = $data['totalQty'];
			$row[] = $data['totalAvg'];
			
			$export[]= $row;
		}
		
		return $export;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportRanking($targeDate, $rankingData)
	{
		$export[] = array_merge(['區域', '門店代號', '門店名稱'], [$targeDate], ['排名']);
		
		foreach($rankingData as $ranking => $shopList)
		{
			#同一排名會有重複
			foreach($shopList as $data)
			{
				$areaName = Area::tryFrom(intval($data['areaId']))->label();
			
				$row = [];
				$row[] = $areaName;
				$row[] = $data['shopId'];
				$row[] = $data['shopName'];
				$row[] = data_get($data, "dayQty.{$targeDate}", 0);
				$row[] = $ranking;
				
				$export[]= $row;
			}
		}
		
		return $export;
	}
}
