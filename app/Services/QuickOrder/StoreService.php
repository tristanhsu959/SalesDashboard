<?php

namespace App\Services\Home;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\QuickOrderRepository;
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
   
	public function __construct(protected QuickOrderRepository $_repository)
	{
		$this->_statistics = [
			'bafang'		=> [],
			'buygood'		=> [],
			'exportName'	=> '',
			'exportToken'	=> '',
		];
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function analysis()
	{
		try
		{
			$params = $this->_initParams();
			
			$this->_getDataByBafang($params);
			
			
			
			#Create output to var statistics
					$this->_generateStatistics($params);
					
					
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Init input params
	 * @params: 
	 * @return: array
	 */
	private function _initParams()
	{
		$params = new Fluent();
		
		$userAreaIds = Area::getAll(); #全開
		$searchMonths = $this->_buildMonthRange();
		
		$params->userAreaIds($userAreaIds)->searchMonths($searchMonths);
		
		return $params;
	}
	
	/* 計算用參數
	 * @params: 
	 * @return: array
	 */
	private function _buildMonthRange()
	{
		$monthList 	= [];
		
		#By month
		$st 	= Carbon::now()->startOfYear();
		$end	= Carbon::now()->startOfMonth(); #因是用< end判別,故要取到下個月
		$period = CarbonPeriod::create($st, '1 month', $end);
		
		foreach ($period as $month) 
		{
			$monthList[] = $month->format('Y-m');
		}
		
		return $monthList;
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['searchDate'] 	= $params->searchDate;
		$this->_statistics['storeInfo'] 	= $params->storeInfo;
		$this->_statistics['purchaseData']	= $params->purchaseData;
		$this->_statistics['saleData']		= $params->saleData;
		
		#無值不cache
		if (! empty($params->purchaseData['data']) OR ! empty($params->saleData['data']))
		{
			$this->_statistics['exportName']	= Str::replaceArray('?', [$params->storeInfo['storeName'], $params->searchDate], '?_訂貨及銷售資訊_?');;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); 
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(20));
		}
	}
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _getDataByBafang($params)
	{
		$params->brand = Brand::BAFANG;
		$params->set('bafang', []);
		$data = [];
		
		#一個月一個月取
		foreach($params->searchMonths as $month)
		{
			$args = new Fluent();
			$args->brand($params->brand)->userAreaIds($params->userAreaIds)->searchMonths($params->searchMonths);
			
			$cacheKey = HelperLib::buildCacheKey([$args->brand->value, 'QuickOrder', $month]);
			
			$data[$month] = Cache::rememberForever($cacheKey, function () use($args, $month){
				$args->stDate	= Carbon::parse($month)->format('Y-m-d');
				$args->endDate	= Carbon::parse($month)->addMonth()->format('Y-m-d');
				
				#Prepare data(object default called by reference)
				$this->_prepareData($args);
				return $this->_outputByMonth($args);
			});
			
			$currentMonth = now()->format('Y-m');
			
			if ($month == $currentMonth)
				Cache::forget($cacheKey); #當月每次都要重算
		}
		
		#綜合計算
		$result = $this->_outputReport($data);
		$params->set('bafang', $result);
		
		dd($params);
	}
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _prepareData($args)
	{
		try
		{
			#1.取時間內有效門店
			$this->_getStoreListByMonth($args);
			
			#2.Get quick orders
			$this->_getOrderByMonth($args);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Short code to proudct id
	 * @params: enum
	 * @params: string
	 * @return: array
	 */
	private function _getStoreListByMonth($args)
	{
		try
		{
			$storeList = PurchaseManager::getStoreList($args->brand, $args->userAreaIds, $args->stDate, $args->endDate);
			
			#只要有storeKey及area參數即可
			$storeList = collect($storeList)->mapWithKeys(function($item, $key){
				$temp['storeKey'] 	= $item['storeKey'];
				$temp['areaId'] 	= $item['areaId'];
				$temp['areaName'] 	= $item['areaName'];
				
				return [$item['storeKey'] => $temp];
			})->all();
			
			$args->storeList = $storeList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get order data
	 * @params: enum
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	private function _getOrderByMonth($args)
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
			$brandCode	= config("web.quick_order.store.code.{$args->brand->value}");
			$stDate		= (new Carbon($args->stDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($args->endDate))->format('Y-m-d H:i:s');
			
			#Get orders
			$orderData = $this->_repository->getOrders($brandCode, $stDate, $endDate);
			$storeList = $args->storeList;
			
			#補全門店Area參數
			$orderData = collect($orderData)->map(function($item, $key) use($storeList){
				
				$store = data_get($storeList, $item['storeId'], NULL);
				
				if(! is_null($store))
				{
					$item['areaId'] 	= $store['areaId'];
					$item['areaName'] 	= $store['areaName'];
					$item['orderDate']	= Carbon::parse($item['orderDate'])->format('Y-m');
					return $item;
				}
				
			})->toArray();
			
			$args->orderData = $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取八方點訂單資料失敗');
		}
	}
	
	/* 依月統計
	 * @params: array
	 * @return: array
	 */
	private function _outputByMonth($args)
	{
		/*
		"2026-01" => array:7 [▼
			"storeCount" => 1039
			"storeUsage" => 909
			"usageRate" => 87.49
			"customers" => 238107
			"amount" => 46845777.0
			"aov" => 196.74
			"area" => array:5 [▼
			  1 => array:7 [▼
				"areaName" => "大台北區"
				"storeCount" => 408
				"storeUsage" => 362
				"usageRate" => 88.73
				"customers" => 132029
				"amount" => 25188459.0
				"aov" => 190.78
			  ]
			  3 => array:7 [▶]
			  4 => array:7 [▶]
			  5 => array:7 [▶]
			  6 => array:7 [▶]
			]
		]
		*/
		$orderData = $args->orderData;
		
		if (empty($orderData))
			return [];
		
		$storeList = collect($args->storeList);
		$orderData = collect($args->orderData);
		
		#parsing
		$result = [];
		
		$result['storeCount'] 	= $storeList->count(); #有效門店統數
		$result['storeUsage'] 	= $orderData->count(); #使用門店數
		$result['usageRate'] 	= round($result['storeUsage'] / $result['storeCount'], 4) * 100; #使用率
		$result['customers']	= $orderData->pluck('customerCount')->sum();
		$result['amount']		= $orderData->pluck('amount')->sum();
		$result['aov']			= round($result['amount'] / $result['customers'], 2); #客單價
		
		$result['area'] = $storeList->groupBy('areaId')->map(function($items, $key) use($orderData) {
			$areaOrders = $orderData->where('areaId', $key);
			
			$temp['areaName']	= $items->pluck('areaName')->first();
			$temp['storeCount']	= $items->count();
			$temp['storeUsage']	= $areaOrders->count();
			$temp['usageRate'] 	= round($temp['storeUsage'] / $temp['storeCount'], 4) * 100; 
			$temp['customers']	= $areaOrders->pluck('customerCount')->sum();
			$temp['amount']		= $areaOrders->pluck('amount')->sum();
			$temp['aov']		= round($temp['amount'] / $temp['customers'], 2); #客單價
			
			return $temp;
		})->all();
		
		return $result;
	}
	
	/* 綜合計算全年結果
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($data)
	{
		try
		{
			$result = [];
			$data = collect($data);
			$months = $data->count();
			
			$result['total']['storeCount'] = intval($data->pluck('storeCount')->sum() / $months);
			$result['total']['storeUsage'] = round($data->pluck('storeUsage')->sum() / $months, 2);
			$result['total']['usageRate']
			$result['total']['customers']
			$result['total']['amount']
			$result['total']['aov']
			dd($result);
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export = $this->_buildExportData($sourceData['purchaseData'], $sourceData['saleData']);
			
			$storeName = data_get($sourceData, 'storeInfo.storeName', '');
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $storeName, $sourceData['searchDate']], '?_?_訂貨及銷售_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$index = 0;
			foreach($export as $sheetKey => $sheetData)
			{
				$sheet = ($index == 0) ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheetName = ($sheetKey == 'purchase') ? '訂貨' : '銷售';
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
	private function _buildExportData($purchase, $sale)
	{
		#Header row
		$export['purchase'][] 	= $purchase['header'];
		$export['sale'][] 		= $sale['header'];
		
		foreach($purchase['data'] as $data)
		{
			$row = [];
			$row[] = $data['shortCode'];
			$row[] = $data['productName'];
			$row[] = $data['qty'];
			$row[] = $data['amount'];
			$row[] = $data['memo'];
			
			$export['purchase'][] = $row;
		}
		
		foreach($sale['data'] as $data)
		{
			$row = [];
			$row[] = $data['erpNo'];
			$row[] = $data['productName'];
			$row[] = $data['qty'];
			$row[] = $data['amount'];
			
			$export['sale'][] = $row;
		}
		
		return $export;
	}
}
		