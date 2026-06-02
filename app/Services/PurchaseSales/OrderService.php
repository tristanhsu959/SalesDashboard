<?php

namespace App\Services\PurchaseSales;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\LegacyManager;
use App\Repositories\PurchaseSalesRepository;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class OrderService
{
	private $_statistics	= [];
   
	public function __construct(protected PurchaseSalesRepository $_repository)
	{
		$this->_statistics = [
			'brandId'			=> '', 
			'searchDate'		=> '', #Y-m-d
			'searchStoreId' 	=> '',
            'purchaseData' 		=> [],
			'salesData'			=> [],
			'exportToken'		=> '',
		];
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function analysis($brand, $functions, $searchDate, $searchStoreId)
	{
		try
		{
			#因不同邏輯,故init params放在child service
			$params = $this->_initParams($brand, $functions, $searchDate, $searchStoreId);
			
			#Prepare data(object default called by reference)
			$this->_prepareData($params);
			dd($params);	
			#Statistics
			$this->_outputReport($params);
				
			#Create output to var statistics
			$this->_generateStatistics($params);
				
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Init input params
	 * @params: enums
	 * @params: integer
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	private function _initParams($brand, $functions, $searchDate, $searchStoreId)
	{
		$params = new Fluent();
		
		$currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser->roleArea;
		
		$cacheKey 	= HelperLib::buildCacheKey([$functions, $userAreaIds, $searchDate, $searchStoreId]);
		
		$params->brand($brand)->userAreaIds($userAreaIds)
				->searchDate($searchDate)->searchStoreId($searchStoreId)
				->cacheKey($cacheKey);
		
		return $params;
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['modeType']		= $params->type;
		$this->_statistics['modeRange']		= $params->range;
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['startDate'] 	= $params->stDate;
		$this->_statistics['endDate']		= $params->endDate;
		$this->_statistics['header']		= $params->header;
		$this->_statistics['data']			= $params->data;
		
		#無值不cache
		if (! empty(Arr::flatten($this->_statistics['data'])))
		{
			
			$this->_statistics['exportName']	= '各餡月均量';
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1.Get product id
			$this->_getStoreParams($params);
			
			#2.Get purchase order
			$this->_getPurchaseOrderFromDB($params);
			
			#3.Get pos order
			$this->_getPosOrderFromDB($params);
			dd($params);
			
			$this->_buildBaseData($params, array_filter($orderData) , array_filter($extraData));
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Short code to proudct id
	 * @params: int
	 * @return: array
	 */
	private function _getStoreParams($params)
	{
		try
		{
			$info = $this->_repository->getPurchaseStoreInfoById($params->searchStoreId);
			
			$info['storeKey'] = PurchaseManager::buildStoreKey($info['storeNo']);
			$params->set('storeInfo', $info);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getPurchaseOrderFromDB($params)
	{
		/* [
			"expectedDate" => "2026-06-01"
			"qty" => "40"
			"amount" => "2800.000000"
			"productName" => "招牌餡"
			"shortCode" => "0001"
			"memo" => "170823最小單位改10斤 1070603改回最小單位5斤"
		]
		*/
	
		try
		{
			$brand 		= $params->brand;
			$stDate		= (new Carbon($params->searchDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($params->searchDate))->addDay()->format('Y-m-d H:i:s');
			$storeId	= $params->searchStoreId;
			$storeKey	= $params->storeInfo['storeKey'];
			
			#已包含蘿蔔訂單(因為是單店,區域權限在list已過濾)
			$orderData = $this->_repository->getPurchaseOrderByStore($brand, $stDate, $endDate, $storeId);
			
			$extraData = LegacyManager::getExtraDataByStore($brand, $stDate, $endDate, $storeKey);
			
			#整合追加資料
			$baseData = collect(array_filter($orderData))->merge(array_filter($extraData));
			
			#處理包裝轉換
			$baseData = $baseData->map(function($item, $key){
				$item['qty'] = round(intval($item['qty']) * PurchaseManager::getPackagingScale($item['shortCode']), 2);
				$item['amount'] = round($item['amount'], 2);
				return $item;
			})->toArray();
			
			$params->purchaseBaseData = $baseData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.計算查詢範圍Month
			$this->_buildMonthRange($params);
			
			#2.統計
			$this->_parsingByFactory($params);
			
			#3.產出成row data
			$params->set('data.qty', $this->_generateOutput($params, 'qty'));
			$params->set('data.avg', $this->_generateOutput($params, 'avg'));
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* 計算用參數
	 * @params: 
	 * @return: array
	 */
	private function _buildMonthRange($params)
	{
		$monthList 	= [];
		
		#By month
		$st 	= Carbon::parse($params->stDate)->startOfMonth();
		$end	= Carbon::parse($params->endDate)->startOfMonth();
			
		$period = CarbonPeriod::create($st, '1 month', $end);
		foreach ($period as $month) 
		{
			$monthList[] = $month->format('Y-m');
		}
		
		$params->monthList = $monthList;
	}
	
	/* 依工廠
	 * @params: array
	 * @return: array
	 */
	private function _parsingByFactory($params)
	{
		/*
		[
			"TW_KH" => array:2 [
				"2026-01" => array:9 [
					"0001" => array:2 [
						"qty" => 464490
						"avg" => 14983.55
					]
					"0002" => array:2 [...]
					"0003" => array:2 [...]
					"0005" => array:2 [...]
					"0006" => array:2 [...]
					"0007" => array:2 [...]
					"0011" => array:2 [...]
					"0015" => array:2 [...]
					"2267" => array:2 [...]
				]
				"2026-02" => array:9 [..]
			]
			"TW_TP" => array:2 [...]
		*/
		$orderData = $params->baseData;
		
		if (empty($orderData))
			return [];
		
		#分群定義不同
		$result = collect($orderData)->groupBy('factoryNo')->map(function($items, $key) {
			return $items->groupBy('expectedDate')->map(function($items, $month) {
				return $items->groupBy('shortCode')->map(function($items, $key) use ($month){
					
					$days = Carbon::parse($month)->daysInMonth;
					$temp['qty'] = round($items->pluck('qty')->sum(), 2);
					$temp['avg'] = round($temp['qty'] / $days, 2);
					
					return $temp;
					
				})->toArray();
			});
			
		})->sortKeys()->toArray();
		
		$params->parsingData = $result;
	}
	
	/* 改成產出row data
	 * @params: array
	 * @return: array
	 */
	private function _generateOutput($params, $type)
	{
		$parsingData = $params->parsingData;
		
		if (empty($parsingData))
			return [];
		
		$productList= collect($params->productList)->pluck('name', 'code')->toArray();
		$factoryList= $params->factoryList;
		$monthList	= $params->monthList;
		
		$params->header = array_merge(['出貨工廠', '年月'], array_values($productList));
		
		#共用Header
		$rowData = [];
		
		foreach($factoryList as $factoryNo => $factoryName)
		{
			$factoryData = data_get($parsingData, $factoryNo);
			
			if (empty($factoryData))
				continue;
			
			foreach($monthList as $month)
			{
				$row = [];
				$row[] = $factoryName;
				$row[] = $month;
				
				foreach($productList as $code => $name)
				{
					$row[] = data_get($factoryData, "{$month}.{$code}.{$type}");
				}
				
				$rowData[] = $row;
			}
		}
		
		return $rowData;
	}
	
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export = $this->_buildExportData($sourceData['header'], $sourceData['data']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_月初報表_?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$index = 0;
			foreach($export as $sheetKey => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheetName = ($sheetKey == 'qty') ? '月總量' : '月均量';
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
	private function _buildExportData($header, $data)
	{
		#Header row
		$export['qty'][] = $header;
		$export['avg'][] = $header;
		
		#只需要$key值
		foreach($export as $key => &$row)
		{
			foreach($data[$key] as $rowData)
			{
				$row[] = $rowData;
			}
		}
		
		return $export;
	}
}
