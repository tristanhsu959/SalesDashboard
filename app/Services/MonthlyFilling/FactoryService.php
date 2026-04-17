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
class FactoryService
{
	private $_userAreaIds 	= FALSE;
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
	
	/* ====================== 主流程 ====================== */
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
			$currentUser = AppManager::getCurrentUser();
			$this->_userAreaIds = $currentUser['roleArea'];
			
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
			$codes		= config('web.purchase.monthly_filling.monthly');
			#非0開頭會變成int
			$codes = collect($codes)->pluck('code')->all();
			
			$ids = $this->_repository->getProductIdByCode($brandId, $codes);
			
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
	
	/* Get order data
	 * @params: enums
	 * @params: date
	 * @params: date
	 * @params: array
	 * @return: array
	 */
	private function _getDataFromDB($productIds)
	{
		/* array:4 [
			"expectedDate" => "2026-02"
			"qty" => "7059"
			"factoryId" => "1"
			"shortCode" => "2267"
		]
		*/
	
		try
		{
			$brand 		= Brand::tryFrom($this->_statistics['brandId']);
			$stDate		= (new Carbon($this->_statistics['startDate']))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($this->_statistics['endDate']))->format('Y-m-d 23:59:59');
			
			$orderData = $this->_repository->getOrderDataByFactory($brand, $stDate, $endDate, $productIds, $this->_userAreaIds);
			
			return $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨資料失敗');
		}
	}
	/* ====================== 主流程 End ====================== */
	
	
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
			
			#3.By工廠
			$data = $this->_parsingByFactory($orderData);
			
			#4.產出成row data
			$this->_statistics['data']['qty'] = $this->_generateOutput($data, 'qty');
			$this->_statistics['data']['avg'] = $this->_generateOutput($data, 'avg');
			
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
			$header['monthList'][] 	= $month->format('Y-m');
		}
		
		#productList
		$header['productList'] = config('web.purchase.monthly_filling.monthly');
		
		$header['factoryList'] = $this->_getFactoryList();
		
		return $header;
	}
	
	/* Header
	 * @params: 
	 * @return: array
	 */
	private function _buildHeader()
	{
		$productList = $this->_statistics['temp']['productList'];
		$productList = collect($productList)->pluck('name')->toArray();
		
		return array_merge(['出貨工廠', '年月'], $productList);
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
			$factory = collect($factory)->mapWithKeys(function($item, $key){
				return [$item['factoryNo'] => $item['factoryName']];
			});
			
			return $factory;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取工廠資料失敗');
		}
	}
	
	/* 依工廠
	 * @params: array
	 * @return: array
	 */
	private function _parsingByFactory($orderData)
	{
		/*
		[
			"TW_KH" => array:2 [
				"2026-01" => array:9 [
					"_0001" => array:2 [
						"qty" => 464490
						"avg" => 14983.55
					]
					"_0002" => array:2 [...]
					"_0003" => array:2 [...]
					"_0005" => array:2 [...]
					"_0006" => array:2 [...]
					"_0007" => array:2 [...]
					"_0011" => array:2 [...]
					"_0015" => array:2 [...]
					"_2267" => array:2 [...]
				]
				"2026-02" => array:9 [..]
			]
			"TW_TP" => array:2 [...]
		*/
		if (empty($orderData))
			return [];
		
		#分群定義不同
		$result = collect($orderData)->groupBy('factoryNo')->map(function($items, $key) {
			
			return $items->groupBy('expectedDate')->map(function($items, $month) {
				
				return $items->groupBy('shortCode')->map(function($items, $key) use ($month){
					
					$days = Carbon::parse($month)->daysInMonth;
					$temp['qty'] = $items->pluck('qty')->sum();
					$temp['avg'] = round($temp['qty'] / $days, 2);
					
					return $temp;
					
				})->toArray();
			});
			
		})->sortKeys()->toArray();
		
		return $result;
	}
	
	/* 改成產出row data
	 * @params: array
	 * @return: array
	 */
	private function _generateOutput($data, $type)
	{
		if (empty($data))
			return [];
		
		$productList = collect(data_get($this->_statistics, 'temp.productList', []))->pluck('name', 'code')->toArray();
		$factoryList = data_get($this->_statistics, 'temp.factoryList', []);
		$monthList = data_get($this->_statistics, 'temp.monthList', []);
		
		$rowData = [];
		
		foreach($factoryList as $factoryNo => $factoryName)
		{
			$factoryData = data_get($data, $factoryNo);
			
			if (empty($factoryData))
				continue;
			
			foreach($monthList as $month)
			{
				$row = [];
				$row[] = $factoryName;
				$row[] = $month;
				
				foreach($productList as $key => $name)
				{
					$row[] = data_get($factoryData, "{$month}.{$key}.{$type}");
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
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_?_?_?.xlsx');
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
