<?php

namespace App\Services\PurchaseReport;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\LocalLegacyManager;
use App\Repositories\PurchaseReportRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Purchase\AreaLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class PerformanceService
{
	private $_statistics	= [];
   
	public function __construct(protected PurchaseReportRepository $_repository)
	{
		$this->_statistics = [
			'modeType'		=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'areaIds'		=> [],
			'report'		=> [],
			'exportName'	=> '', #export
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
			#Prepare data(object default called by reference)
			$this->_prepareData($params);
				
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
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['modeType']		= $params->type;
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['startDate'] 	= $params->stDate;
		$this->_statistics['endDate']		= $params->endDate;
		$this->_statistics['areaIds']		= $params->areaIds;
		$this->_statistics['report']		= $params->report;
		$this->_statistics['hasResult']		= FALSE;
		
		#無值不cache
		#因有補全門店,故基本上不會有空值,故hasResult應該都會是TRUE
		if (! empty(Arr::flatten($this->_statistics['report'])))
		{
			$this->_statistics['hasResult']		= TRUE;
			$this->_statistics['exportName']	= '營運概況';
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(15));
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
			$this->_getProductIdByCode($params);
			
			#2.Build params
			$params->storeList = PurchaseManager::getStoreListWithLb($params->brand, $params->areaIds, $params->stDate, $params->endDate);
			
			#3.Get Purchase data
			$orderData = $this->_getDataFromDB($params);
			
			#4.Get extra data
			$extraData = $this->_getExtraDataFromDB($params);
			
			#5. Build base data
			#會有false的無效array, 用array_filter去除
			$this->_buildBaseData($params, array_filter($orderData), array_filter($extraData));
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
	private function _getProductIdByCode($params)
	{
		try
		{
			#營運概況取固定的product
			$brandId 	= $params->brand->value;
			$codeGroup	= config('web.purchase.report.performance');
			
			#取出所有的short code
			$codes = collect($codeGroup)->collapse()->pluck('code')->toArray();
			
			$ids = PurchaseManager::getProductIdByShortCode($brandId, $codes);
			
			if (empty($ids))
				throw new Exception('查無參照的產品');
			
			$params->productGroup	= $codeGroup;
			$params->productCodes 	= $codes; #舊系統DB需用到
			$params->productIds 	= $ids;
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
	private function _getDataFromDB($params)
	{
		/*[
			"expectedDate" => "2026-06-17"
			"qty" => "1"
			"amount" => "45.000000"
			"storeNo" => "KH1100000"
			"shortCode" => "0202"
		]
		*/
	
		try
		{
			$brand 			= $params->brand;
			$stDate			= Carbon::parse($params->stDate)->format('Y-m-d H:i:s');
			$endDate 		= Carbon::parse($params->endDate)->addDay()->format('Y-m-d H:i:s');
			$userAreaIds	= $params->areaIds;
			$productIds 	= $params->productIds;
			
			$orderData = $this->_repository->getOrderDataByPerformance($brand, $userAreaIds, $stDate, $endDate, $productIds);
			
			return $orderData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* Get extra order data from old system
	 * @params: 
	 * @return: array
	 */
	private function _getExtraDataFromDB($params)
	{
		/*0 => array:8 [▼
			"expectedDate" => "2026-06-01"
			"storeNo" => "22000321"
			"shortCode" => "0101"
			"productName" => "水餃皮"
			"factoryNo" => "TW_TP"
			"factoryName" => "淡水總廠"
			"qty" => "1"
			"amount" => "27"
		]*/
		
		try
		{
			$brand 			= $params->brand;
			$stDate			= Carbon::parse($params->stDate)->format('Y-m-d H:i:s');
			$endDate 		= Carbon::parse($params->endDate)->addDay()->format('Y-m-d H:i:s');
			$productCodes 	= $params->productCodes;
			$userAreaIds 	= $params->areaIds;
			
			$extraData = LocalLegacyManager::getExtraDataByProduct($brand, $stDate, $endDate, $productCodes);
			
			return $extraData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取訂貨系統訂單資料失敗');
		}
	}
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params, $orderData, $extraData)
	{
		#整合追加資料
		$baseData = collect($orderData)->merge($extraData);
		
		$storeList = collect($params->storeList)->mapWithKeys(function($item, $key){
			return [$item['storeKey'] => $item];
		})->toArray();
		
		#過濾不計算門店(確保取得到store info)
		$baseData = PurchaseManager::filterOrderByStoreNo($params->brand->value, $baseData);
		
		#處理包裝轉換
		#因追加在舊系統,故要改成storeKey做為主要關聯
		$baseData = collect($baseData)->map(function($item, $key) use($storeList){
			$storeKey = PurchaseManager::buildStoreKey($item['storeNo']);
			$store = data_get($storeList, $storeKey);
			if (empty($store))
				dd($item, $storeKey, $store);
			$temp['expectedDate']	= $item['expectedDate'];
			$temp['storeNo'] 		= $item['storeNo'];
			$temp['storeKey'] 		= $storeKey;
			$temp['storeName'] 		= $store['storeName'];
			$temp['areaId'] 		= $store['areaId'];
			$temp['areaName'] 		= $store['areaName'];
			$temp['openDate'] 		= $store['openDate'];
			$temp['shortCode'] 		= $item['shortCode'];
			$temp['qty'] 			= round(intval($item['qty']) * PurchaseManager::getPackagingScale($item['shortCode']), 2);
			$temp['amount']			= $item['amount'];
			
			return $temp;
		});
		
		$orderStoreKeys = $baseData->pluck('storeKey')->toArray();
		
		#以store為基準補全資料
		$fillinData = collect($storeList)->reject(function($item, $key) use($orderStoreKeys){
			return in_array($item['storeKey'], $orderStoreKeys);
		})->map(function($item, $key) use($params){
			$temp['expectedDate']	= NULL; #會用來計算營業天數, 故要放空值
			$temp['storeNo'] 		= $item['storeNo'];
			$temp['storeKey'] 		= PurchaseManager::buildStoreKey($item['storeNo']);
			$temp['storeName'] 		= $item['storeName'];
			$temp['areaId'] 		= $item['areaId'];
			$temp['areaName'] 		= $item['areaName'];
			$temp['openDate'] 		= $item['openDate'];
			$temp['shortCode'] 		= '';
			$temp['qty'] 			= 0;
			$temp['amount']			= 0;
			
			return $temp;
		})->toArray();
		
		$params->baseData = $baseData->merge($fillinData);
	}
	
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
			#1.Build header data
			$this->_buildHeader($params);
			
			#2.By area & store
			$this->_parsingByStore($params);
			
			#3.Format output
			$this->_generateOutput($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Header
	 * @params: 
	 * @return: array
	 */
	private function _buildHeader($params)
	{
		$productGroup 	= $params->productGroup;
		
		$groupList = collect($productGroup)->map(function($group, $key){
			return collect($group)->mapWithKeys(function($item, $key){
				return [$item['code'] => $item['name']];
			})->toArray();
			
		})->toArray();
		
		#Build header
		$header = collect(['序號', '店名', '客戶編號', '開店日期'])
			->merge(array_values($groupList['filling']))
			->merge(['餡料總和', '餡料平均', '餡料銷售金額'])
			->merge(array_values($groupList['wrapper']))
			->merge(['皮總和', '皮銷售金額', '餡皮比率'])
			->merge(array_values($groupList['drink']))
			->merge(['總和', '銷售總額', '營業天數'])
			->all();
			
		$params->set('report.header', $header);
	}
	
	/* 依區->門店->產品
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($params)
	{
		#依區->門店->產品
		$result = collect($params->baseData)->groupBy('areaId')->map(function($items, $key){
			
			return $items->groupBy('storeKey')->map(function($items, $key){
				$temp['storeKey'] 	= $items->pluck('storeKey')->first();
				$temp['storeName'] 	= $items->pluck('storeName')->first();
				$temp['areaId'] 	= $items->pluck('areaId')->first();
				$temp['areaName'] 	= $items->pluck('areaName')->first();
				$temp['openDate'] 	= $items->pluck('openDate')->first();
				
				$temp['products']	= $items->groupBy('shortCode')->map(function($items, $key){
					#DB其實是unique的, 但還是當成array來處理
					$product['qty'] 	= round(floatval($items->pluck('qty')->sum()), 2); #因有乘係數, 故要用float
					$product['amount'] 	= round(floatval($items->pluck('amount')->sum()), 2); #因有乘係數, 故要用float
					
					return $product;
				})->toArray();
				
				$temp['openDays'] 	= $items->whereNotNull('expectedDate')->pluck('expectedDate')->unique()->count();
				
				return $temp;
			})->toArray();
			
		})->sortKeys()->toArray();
		
		$params->orders = $result;
	}
	
	/* 改成產出row data(降低JS render效能, 匯出可直接使用)
	 * @params: array
	 * @return: array
	 */
	private function _generateOutput($params)
	{
		$orders = $params->orders;
		
		if (empty($orders))
			return [];
		
		#須依據header順序
		foreach($orders as $areaId => $areaGroup)
		{
			$areaName = collect($areaGroup)->pluck('areaName')->first();
			$areaData[$areaName] = [];
			$sn = 1;
			
			foreach($areaGroup as $storeData)
			{
				$configMap	= $this->_getConfigProductMap();
				$opendays 	= data_get($storeData, 'openDays'); #營業天數
				
				$total = $this->_getProductOutput($storeData);
				
				$row = [];
				$row['sn']	= $sn;
				$row['storeName'] 	= data_get($storeData, 'storeName');
				$row['storeKey'] 	= data_get($storeData, 'storeKey');
				$row['openDate']	= data_get($storeData, 'openDate');
				
				#各餡量
				foreach($total['filling'] as $code => $value) 
				{
					$row[$code] = $value['qty'];
				}
				
				$fillingQty 	= collect($total['filling'])->pluck('qty')->sum();
				$fillingAmount 	= collect($total['filling'])->pluck('amount')->sum();
				
				$row['fillingQty'] 		= $fillingQty; #餡料總和
				$row['fillingAvg'] 		= empty($opendays) ? 0 : round($fillingQty / $opendays, 2); #餡料平均
				$row['fillingAmount'] 	= $fillingAmount; 	#餡料銷售金額
				
				$wrapperQty 	= collect($total['wrapper'])->pluck('qty')->sum();
				$wrapperAmount 	= collect($total['wrapper'])->pluck('amount')->sum();
				
				#各皮量
				foreach($total['wrapper'] as $code => $value) 
				{
					$row[$code] = $value['qty'];
				}
				
				$row['wrapperQty'] 		= $wrapperQty; 	#皮總和
				$row['wrapperAmount'] 	= $wrapperAmount; #皮銷售金額
				$row['wrapperRatio'] 	= empty($fillingQty) ? 0 : round($wrapperQty / $fillingQty, 2); #餡皮比率 (皮/餡)
				
				$drinkQty 		= collect($total['drink'])->pluck('qty')->sum();
				$drinkAmount 	= collect($total['drink'])->pluck('amount')->sum();
				
				#各飲料量
				foreach($total['drink'] as $code => $value) 
				{
					$row[$code] = $value['qty'];
				}
				
				$row['drinkQty'] 	= $drinkQty; #飲料總和
				$row['totalAmount'] = $fillingAmount + $wrapperAmount + $drinkAmount; #銷售總額
				$row['openDays'] = $opendays; #營業天數
				$sn++;
				
				$areaData[$areaName][] = $row;
			}
		}
		
		$params->set('report.sheets', array_keys($areaData));
		$params->set('report.amountFields', ['fillingAmount', 'wrapperAmount', 'totalAmount']);
		$params->set('report.data', $areaData);
	}
	
	/* 依據config product codes 先處理資料
	 * @params: array
	 * @return: array
	 */
	private function _getProductOutput($storeData)
	{
		$configMap = $this->_getConfigProductMap($storeData);
		$result = [];
		
		foreach($configMap as $group => $items)
		{
			$result[$group] = [];
			$row = [];
			
			foreach($items as $code => $name)
			{
				$row[$code] = [];
				$row[$code]['qty'] 		= intval(data_get($storeData, "products.{$code}.qty", 0));
				$row[$code]['amount'] 	= floatval(data_get($storeData, "products.{$code}.amount", 0));
			}

			$result[$group] = $row;
		}
		
		return $result;
	}
	
	/* 依據config product codes 先處理資料
	 * @params: array
	 * @return: array
	 */
	private function _getConfigProductMap()
	{
		$config = config('web.purchase.report.performance');
		
		$map = collect($config)->map(function($groups, $key){
			return collect($groups)->mapWithKeys(function($item, $key){
				return [$item['code'] => $item['name']];
			})->all();;
		})->all();
		
		return $map;
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
			$export = $this->_buildExportData($sourceData['report']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_?_?_?.xlsx');
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
	private function _buildExportData($reportData)
	{
		$data 			= $reportData['data'];
		$sheets 		= $reportData['sheets'];
		$header 		= $reportData['header'];
		$amountFields 	= $reportData['amountFields'];
		
		#每個product要一個sheet
		foreach($sheets as $key => $sheetName)
		{
			$export[$sheetName] = [];
			$export[$sheetName][] = $header;
			
			$storeData = data_get($data, $sheetName, []);
			
			if (empty($storeData))
				continue;
			
			foreach($storeData as $key => $rowData)
			{
				#dollar format
				$rowData = collect($rowData)->map(function($value, $key) use($amountFields){
					
					if (in_array($key, $amountFields))
						$value = Number::currency($value, precision: 0);
					
					return $value;
				})->values()->all();
				
				$export[$sheetName][] = $rowData;
			}
		}
		
		return $export;
	}
}