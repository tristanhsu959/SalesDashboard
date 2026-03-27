<?php

namespace App\Services\Shipments;

use App\Facades\AppManager;
use App\Repositories\ShipmentsRepository;
use App\Libraries\ShopLib;
use App\Libraries\ResponseLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
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

#partial Service
class ShipmentsByNameService
{
	const MODE = 'Name';
	
	private $_statistics	= [];
   
	public function __construct(protected ShipmentsRepository $_repository)
	{
		$this->_statistics = [
			'mode'			=> self::MODE,
			'brandId'		=> '', #export
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'productIds'	=> [],
			'header'		=> [],
			'shop' 			=> [],
			'factory'		=> [],
			'exportName'	=> '', #export
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($brand, $function, $searchStDate, $searchEndDate, $searchProductName)
	{
		try
		{
			#Check cache
			$searchEndDate = empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
			$cacheKey = implode(':', [$function->value, $searchProductName, $searchStDate, $searchEndDate]);
			
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get shipments data by name from cache');
				
				$statistics = Cache::get($cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get shipments data by name from db');
				
				$this->_statistics['brandId']	= $brand->value; 
				#儲存頁面計算天數用日期
				$this->_statistics['startDate'] = (new Carbon($searchStDate))->format('Y-m-d'); 
				$this->_statistics['endDate'] 	= (new Carbon($searchEndDate))->format('Y-m-d');
				$this->_statistics['exportName']= $searchProductName;
				
				#執行統計
				$response = $this->_analysisStatisticsData($brand, $searchProductName);
				
				#無值不cache
				if (! empty($this->_statistics['shop']))
				{
					$this->_statistics['exportToken'] = bin2hex($cacheKey); #hex2bin
					Cache::put($cacheKey, $this->_statistics, now()->addMinutes(1));
				}
				
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
	 * @return: array
	 */
	private function _analysisStatisticsData($brand, $productName)
	{
		try
		{
			#1. Calc time
			$stDate		= (new Carbon($this->_statistics['startDate']))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($this->_statistics['endDate']))->format('Y-m-d 23:59:59');
			
			#2. Get params
			$productIds = $this->_getParams($brand, $productName);
			$this->_statistics['productIds'] = $productIds;
			
			#3. Get store list
			$storeList = $this->_getStoreList($brand->value);
			
			#4. Get Order data
			$orderData = $this->_getDataFromDB($brand, $stDate, $endDate, $productIds);
			
			#5. Build base data
			#$baseData = $this->_buildBaseData(array_filter($orderData));
			
			return $this->_outputReport($orderData);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	/* ====================== 主流程 End ====================== */
	
	
	/* 取ErpNo及條件
	 * @params: int
	 * @return: array
	 */
	private function _getParams($brand, $productName)
	{
		try
		{
			$ids = $this->_repository->getProductIdByName($brand->value, $productName);
			
			if (empty($ids))
				throw new Exception('無此產品名稱');
			
			return $ids;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析產品參數發生錯誤');
		}
	}
	
	/* Get order data
	 * @params: enums
	 * @params: date
	 * @params: date
	 * @params: array
	 * @return: array
	 */
	private function _getStoreList($brandId)
	{
		try
		{
			$stores = $this->_repository->getStoreList($brandId);
			
			#To key-value
			$stores = collect($stores)->mapWithKeys(function($item, $key){
				return [$item['storeId'] => $item];
			})->toArray();
				
			return $stores;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* Get order data
	 * @params: enums
	 * @params: date
	 * @params: date
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromDB($brand, $stDate, $endDate, $productIds)
	{
		/*0 => array:9 [
			"expectedDate" => "2026-03-25"
			"area" => "中彰投-八方"
			"storeId" => "156"
			"factoryNo" => "TW_KH"
			"factoryName" => "高雄工廠"
			"qty" => "2"
			"amount" => "500.000000"
			"productName" => "紅燒帶骨牛小排調理包"
			"erpNo" => "PR00313063"
		  ]
		*/
	
		try
		{
			$orderData = $this->_repository->getOrderDataByProductId($brand->value, $stDate, $endDate, $productIds);
				
			return $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨資料失敗');
		}
	}
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($orderData)
	{
		/*
		[
		330002 => [
			"shopId" => "350001"
			"saleDate" => "2025-09-22"
			"qty" => "4"
			"shopName" => "御廚竹南博愛店"
			"areaId" => 3
			"areaName" => "桃竹苗區"
		]
		"expectedDate" => "2026-03-25"
			"area" => "中彰投-八方"
			"storeId" => "156"
			"factoryNo" => "TW_KH"
			"factoryName" => "高雄工廠"
			"qty" => "2"
			"amount" => "500.000000"
			"productName" => "紅燒帶骨牛小排調理包"
			"erpNo" => "PR00313063"
		*/
		
		
		$baseData = collect($orderData)->groupBy('expectedDate')->map(function($items, $key) {
			dd($items);
			$temp['month'] = Carbon::parse($items['expectedDate'])->format('Y-m');
			dd($temp);
			$shop = $groupShopList->get($item['shopId']);
			
			$item['shopName'] 	= $shop->pluck('shopName')->first();
			$item['areaId'] 	= Area::toId($shop->pluck('areaId')->first());
			$item['areaName']	= (Area::tryFrom($item['areaId']))->label();

			return $item; 
		});
		
		#補全未有銷售的門店資料(closedown = 0)
		$saleShopIds = $baseData->pluck('shopId')->unique()->values()->toArray();
		
		$filterShops = $groupShopList->filter(function($item, $key) use($saleShopIds){
			#過濾出無銷售且為active門店
			return ! in_array($item->pluck('shopId')->first(), $saleShopIds) && ($item->pluck('closedown')->first() == 0);
		});
		
		#重建
		$filterShops = $filterShops->map(function($item, $key) {
			$temp['shopId'] 	= $item->pluck('shopId')->first();
			$temp['saleDate'] 	= $this->_statistics['endDate'];
			$temp['qty'] 		= 0;
			$temp['shopName'] 	= $item->pluck('shopName')->first();
			$temp['areaId'] 	= Area::toId($item->pluck('areaId')->first());
			$temp['areaName']	= (Area::tryFrom($temp['areaId']))->label();
			
			return $temp;
		});
		
		$baseData = $baseData->merge($filterShops)->toArray();
		
		return $baseData;
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($orderData)
	{
		try
		{
			#1.計算查詢範圍總天數 (use Date not DateTime)
			$this->_statistics['header'] = $this->_buildHeader($orderData);
			
			#2.區域彙總
			$this->_statistics['factory'] = $this->_parsingByFactory($orderData);
			dd($this->_statistics['factory']);
			
			#2.店別每日銷售
			$this->_statistics['shop'] = $this->_parsingByShop($orderData);
			
							
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
	private function _buildHeader($orderData)
	{
		$st 		= Carbon::create($this->_statistics['startDate']);
		$end 		= Carbon::create($this->_statistics['endDate']);
		$header 	= [];
		
		#By day
		$period 	= CarbonPeriod::create($st, $end);
		foreach ($period as $date) 
		{
			$header['days'][] = $date->format('Y-m-d');
		}
		
		#By month
		$st = Carbon::parse($this->_statistics['startDate'])->startOfMonth();
		$end = Carbon::parse($this->_statistics['endDate'])->startOfMonth();
		
		$period = CarbonPeriod::create($st, '1 month', $end);
		foreach ($period as $date) 
		{
			$header['months'][] = $date->format('Y-m');
		}
		
		#product
		$product = collect($orderData)->mapWithKeys(function($items, $key){
			return [$items['erpNo'] => $items['productName']];
		})->toArray();
		
		$header['products'] = $product;
		
		return $header;
	}
	
	/* 依工廠
	 * @params: array
	 * @return: array
	 */
	private function _parsingByFactory($orderData)
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
		array:9 [
			"expectedDate" => "2026-03-25"
			"area" => "中彰投-八方"
			"storeId" => "156"
			"factoryNo" => "TW_KH"
			"factoryName" => "高雄工廠"
			"qty" => "2"
			"amount" => "500.000000"
			"productName" => "紅燒帶骨牛小排調理包"
			"erpNo" => "PR00313063"
		  ]
		*/
		if (empty($orderData))
			return [];
		
		$result = collect($orderData)->groupBy('erpNo')->map(function($items, $key) {
			$temp = $items->groupBy('factoryNo')->map(function($items, $key) {
				$day = $items->groupBy('expectedDate')->map(function($items, $key) {
					$temp['qty'] 	= $items->pluck('qty')->sum();
					$temp['amount'] = $items->pluck('amount')->sum();
					return $temp;
				});
				
				$month = $items->groupBy(function ($item) {
					return substr($item['expectedDate'], 0, 7); 
				})->map(function ($group) {
					$temp['qty'] 	= $group->pluck('qty')->sum();
					$temp['amount'] = $group->pluck('amount')->sum();
					
					return $temp;
				});
				
				return $day->merge($month)->toArray();
			});
			
			/* $temp['areaName']		= $items->pluck('areaName')->first();
			$temp['shopCount']		= $items->pluck('shopId')->unique()->count(); #店家數
			$temp['totalQty'] 		= intval($items->pluck('qty')->sum()); #區域銷售總量
			$temp['avgDayQty'] 		= round($temp['totalQty'] / $totalDays, 1); 		#區域平均日銷售量: 區域銷售總量/天數
			$temp['avgShopQty'] 	= round($temp['totalQty'] / $temp['shopCount'], 1); #區域每店平均銷量: 區域銷售總量/店家數
			$temp['avgDayShopQty'] 	= round($temp['totalQty'] / $totalDays / $temp['shopCount'], 1); 	#區域每店平均日銷量: 區域銷售總量/店家數/天數
			 */
			return $temp;
		})->sortKeys()->toArray();
		
		dd($result);
		#這裏是依header
		$result['total']['shopCount'] 		= collect($result)->pluck('shopCount')->sum(); 
		$result['total']['totalQty'] 		= collect($result)->pluck('totalQty')->sum();
		$result['total']['avgDayQty'] 		= round($result['total']['totalQty'] / $totalDays, 1);
		$result['total']['avgShopQty'] 		= round($result['total']['totalQty'] / $result['total']['shopCount'], 1); #totalQty / shopCount
		$result['total']['avgDayShopQty']	= round($result['total']['avgDayQty'] / $result['total']['shopCount'], 1); #avgDayQty / shopCount
		
		return $result;
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
			"shopId" => "420001"
			"shopName" => "御廚豐原向陽店"
			"areaId" => 4
			"areaName" => "中彰投區"
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
		
		$result = collect($baseData)->groupBy('shopId')->map(function($item, $key) use($totalDays) {
			#$temp['shopId']		= $item->pluck('shopId')->first();
			$temp['shopName'] 	= $item->pluck('shopName')->first();
			#$temp['areaId'] 	= $item->pluck('areaId')->first();
			$temp['areaName'] 	= $item->pluck('areaName')->first();
			
			$temp['dayQty'] = $item->mapWithKeys(function($item, $key){
				if (! empty($item['saleDate']))
					return [$item['saleDate'] => intval($item['qty'])];
				else
					return [];
			})->toArray();
			
			#計算=>銷售總量|平均銷售數量
			$temp['totalQty'] = array_sum($temp['dayQty']); #銷售量總和
			$temp['totalAvg'] = empty($temp['totalQty']) ? 0 : round($temp['totalQty'] / $totalDays, 1); #平均銷售數量:銷售量總和/天數
			
			return $temp; 
		})->sortKeys()->toArray();
		
		return $result;
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
		
		foreach($shopData as $shopId => $data)
		{
			$row = [];
			$row[] = $data['areaName'];
			$row[] = $shopId;
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
			foreach($shopList as $shopId => $data)
			{
				$row = [];
				$row[] = $data['areaName'];
				$row[] = $data['shopId'];
				$row[] = $data['shopName'];
				$row[] = $data['qty'];
				$row[] = $ranking + 1;
				
				$export[]= $row;
			}
		}
		
		return $export;
	}
}
