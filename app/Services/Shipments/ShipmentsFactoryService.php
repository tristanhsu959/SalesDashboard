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
class ShipmentsFactoryService
{
	const MODE = 'Name';
	
	private $_statistics	= [];
   
	public function __construct(protected ShipmentsRepository $_repository)
	{
		$this->_statistics = [
			'modeType'		=> '',
			'modeCalc'		=> '',
			#'modeUnit'		=> '',
			'brandId'		=> '', #export
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'productIds'	=> [],
			'header'		=> [],
			'data'			=> [],
			'exportName'	=> '', #export
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: int
	 * @params: date
	 * @params: date
	 * @params: array
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function analysis($brandId, $searchStDate, $searchEndDate, $productIds, $searchType, $searchCalc)
	{
		try
		{
			#Check cache
			$searchEndDate = empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
			
			$this->_statistics['modeType']	= $searchType;
			$this->_statistics['modeCalc']	= $searchCalc; 
			#$this->_statistics['modeUnit']	= $searchUnit; 
			$this->_statistics['brandId']	= $brandId; 
			$this->_statistics['startDate'] = (new Carbon($searchStDate))->format('Y-m-d'); 
			$this->_statistics['endDate'] 	= (new Carbon($searchEndDate))->format('Y-m-d');
			$this->_statistics['productIds']= $productIds;
			
			#執行統計
			$this->_analysisStatisticsData();
				
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* 取出貨統計By工廠
	 * @params: integer
	 * @params: array
	 * @return: array
	 */
	private function _analysisStatisticsData()
	{
		try
		{
			#1. Calc time
			$stDate		= (new Carbon($this->_statistics['startDate']))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($this->_statistics['endDate']))->format('Y-m-d 23:59:59');
			
			#2. Get Order data
			$orderData = $this->_getDataFromDB();
			
			return $this->_outputReport($orderData);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	/* ====================== 主流程 End ====================== */
	
	/* Get order data
	 * @params: enums
	 * @params: date
	 * @params: date
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromDB()
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
			$brandId 	= $this->_statistics['brandId'];
			$stDate		= $this->_statistics['startDate'];
			$endDate	= $this->_statistics['endDate'];
			$productIds	= $this->_statistics['productIds'];
			
			$orderData = $this->_repository->getOrderDataByProductId($brandId, $stDate, $endDate, $productIds);
			
			return $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨資料失敗');
		}
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($orderData)
	{
		try
		{
			#1.計算查詢範圍總天數 (use Date not DateTime)
			$this->_statistics['header'] = $this->_buildHeader($orderData);
			
			#2.By工廠
			$this->_statistics['data'] = $this->_parsingByFactory($orderData);
			
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
		$modeCalc 	= $this->_statistics['modeCalc'];
		$header 	= [];
		
		if ($modeCalc == 'day')
		{
			#By day
			$period 	= CarbonPeriod::create($st, $end);
			foreach ($period as $date) 
			{
				$header['dateList'][] = $date->format('Y-m-d');
			}
		}
		else
		{
			#By month
			$st = Carbon::parse($this->_statistics['startDate'])->startOfMonth();
			$end = Carbon::parse($this->_statistics['endDate'])->startOfMonth();
			
			$period = CarbonPeriod::create($st, '1 month', $end);
			foreach ($period as $date) 
			{
				$header['dateList'][] = $date->format('Y-m');
			}
		}
		
		#productList
		$header['productList']  = collect($orderData)->mapWithKeys(function($items, $key){
			return [$items['erpNo'] => $items['productName']];
		})->toArray();
		
		$header['factoryList'] = $this->_getFactoryList();

		return $header;
	}
	
	/* Get order data
	 * @params: enums
	 * @params: date
	 * @params: date
	 * @params: array
	 * @return: array
	 */
	private function _getFactoryList()
	{
		try
		{
			$brandId = $this->_statistics['brandId'];
			$factory = $this->_repository->getFactoryList($brandId);
			
			#To key-value
			$factory = collect($factory)->mapWithKeys(function($item, $key){
				return [$item['factoryNo'] => $item['factoryName']];
			})->toArray();
			
			return $factory;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* 依工廠
	 * @params: array
	 * @return: array
	 */
	private function _parsingByFactory($orderData)
	{
		/*
		"PR00313063" => array:2 [
			"TW_KH" => array:2 [
				"2026-03-25" => array:1 [
					"qty" => 116
				]
				"2026-03-26" => array:1 []
			]
			"TW_TP" => array:2 []
		]
		*/
		if (empty($orderData))
			return [];
		
		$modeCalc = $this->_statistics['modeCalc'];
		
		$result = collect($orderData)->groupBy('erpNo')->map(function($items, $key) use($modeCalc) {
			$temp = $items->groupBy('factoryNo')->map(function($items, $key) use($modeCalc) {
				
				if ($modeCalc == 'day')
				{
					$day = $items->groupBy('expectedDate')->map(function($items, $key) {
						$temp['qty'] = $items->pluck('qty')->sum();
						/* if ($modeUnit == 'qty')
							$temp['qty'] 	= $items->pluck('qty')->sum();
						else
							$temp['amount'] = $items->pluck('amount')->sum(); */
						
						return $temp;
					});
					
					return $day->toArray();	
				}
				
				if ($modeCalc == 'month')
				{
					$month = $items->groupBy(function ($item) {
						return substr($item['expectedDate'], 0, 7); 
					})->map(function ($group) {
						$temp['qty'] = $group->pluck('qty')->sum();
						/* if ($modeUnit == 'qty')
							$temp['qty'] 	= $group->pluck('qty')->sum();
						else
							$temp['amount'] = $group->pluck('amount')->sum(); */
						
						return $temp;
					});
					
					return $month->toArray();	
				}
				
				#return $day->merge($month)->toArray();
			});
			
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
