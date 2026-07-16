<?php

namespace App\Services\PurchaseNotOrder;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Facades\StoreManager;
use App\Facades\LocalLegacyManager; #from local
use App\Repositories\PurchaseNotOrderRepository;
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
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class ProductService
{
	private $_statistics	= [];
   
	public function __construct(protected PurchaseNotOrderRepository $_repository)
	{
		$this->_statistics = [
			'type'		=> '',
			'calc'		=> '',
			'by'		=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'productIds'	=> [],
			'productList'	=> [],
			'storeList'		=> [],
			'data'			=> [],
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
			$this->_prepareData($params);
			
			$this->_outputReport($params);
		
			$this->_generateStatistics($params);
			dd($params);
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
		$this->_statistics['type']			= $params->type;
		$this->_statistics['calc']			= $params->calc; 
		$this->_statistics['by']			= $params->by; 
		$this->_statistics['brandId']		= $params->brand->value; 
		$this->_statistics['brandCode']		= $params->brand->code(); 
		$this->_statistics['startDate'] 	= $params->stDate; 
		$this->_statistics['endDate'] 		= $params->endDate;
		$this->_statistics['productList'] 	= $params->productList;
		$this->_statistics['storeList'] 	= $params->storeList;
		$this->_statistics['data'] 			= $params->data;
		$this->_statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->data))
		{
			$this->_statistics['hasResult'] 	= TRUE;
			$this->_statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			
			$name = [];
			$name[] = ($params->type == 'store') ? '門店' : '工廠';
			$name[] = ($params->calc == 'day') ? 'BY日' : 'BY月';
			
			$this->_statistics['exportName'] = Arr::join($name, '_');
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			$this->_getStoreList($params);
			
			$orderData = $this->_getDataFromDB($params);
			
			$extraData = $this->_getExtraDataFromDB($params); #追加目前在舊系統,要另外處理
			
			$this->_buildBaseData($params, array_filter($orderData) , array_filter($extraData));
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* 門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getStoreList($params)
	{
		$brand 				= $params->brand;
		$stDate				= $params->stDate;
		$endDate			= $params->endDate;
		$allowOpCenterIds 	= $params->allowOpCenterIds;
		$allowAreaIds 		= $params->allowAreaIds;
		
		$storeList = StoreManager::getStoreListWithLb($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate);
		#這裏不排除工廠學區店
		#$params->storeList = StoreManager::filterFactoryStore($brand, $storeList);
		
		$params->storeList = $storeList;
	}
	
	/* Get order data
	 * @params: 
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		/*0 => array:12 [
			"expectedDate" => "2026-05-29"
			"area" => "中彰投-八方"
			"storeNo" => "KH4010002"
			"qty" => "90"
			"productName" => "招牌餡"
			"shortCode" => "0001"
		]
		*/
	
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$productIds			= $params->productIds;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			#已包含蘿蔔訂單
			#要有ErpNo,用來判別是否為有效產品
			$orderData = $this->_repository->getOrderDataByProductId($brand, $allowOpCenterIds, $allowAreaIds, $stDate, $endDate, $productIds);
			
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
		try
		{
			$brand 				= $params->brand;
			$stDate				= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 			= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$productCodes 		= $params->shortCodes;
			$allowOpCenterIds 	= $params->allowOpCenterIds;
			$allowAreaIds 		= $params->allowAreaIds;
			
			#維持原狀,不判別opCenter, 最後由門店一起過濾
			$extraData = LocalLegacyManager::getExtraDataByProduct($brand, $allowOpCenterIds, $stDate, $endDate, $productCodes);
			
			#因無areaId, 故只能從門店過濾
			$validStoreKeys = collect($params->storeList)->pluck('storeKey')->values()->all();
			
			$extraData = collect($extraData)->filter(function($item, $key) use($validStoreKeys) {
				$storeKey = Str::take($item['storeNo'], 7);
				return in_array($storeKey, $validStoreKeys);
			})->toArray();
			
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
	private function _buildBaseData($params, $orderData, $extraData = [])
	{
		#整合追加資料
		$baseData = collect($orderData)->merge($extraData);
		
		$authStoreKeys = collect($params->storeList)->pluck('storeKey')->unique()->all();
		
		#不處理包裝轉換,只算數量
		$baseData = collect($baseData)->map(function($item, $key){
			
			$item['storeKey'] 	= StoreManager::buildStoreKey($item['storeNo']);
			return $item;
		})->filter(function($item, $key) use($authStoreKeys){
			
			return in_array($item['storeKey'], $authStoreKeys);
		})->toArray();
	
		$params->baseData = $baseData;
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
			#1.Build productList
			$this->_buildProductList($params);
		
			#2. analysis by 門店
			$this->_parsingByStore($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _buildProductList($params)
	{
		$baseData = $params->baseData;
		
		#用shortcode group(因舊系統追加沒erpNo)
		$productList = collect($baseData)->groupBy('shortCode')->map(function($items, $key){
			
			return $items->pluck('productName')->first();
		})->sortKeys()->toArray();
		
		$params->productList = $productList;
		
	}
	
	/* 依Store
	 * @params: array
	 * @return: array
	 */
	private function _parsingByStore($params)
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
		
		$orderData = $params->baseData;
		
		if (empty($orderData))
		{
			$params->data = [];
			return;
		}
		
		#以門店最值,以便過濾無效門店
		$modeCalc = $params->calc;
		
		$result = collect($params->storeList)->map(function($item, $key) use($modeCalc) {
			
			$temp = $items->groupBy('storeKey')->map(function($items, $key) use($modeCalc) {
				
				if ($modeCalc == 'day')
				{
					$day = $items->groupBy('expectedDate')->map(function($items, $key) {
						$temp['qty'] = round($items->pluck('qty')->sum(), 2);
						return $temp;
					});
					
					return $day->toArray();	
				}
				
				if ($modeCalc == 'month')
				{
					$month = $items->groupBy(function ($item) {
						return substr($item['expectedDate'], 0, 7); 
					})->map(function ($group) {
						$temp['qty'] = round($group->pluck('qty')->sum(), 2);
						return $temp;
					});
					
					return $month->toArray();	
				}
			}); 
			
			return $temp;
		})->sortKeys()->toArray();
		dd($result);
		$params->data = $result;
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
			$export = $this->_buildExportData($sourceData);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate'], $sourceData['endDate']], '?_出貨總量_?_?_?.xlsx');
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
	private function _buildExportData($sourceData)
	{
		$export = [];
		$outputHeader = array_merge(['區域', 'POS店號', '門店代號', '門店名稱'], $sourceData['dateList']);
		
		#每個product要一個sheet
		foreach($sourceData['productList'] as $shortCode => $item)
		{
			$storeData = data_get($sourceData['data'], $shortCode, []);
			$productName = "{$shortCode}_{$item['productName']}";
			
			if (empty($storeData))
				continue;
			
			$export[$productName] = [];
			$export[$productName][] = $outputHeader;
			
			#使用header來控制顯示順序,先TP後KH
			foreach($sourceData['storeList'] as $index => $store)
			{
				$row = [];
				$row[] = $store['areaName'];
				$row[] = $store['posId'];
				$row[] = $store['storeNo'];
				$row[] = $store['storeName'];
				
				$storeKey = $store['storeKey'];
				
				#要按Header的順序
				foreach($sourceData['dateList'] as $date)
				{
					$row[] = data_get($storeData, "{$storeKey}.{$date}.qty", 0);
				}
				
				$export[$productName][] = $row;
			}
		}
		
		return $export;
	}
}
