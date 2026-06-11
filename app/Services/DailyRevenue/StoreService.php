<?php

namespace App\Services\DailyRevenue;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Repositories\DailyRevenueRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Sales\AreaLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class StoreService
{
	private $_statistics	= [];
   
	public function __construct(protected DailyRevenueRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'brandCode'		=> '',
			'modeType'		=> '',
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'shop' 			=> [],
			'area' 			=> [],
			'exportName'	=> '',
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function analysis($params)
	{
		try
		{
			$this->_prepareData($params);
			
			$this->_outputReport($params);
		
			$this->_generateStatistics($params);
			
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']	= $params->brand->value;
		$this->_statistics['brandCode']	= $params->brand->code();
		$this->_statistics['modeType']	= $params->type;
		$this->_statistics['startDate'] = $params->stDate;
		$this->_statistics['endDate']	= $params->endDate;
		$this->_statistics['shop']		= $params->shop;
		$this->_statistics['area']		= $params->area;
		
		#無值不cache
		if (! empty(Arr::flatten($this->_statistics['shop'])))
		{
			$this->_statistics['exportToken'] = bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get all shops with area permission
			$params->allShopList 	= PosManager::getAllStores($params->brand, $params->userAreaIds, $params->shopType, $params->shopName); #all shops
			$params->activeShopList = PosManager::getActiveStores($params->brand, $params->userAreaIds, $params->shopType, $params->shopName); #only active shops
			
			#2. Get data from DB
			$saleData = $this->_getDataFromDB($params);
			
			#3.build to base data
			$this->_buildBaseData($params, array_filter($saleData)); 
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get buy good data
	 * @params: fluent
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$shopType 		= $params->shopType;
			$shopName 		= $params->shopName;
			$userAreaIds 	= $params->userAreaIds;
			
			$result = $this->_repository->getSale00Data($brand, $userAreaIds, $stDate, $endDate, $shopType, $shopName);
			
			#去除shop id
			$result = collect($result)->map(function($item, $key){
				$item['shopName'] = Str::replace($item['shopId'], '', $item['shopName']);
				return $item;
			})->toArray();
				
			return $result;
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
	private function _buildBaseData($params, $saleData)
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
		$saleData = PosManager::filterExceptStore($params->brand, $saleData);
		
		$baseData = collect($saleData)->map(function($item, $key) {
			$temp['shopId'] 		= $item['shopId'];
			$temp['shopName'] 		= $item['shopName'];
			$temp['shopTypeName']	= $item['typeName'];
			$temp['areaId'] 		= AreaLib::toId($item['areaId']);
			$temp['areaName']		= (Area::tryFrom($temp['areaId']))->label();
			$temp['saleDate']		= (new Carbon($item['saleDate']))->format('Y-m-d');
			$temp['amount'] 		= $item['amount'];
			#發票金額 = amount OR totalSales + totalDischarge
			#實銷金額 = totalSales + totalExtra + totalDischarge
			$temp['totalSales']		= floatval($item['totalSales']) + floatval($item['totalDischarge']); #+ floatval($item['totalExtra']) 
			
			return $temp; 
		});
		
		#補全未有銷售的門店資料(closedown = 0)
		$saleShopIds = $baseData->pluck('shopId')->unique()->values()->toArray();
		$filloutShops = PosManager::getFillOutStore($params->activeShopList, $saleShopIds);
		
		#重建
		$filloutShops = $filloutShops->map(function($item, $key) use($params){
			$temp['shopId'] 		= $item['shopId'];
			$temp['shopName'] 		= $item['shopName'];
			$temp['shopTypeName']	= $item['typeName'];
			$temp['areaId'] 		= $item['areaId'];
			$temp['areaName']		= $item['areaName'];
			$temp['saleDate'] 		= $params->endDate;
			$temp['amount'] 		= 0;
			$temp['totalSales']		= 0;
			
			return $temp;
		});
		
		$params->baseData = $baseData->merge($filloutShops)->sortBy('areaId')->toArray();
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.Header(共用)
			$this->_buildDayRange($params);
			
			#2.By區域
			$this->_parsingByArea($params);
			
			#3.By店別
			$this->_parsingByShop($params);
			
			return $params;
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
	private function _buildDayRange($params)
	{
		$st 		= Carbon::create($params->stDate);
		$end 		= Carbon::create($params->endDate);
		$period 	= CarbonPeriod::create($st, $end);
		
		$dateList = [];

		foreach ($period as $date) 
		{
			$dateString = $date->format('Y-m-d');
			$dateList[$dateString] = $dateString;
		}
		
		$params->dayRange = $dateList;
	}
	
	
	/* 區域營收By Day
	 * @params: array
	 * @return: array
	 */
	private function _parsingByArea($params)
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
		
		$params->set('area.header', []);
		$params->set('area.data', []);
		
		$baseData = $params->baseData;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		$header = ['areaName' => '區域', 'shopCount'	=> '門店數', 'dayAmount' => $params->dayRange];
		$params->set('area.header', $header);
		
		#這裏也是By day
		$result = collect($baseData)->groupBy('areaId')->map(function($items, $key) {
			$temp['areaName']		= $items->pluck('areaName')->first();
			$temp['shopCount']		= $items->pluck('shopId')->unique()->count(); #店家數
			
			#整理Amount成Daily形式
			$temp['dayAmount'] = $items->groupBy('saleDate')->mapWithKeys(function($items, $date) {
				#因amount會有0的狀況
				$amount			= $items->pluck('amount')->sum();
				$totalSales		= $items->pluck('totalSales')->sum();
				$finalAmount 	= empty($amount) ? $totalSales : $amount;
				
				return [$date => round($finalAmount, 2)];
			})->filter(function($item, $key){
				return $key > 0;
			})->toArray();
			
			return $temp;
		})->sortKeys()->toArray();
		
		#區域總計
		$result['total']['areaName'] 	= '總計'; 
		$result['total']['shopCount'] 	= collect($baseData)->pluck('shopId')->unique()->count(); 
		$result['total']['dayAmount'] 	= collect($baseData)->groupBy('saleDate')->mapWithKeys(function($items, $date) {
			$amount		= $items->pluck('amount')->sum();
			$totalSales	= $items->pluck('totalSales')->sum();
			$finalAmount= empty($amount) ? $totalSales : $amount;
			
			return [$date => round($finalAmount)];
		})->filter(function($item, $key){
			return $key > 0;
		})->toArray();
		
		$params->set('area.data', $result);
	}
	
	/* 店別每日營收
	 * @params: array
	 * @return: array
	 */
	private function _parsingByShop($params)
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
		
		$params->set('shop.header', []);
		$params->set('shop.data', []);
		
		$baseData = $params->baseData;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		$header = ['areaName' => '區域', 'shopId' => '門店代號', 'shopName' => '門店名稱', 'shopTypeName' => '類型',
					'dayAmount' => $params->dayRange
				];
		$params->set('shop.header', $header);
		
		#Sum已在DB計算, 這裏只要format output
		$result = collect($baseData)->groupBy('shopId')->map(function($items, $key) {
			
			$temp['shopId'] 		= $items->pluck('shopId')->first();
			$temp['shopName'] 		= $items->pluck('shopName')->first();
			$temp['shopTypeName'] 	= $items->pluck('shopTypeName')->first();
			$temp['areaId'] 		= $items->pluck('areaId')->first();
			$temp['areaName'] 		= $items->pluck('areaName')->first();
			
			#整理Amount成Daily形式
			$temp['dayAmount'] = $items->groupBy('saleDate')->mapWithKeys(function($items, $key){
				$saleDate = $items->pluck('saleDate')->first();
				
				if (empty($saleDate))
					return [];
				
				$amount			= $items->pluck('amount')->sum();
				$totalSales		= $items->pluck('totalSales')->sum();
				$finalAmount 	= empty($amount) ? $totalSales : $amount;
				
				return [$saleDate => round($finalAmount, 2)];

			})->filter(function($item, $key){
				return $key > 0;
			})->toArray();
			
			return $temp; 
		})
		->values()
		->sortBy('areaId')
		->toArray();
		
		$result['total']['shopId'] 		= ''; 
		$result['total']['shopName'] 	= '總計'; 
		$result['total']['shopTypeName']= ''; 
		$result['total']['areaName'] 	= ''; 
		$result['total']['dayAmount']	= collect($baseData)->groupBy('saleDate')->mapWithKeys(function($items, $date) {
			$amount		= $items->pluck('amount')->sum();
			$totalSales	= $items->pluck('totalSales')->sum();
			$finalAmount= empty($amount) ? $totalSales : $amount;
			
			return [$date => round($finalAmount, 2)];
		})->toArray();
		
		$params->set('shop.data',  $result);
	}
	
	/*************** 匯出 ***************/
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export['區域彙總'] = $this->_buildExportArea($sourceData['area']);
            $export['店別明細'] = $this->_buildExportShop($sourceData['shop']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['startDate'], $sourceData['endDate']], '?_門店營收_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$index = 0;
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data);
					$writer->addRow($row);
				}
				$index++;
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
		$export = [];
		$export[] = Arr::flatten($srcData['header']);
		
		#Area data
		foreach($srcData['data'] as $key => $area)
		{
			if (empty($area))
				continue;
			
			$row = [];
			$row[] = $area['areaName'];
			$row[] = $area['shopCount'];
				
			#要按Header的順序
			foreach($srcData['header']['dayAmount'] as $colKey)
			{
				$amount = data_get($area, "dayAmount.{$colKey}", 0);
				$row[] = Number::currency($amount, precision: 0);
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
		$export[] = Arr::flatten($srcData['header']);
		
		foreach($srcData['data'] as $shopId => $shop)
		{
			$row = [];
			$row[] = $shop['areaName'];
			$row[] = $shop['shopId'];
			$row[] = $shop['shopName'];
			$row[] = $shop['shopTypeName'];
			
			foreach($srcData['header']['dayAmount'] as $colKey)
			{
				$amount = data_get($shop, "dayAmount.{$colKey}", 0);
				$row[] = Number::currency($amount, precision: 0);
			}
			
			$export[]= $row;
		}
		
		return $export;
	}
}
