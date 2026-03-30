<?php

namespace App\Services\MonthlyFilling;

use App\Facades\AppManager;
use App\Repositories\MonthlyFillingRepository;
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
class MonthlyFillingStoreService
{
	const MODE = 'Name';
	
	private $_statistics	= [];
   
	public function __construct(protected MonthlyFillingRepository $_repository)
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
			
			#2.By門店
			$this->_statistics['data'] = $this->_parsingByStore($orderData);
			
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
		
		$header['storeList'] = $this->_getStoreList();

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
			$store = collect($store)->mapWithKeys(function($item, $key){
				if (is_null($item['postId']) OR $item['postId'] == 'null')
					$item['postId'] =  '';
				
				$item['area'] = Str::replace('-八方', '', $item['area']);
				$item['area'] = Str::replace('-御廚', '', $item['area']);
				
				return [$item['storeId'] => $item];
			})->toArray();
			
			return $store;
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
	private function _parsingByStore($orderData)
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
			$temp = $items->groupBy('storeId')->map(function($items, $key) use($modeCalc) {
				
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
