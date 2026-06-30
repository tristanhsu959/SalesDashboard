<?php

namespace App\Services\EzOrderPos;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\EzOrderPosRepository;
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
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class StoreService
{
   
	public function __construct(protected EzOrderPosRepository $_repository)
	{
	}
	
	public function analysis($params)
	{
		try
		{
			$this->_prepareData($params);
			
			$this->_outputReport($params);
		
			return $this->_generateStatistics($params);
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
		$statistics['brandId']		= $params->brand->value;
		$statistics['brandCode']	= $params->brand->code();
		$statistics['type']			= $params->type;
		$statistics['startDate'] 	= $params->stDate;
		$statistics['endDate']		= $params->endDate;
		$statistics['store']['header']	= $params->header;
		$statistics['store']['data']	= $params->data;
		$statistics['hasResult']	= FALSE;
		
		#無值不cache
		if (! empty($statistics['store']['data']))
		{
			$statistics['exportToken'] = bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $statistics, now()->addMinutes(10));
			
			$statistics['hasResult']	= TRUE;
		}
		
		return $statistics;
	}
	
	/* ====================== Prepare Data ====================== */
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get all shops with area permission
			$this->_getActiveStoreList($params);
			
			#2.八方點Data
			$this->_getEzorderFromDB($params);
			
			#3.POS Data
			$this->_getPosFromDB($params);
			
			#4.POS Data
			$this->_buildBaseData($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get store info
	 * @params: fluent
	 * @return: array
	 */
	private function _getActiveStoreList($params)
	{
		#以訂貨的為基準, 因八方點是用訂貨的store(取有權限的全部與查詢area無關)
		$storeList = PurchaseManager::getStoreList($params->brand, $params->userAreaIds, $params->stDate, $params->endDate);
		
		#須濾除廠區學區店(依八方點的條件,雖有些店有PosId,但仍濾除)
		$brandId = $params->brand->value;
		$excepts = array_merge(config("web.ezorder_pos.store.factoryStore.{$brandId}"), config("web.ezorder_pos.store.except.{$brandId}"));
		
		$storeList = collect($storeList)->reject(function($item, $key) use($excepts){
			return in_array($item['storeKey'], $excepts) OR empty($item['posId']);
		})->all();
		
		$params->storeList = PurchaseManager::filterFactoryStore($storeList);
	}
	
	/* 取八方點DB
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _getEzorderFromDB($params)
	{
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$posIds 		= $this->_getPosIdsByUserArea($params);
			
			$result = $this->_repository->getDataFromEzOrder($brand, $stDate, $endDate, $posIds);
			
			$params->ezorderData = $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取八方點訂單資料失敗');
		}
	}
	
	/* Get posIds
	 * @params: fluent
	 * @return: array
	 */
	private function _getPosIdsByUserArea($params)
	{
		#因八方點無area, 故需用posid來判別過濾
		$posIds 	= [];
		$areaIds 	= $params->userAreaIds;
		
		$allAreaIds = Area::getAll();
		$filterArea = ! collect($allAreaIds)->diff($areaIds)->isEmpty(); #非全區才要過濾(減少DB where in參數)
				
		if (! empty($areaIds) && $filterArea)
		{
			$posIds = collect($params->storeList)->filter(function($item, $key) use($areaIds){
				return in_array($item['areaId'], $areaIds);
			})->pluck('posId')->all();
		}
		
		return $posIds; 
	}
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _getPosFromDB($params)
	{
		/* [
			"shopId" => "0001"
			"orderCount" => "137"
			"amount" => "26629.0000"
			"totalSales" => "26630.5000"
			"totalExtra" => ".0000"
			"totalDischarge" => "-1.5000"
			"businessDays" => "137"
		] */
  
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$areaIds 		= $params->userAreaIds;
			
			$result = $this->_repository->getDataFromPos($brand, $stDate, $endDate, $areaIds);
			
			$params->posData = $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取Pos訂單資料失敗');
		}
	}
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params)
	{
		#要先整理門店一致以便與ezorder對齊(依訂貨門店)
		#因八方點已是訂貨門店, 故只要處理POS
		$storeList = collect($params->storeList)->mapWithKeys(function($item, $key){
			return [$item['posId'] => $item['storeKey']];
		});
		
		$baseData = collect($params->posData)->map(function($item, $key) use($storeList){
			$temp['storeKey'] 	= data_get($storeList, $item['shopId'], NULL);
			
			#廠區或無POS再過濾
			if (empty($temp['storeKey']))
				return '';
			
			#二取一因可能有空值
			#發票金額 = amount OR totalSales + totalDischarge
			#實銷金額 = totalSales + totalExtra + totalDischarge
			$amount 	= floatval(data_get($item, 'amount', 0));
			$totalSales = floatval(data_get($item, 'totalSales', 0) + data_get($item, 'totalDischarge', 0));
			$temp['amount'] 		= empty($amount) ? $totalSales : $amount;
			$temp['orderCount']		= data_get($item, 'orderCount', 0);
			$temp['businessDays']	= data_get($item, 'businessDays', 0);
			
			return $temp; 
		})->all();
		
		$params->posData = array_filter($baseData);
	}
	
	/* ====================== Prepare Data End ====================== */
	
	
	/* ========================== Output 統計 ========================== */
	/* 整併統計資料
	 * @params: fluent object
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			$this->_buildHeader($params);
			
			$this->_parsingByStore($params);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Header資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildHeader($params)
	{
		$params->header = ['門店代號', '門店名稱', '區域', '營業天數', '八方點訂單數', '八方點總金額', 'POS訂單數', 'POS總金額', 
							'平均客單價', '平均日營收', '來客數佔比', '業績佔比'];
	}
	
	/* POS基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _parsingByStore($params)
	{
		#合併data
		$ezorderData = collect($params->ezorderData)->mapWithKeys(function($item, $key){
			return [$item['storeKey'] => $item];
		});
		
		$posData = collect($params->posData)->mapWithKeys(function($item, $key){
			return [$item['storeKey'] => $item];
		});
		
		$data = collect($params->storeList)->map(function($item, $key) use($ezorderData, $posData){
			
			$ezOrder	= data_get($ezorderData, $item['storeKey'], NULL);
			$posOrder 	= data_get($posData, $item['storeKey'], NULL);
			
			$temp['storeKey'] 		= $item['storeKey'];
			$temp['storeName'] 		= $item['storeName'];
			$temp['areaName'] 		= $item['areaName'];
			$temp['businessDays'] 	= intval(data_get($posOrder, 'businessDays', 0));
			
			#八方點
			$temp['ezOrderCount'] 	= intval(data_get($ezOrder, 'orderCount', 0));
			$temp['ezAmount'] 		= round(data_get($ezOrder, 'amount', 0), 2);
			
			#POS
			$temp['posOrderCount'] 	= intval(data_get($posOrder, 'orderCount', 0));
			$temp['posAmount'] 		= round(data_get($posOrder, 'amount', 0), 2);
			
			#總計
			$temp['avgOrderAmount'] = empty($temp['posOrderCount']) ? 0 : round($temp['posAmount'] / $temp['posOrderCount'], 2); #平均客單價
			$temp['avgDayAmount'] 	= empty($temp['businessDays']) ? 0 : round($temp['posAmount'] / $temp['businessDays'], 2);#平均每日營收
			
			#佔比
			$temp['countPercent'] 	= empty($temp['posOrderCount']) ? 0 : round($temp['ezOrderCount'] / $temp['posOrderCount'] * 100, 2) ; #來客數
			$temp['amountPercent'] 	= empty($temp['posAmount']) ? 0 : round($temp['ezAmount'] / $temp['posAmount'] * 100, 2);#營收
			
			return $temp; 
		})->all();
		
		$params->data = array_filter($data);
	}
	/* ========================== 統計 End ========================== */
	
	
	/* ========================== 匯出 ========================== */
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
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['startDate'], $sourceData['endDate']], '?_八方點統計_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$sheet = $writer->getCurrentSheet();
			$sheet->setName("{$sourceData['startDate']}~{$sourceData['endDate']}");
			
			foreach($export as $data)
			{
				$row =  Row::fromValues($data);
				$writer->addRow($row);
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
		$export[] = $sourceData['store']['header'];
		
		foreach($sourceData['store']['data'] as $data)
		{
			$data['ezAmount'] 			= Number::currency($data['ezAmount'], precision: 2);
			$data['posAmount'] 			= Number::currency($data['posAmount'], precision: 2);
			$data['avgOrderAmount'] 	= Number::currency($data['avgOrderAmount'], precision: 2);
			$data['avgDayAmount'] 		= Number::currency($data['avgDayAmount'], precision: 2);
			$data['countPercent'] 		= Number::percentage($data['countPercent'], precision: 2); 
			$data['amountPercent'] 		= Number::percentage($data['amountPercent'], precision: 2); 
			
			$export[] = array_values($data);
		}
		
		return $export;
	}
	
}
