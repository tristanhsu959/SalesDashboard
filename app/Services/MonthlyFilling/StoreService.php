<?php

namespace App\Services\MonthlyFilling;

use App\Facades\AppManager;
use App\Repositories\MonthlyFillingRepository;
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
class StoreService
{
	const MODE = 'Name';
	
	private $_statistics	= [];
   
	public function __construct(protected MonthlyFillingRepository $_repository)
	{
		$this->_statistics = [
			'modeType'		=> '',
			'modeRange'		=> '',
			'brandId'		=> '', #export
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
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
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function analysisStatisticsData($brandId, $searchStDate, $searchEndDate, $searchType, $searchRange)
	{
		try
		{
			$this->_statistics['modeType']	= $searchType;
			$this->_statistics['modeRange']	= $searchRange; 
			$this->_statistics['brandId']	= $brandId; 
			$this->_statistics['startDate'] = $searchStDate; 
			$this->_statistics['endDate'] 	= $searchEndDate;
			
			#執行統計
			#1. 取餡料product id
			$productIds = $this->_getProductIdByCode();
			
			#2. Get Order data
			$orderData = $this->_getDataFromDB($productIds);
			
			return $this->_outputReport($orderData);
				
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Short code to proudct id
	 * @params: int
	 * @return: array
	 */
	private function _getProductIdByCode()
	{
		try
		{
			$brandId 	= $this->_statistics['brandId'];
			$codes		= array_keys(config('web.purchase.monthly_filling.totalCount.code'));
			$codes = collect($codes)->map(function ($value, $key) {
				return Str::after($value, '_');
			})->all();
			
			$ids = $this->_repository->getProductIdByCode($brandId, $codes);
			$ids = collect($ids)->map(function($item, $key){
				return (int)$item;
			})->toArray();
			
			if (empty($ids))
				throw new Exception('查無參照的產品');
			
			return $ids;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromDB($productIds)
	{
		/* [
			"expectedDate" => "2026-03"
			"qty" => "310"
			"storeId" => "1911"
			"shortCode" => "0003"
		]
		*/
	
		try
		{
			$brandId 	= $this->_statistics['brandId'];
			$stDate		= (new Carbon($this->_statistics['startDate']))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($this->_statistics['endDate']))->format('Y-m-d 23:59:59');
			
			$orderData = $this->_repository->getOrderDataByStore($brandId, $stDate, $endDate, $productIds);
			
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
			#1.Build params
			$this->_statistics['temp'] = $this->_buildParams();
			
			#2.Build header data
			$this->_statistics['header'] = $this->_buildHeader();
			
			#3.By門店
			$productList = $this->_statistics['temp']['productList'];
			$data = [];
				
			foreach($productList as $key => $product)
			{
				$data[$key] = $this->_parsingByStore($orderData, $product);
				#$this->_statistics['data'][$key] = $this->_generateOutput($data);
			}
			dd($data['g2']);
			unset($this->_statistics['temp']);
			
			return $this->_statistics;
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
	private function _buildParams()
	{
		$header 	= [];
		
		#By month
		$st 	= Carbon::parse($this->_statistics['startDate'])->startOfMonth();
		$end	= Carbon::parse($this->_statistics['endDate'])->startOfMonth();
			
		$period = CarbonPeriod::create($st, '1 month', $end);
		foreach ($period as $month) 
		{
			$header['monthList'][] = $month->format('Y-m');
		}
		
		$header['productList'] = config('web.purchase.monthly_filling.totalCount.group');
		
		$header['storeList'] = $this->_getStoreList();
		
		return $header;
	}
	
	/* Header
	 * @params: 
	 * @return: array
	 */
	private function _buildHeader()
	{
		$monthList = $this->_statistics['temp']['monthList'];
		$productList = $this->_statistics['temp']['productList'];
		
		$header['sheet'] = collect($productList)->mapWithKeys(function($item, $key){
			return [$key => $item['name']];
		})->toArray();
		
		$header['tableHeader'] 	= array_merge(['POS ID', '區域', '門店名稱'], $monthList);
		
		return $header;
	}
	
	/* Get order data
	 * @params: enums
	 * @params: date
	 * @params: date
	 * @params: array
	 * @return: array
	 */
	private function _getStoreList()
	{
		try
		{
			$brandId = $this->_statistics['brandId'];
			$store = $this->_repository->getStoreList($brandId);
			
			#To key-value
			$store = collect($store)->map(function($item, $key){
				if (is_null($item['postId']) OR $item['postId'] == 'null')
					$item['postId'] =  '';
				
				$item['area'] = Str::replace('-八方', '', $item['area']);
				$item['area'] = Str::replace('-御廚', '', $item['area']);
				
				return $item;
			})->toArray();
			
			return $store;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* 依門店
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($orderData, $product)
	{
		/*
		"g1" => array:1063 [
			"1911" => array:2 [
				"2026-03" => 1590
				]
				"2026-02" => array:1 [...]
			]
		]
		*/
		if (empty($orderData))
			return [];
		
		$groups = data_get($product, 'code', []);
		
		#先依定義的餡分群
		$result = collect($orderData)->filter(function($item, $key) use($groups){
			return in_array($item['shortCode'], $groups);
		
		})->map(function($item, $key){
			$coefficient = config("web.purchase.monthly_filling.totalCount.code.{$item['shortCode']}.coefficient");
			$item['qty'] = round(floatval($item['qty']) * $coefficient, 2);
			return $item;
		
		})->groupBy('storeId')->map(function($items, $key) {
			return $items->groupBy('expectedDate')->map(function($items, $key) {
				return $items->pluck('qty')->sum();
			})->toArray();
		
		})->toArray();
		
		return $result;
	}
	
	/* 改成產出row data
	 * @params: array
	 * @return: array
	 */
	private function _generateOutput($data)
	{
		if (empty($data))
			return [];
		
		$storeList = data_get($this->_statistics, 'temp.storeList', []);
		$monthList = data_get($this->_statistics, 'temp.monthList', []);
		
		$rowData = [];
		
		foreach($storeList as $store)
		{
			$storeId = $store['storeId'];
			$storeData = data_get($data, $storeId);
						
			$row = [];
			$row[] = data_get($store, 'postId');
			$row[] = data_get($store, 'area');
			$row[] = data_get($store, 'storeName');
			
			foreach($monthList as $month)
			{
				$row[] = data_get($data, "{$storeId}.{$month}", 22);
			}			
				
			$rowData[] = $row;
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
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_?_出貨總量_?_?.xlsx');
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
	private function _buildExportData($header, $data)
	{
		$export = [];
		$outputHeader = array_merge(['POS ID', '區域', '門店代號', '門店名稱'], $header['dateList']);
		
		#每個product要一個sheet
		foreach($header['productList'] as $erpNo => $productName)
		{
			$storeData = data_get($data, $erpNo, []);
			
			if (empty($storeData))
				continue;
			
			$export[$productName] = [];
			$export[$productName][] = $outputHeader;
			
			#使用header來控制顯示順序,先TP後KH
			foreach($header['storeList'] as $storeNo => $store)
			{
				$row = [];
				$row[] = $store['postId'];
				$row[] = $store['area'];
				$row[] = $store['storeNo'];
				$row[] = $store['storeName'];
				
				#要按Header的順序
				foreach($header['dateList'] as $date)
				{
					$row[] = data_get($storeData, "{$storeNo}.{$date}.qty", 0);
				}
				
				$export[$productName][] = $row;
			}
		}
		
		return $export;
	}
}
