<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\NewReleaseRepository;
use App\Services\Traits\Sales\StoreServiceTrait;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
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

class NewReleaseService
{
	use StoreServiceTrait;
	
	private $_statistics	= [];
	
	public function __construct(protected NewReleaseRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'shop' 			=> [],
			'area' 			=> [],
			'top' 			=> [],
			'last' 			=> [],
			'productName'	=> '', #export
			'exportToken'	=> '', #export
		];
	}
	
	/* Parsing brand from url segment
	 * @params: string
	 * @return: string
	 */
	public function parsingBrand($segments)
	{
		$brand = $segments[0];
		return Brand::tryFromCode($brand);
	}
	
	/* Parsing function by brand
	 * @params: enums
	 * @return: string
	 */
	public function parsingFunction($brand)
	{
		return match ($brand) 
		{
			Brand::BAFANG	=> Functions::BF_NEW_RELEASE, 
			Brand::BUYGOOD	=> Functions::BG_NEW_RELEASE,
        };
	}
	
	/* 取新品設定by brand
	 * @params: int
	 * @return: string
	 */
	public function getNewReleaseProducts($brandId)
	{
		$result = $this->_repository->getNewReleaseProducts($brandId);
		$result = collect($result)->keyBy('id')->all();
		
		return $result;
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: int
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchReleaseId, $searchStDate, $searchEndDate)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			#Params都用pass(保留service可複用空間)
			$params = $this->_initParams($brand, $searchReleaseId, $searchStDate, $searchEndDate);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get new release data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get new release data from db');
				
				#Prepare data(object default called by reference)
				$this->_prepareData($params);
				
				#Statistics
				$this->_outputReport($params);
				
				#Create output to var statistics
				$this->_generateStatistics($params);
				
				return ResponseLib::initialize($this->_statistics)->success();
			}
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* Init input params
	 * @params: enums
	 * @params: integer
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	private function _initParams($brand, $searchReleaseId, $searchStDate, $searchEndDate)
	{
		$params = new Fluent();
		
		$currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser->roleArea;
		
		$searchEndDate 	= empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
		$functions 		= $this->parsingFunction($brand);
		$cacheKey 		= HelperLib::buildCacheKey([$functions->value, $userAreaIds, $searchReleaseId, $searchStDate, $searchEndDate]);
		
		$params->brand($brand)->userAreaIds($userAreaIds)
				->releaseId($searchReleaseId)->stDate($searchStDate)->endDate($searchEndDate)
				->cacheKey($cacheKey);
		
		return $params;
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']	= $params->brand->value;
		$this->_statistics['brandCode']	= $params->brand->code();
		$this->_statistics['startDate'] = $params->stDate;
		$this->_statistics['endDate']	= $params->endDate;
		$this->_statistics['shop']		= $params->shop;
		$this->_statistics['area']		= $params->area;
		$this->_statistics['top']		= $params->top;
		$this->_statistics['last']		= $params->last;
		$this->_statistics['productName']	= $params->productName;
		
		#無值不cache
		if (! empty(Arr::flatten($this->_statistics['shop'])))
		{
			$this->_statistics['exportToken'] = bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* 取新品銷售統計相關資料
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get product params
			$this->_getProductParams($params);
			
			#2. Get all shops with area permission
			$params->allShopList 	= $this->_getAllStores($params->brand, $params->userAreaIds); #all shops
			$params->activeShopList = $this->_getActiveStores($params->brand, $params->userAreaIds); #only active shops
			
			#3. Get POS data, 不需存
			$saleData = $this->_getDataFromDB($params);
			
			#4. Build base data
			#會有false的無效array, 用array_filter去除
			$this->_buildBaseData($params, array_filter($saleData));
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	/* ====================== 主流程 End ====================== */
	
	
	/* 取ErpNo及條件
	 * @params: object
	 * @return: array
	 */
	private function _getProductParams($params)
	{
		try
		{
			$settings = $this->_repository->getSettingById($params->releaseId);
			
			if (empty($settings))
				throw new Exception('新品設定不存在或已停用');
			
			$result = $this->_repository->getErpNoById($params->releaseId);
			
			#分開primary & secondary
			$primaryIds = collect($result)->filter(function($item, $key){
				return $item['isPrimary'];
			})->pluck('erpNo')->toArray();
			
			$secondaryIds = collect($result)->filter(function($item, $key){
				return ! $item['isPrimary'];
			})->pluck('erpNo')->toArray();
			
			$params->productName	= $settings['releaseName'];
			$params->primaryIds		= $primaryIds;
			$params->secondaryIds 	= $secondaryIds;
			$params->taste 			= $settings['releaseTaste'];
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析查詢參數發生錯誤');
		}
	}
	
	/* Get main data & mapping data
	 * @params: date
	 * @params: date
	 * @params: array
	 * @params: array => product ids of BF
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->format('Y-m-d 23:59:59');
			$primaryIds 	= $params->primaryIds;
			$secondaryIds 	= $params->secondaryIds;
			$tastes 		= $params->tastes;
			$userAreaIds 	= $params->userAreaIds;
			
			$saleData = $this->_repository->getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes, $userAreaIds);
				
			return $saleData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	
	
	/* 基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($params, $saleData)
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
		*/
		#要改成所有店家統計(含閉店)
		#這裏只要先補全店家資料(無銷售訂單)及所需欄位
		$allShopList = collect($params->allShopList)->groupBy('shopId');
		
		#DB有濾一遍了
		$saleData = $this->_filterExceptShop($params->brand, $saleData);
		
		#因有不同的gid定義, 故無法直接寫在sql
		$baseData = collect($saleData)->map(function($item, $key) use($allShopList) {
			$shop = $allShopList->get($item['shopId']);
			
			$item['saleDate']	= (new Carbon($item['saleDate']))->format('Y-m-d');
			$item['shopName'] 	= is_null($shop) ? '' : $shop->pluck('shopName')->first();
			$item['areaId'] 	= is_null($shop) ? 0 : AreaLib::toId($shop->pluck('areaId')->first());
			$item['areaName']	= (Area::tryFrom($item['areaId']))->label();

			return $item; 
		});
		
		#補全未有銷售的門店資料(closedown = 0)
		$saleShopIds = $baseData->pluck('shopId')->unique()->values()->toArray();
		$filterShops = $this->_getFillShop($params->activeShopList, $saleShopIds);
		
		#重建
		$filterShops = collect($filterShops)->map(function($item, $key) {
			$temp['shopId'] 	= $item['shopId'];
			$temp['saleDate'] 	= $this->_statistics['endDate'];
			$temp['qty'] 		= 0;
			$temp['shopName'] 	= $item['shopName'];
			$temp['areaId'] 	= AreaLib::toId($item['areaId']);
			$temp['areaName']	= (Area::tryFrom($temp['areaId']))->label();
			
			return $temp;
		});
		
		$params->baseData = $baseData->merge($filterShops)->toArray();
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: fluent object
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.計算查詢範圍總天數 (use Date not DateTime)
			$this->_buildDayRange($params);
			
			#2.店別每日銷售
			$this->_parsingByShop($params);
			
			#3.區域彙總
			$this->_parsingByArea($params);
			
			#4.當日銷售前10名
			#5.當日銷售後10名
			$this->_parsingByRanking($params);
			
			/***** Statistics End *****/
			return $params;
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
	private function _buildDayRange($params)
	{
		$st 		= Carbon::create($params->stDate);
		$end 		= Carbon::create($params->endDate);
		$period 	= CarbonPeriod::create($st, $end);
		
		$dateList = [];

		foreach ($period as $date) 
		{
			$dateString = $date->format('Y-m-d');
			$dateList[$dateString] = $dateString;
		}
		
		$params->dayRange	= $dateList;
		$params->totalDays 	= count($dateList);
	}
	
	
	
	/* 店別每日銷售
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByShop($params)
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
		
		$params->set('shop.header', []);
		$params->set('shop.data', []);
		
		$baseData	= $params->baseData;
		$totalDays 	= $params->totalDays;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		$header = ['areaName' => '區域', 'shopId' => '門店代號', 'shopName' => '門店名稱', 
					'dayQty' => $params->dayRange, 
					'totalQty' => '銷售總量', 'totalAvg' => '平均銷售數量'
				];
		
		$params->set('shop.header', $header);
		
		$result = collect($baseData)->sortBy('areaId')->groupBy('shopId')->map(function($item, $key) use($totalDays) {
			$temp['shopId']		= $item->pluck('shopId')->first();
			$temp['shopName'] 	= $item->pluck('shopName')->first();
			$temp['areaId'] 	= $item->pluck('areaId')->first();
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
		})->toArray();
		
		$params->set('shop.data', $result);
	}
	
	/* 區域彙總
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByArea($params)
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
		*/
		$params->set('area.header', []);
		$params->set('area.data', []);
		
		$baseData	= $params->baseData;
		$totalDays 	= $params->totalDays;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		$header = ['areaName' => '區域', 'shopCount'	=> '店家數', 'totalQty' => '銷售總量', 
					'avgDayQty' => '平均日銷售量', 'avgShopQty' => '每店平均銷量', 'avgDayShopQty' => '每店平均日銷量'];
		
		$params->set('area.header', $header);
		
		$result = collect($baseData)->groupBy('areaId')->map(function($items, $key) use($totalDays) {
			$temp['areaName']		= $items->pluck('areaName')->first();
			$temp['shopCount']		= $items->pluck('shopId')->unique()->count(); #店家數
			$temp['totalQty'] 		= intval($items->pluck('qty')->sum()); #區域銷售總量
			$temp['avgDayQty'] 		= round($temp['totalQty'] / $totalDays, 1); 		#區域平均日銷售量: 區域銷售總量/天數
			$temp['avgShopQty'] 	= round($temp['totalQty'] / $temp['shopCount'], 1); #區域每店平均銷量: 區域銷售總量/店家數
			$temp['avgDayShopQty'] 	= round($temp['totalQty'] / $totalDays / $temp['shopCount'], 1); 	#區域每店平均日銷量: 區域銷售總量/店家數/天數
			
			return $temp;
		})->sortKeys()->toArray();
		
		#這裏是依header
		$result['total']['areaName'] 		= '全區合計'; 
		$result['total']['shopCount'] 		= collect($result)->pluck('shopCount')->sum(); 
		$result['total']['totalQty'] 		= collect($result)->pluck('totalQty')->sum();
		$result['total']['avgDayQty'] 		= round($result['total']['totalQty'] / $totalDays, 1);
		$result['total']['avgShopQty'] 		= round($result['total']['totalQty'] / $result['total']['shopCount'], 1); #totalQty / shopCount
		$result['total']['avgDayShopQty']	= round($result['total']['avgDayQty'] / $result['total']['shopCount'], 1); #avgDayQty / shopCount
		
		$params->set('area.data', $result);
	}
	
	/* 當日銷售前10名
	 * @params: array
	 * @params: date
	 * @return: array
	 */
	private function _parsingByRanking($params)
	{
		/* 以銷售量來group shop
		[
			"103001" => [
				"shopId" => "103001"
				"shopName" => "御廚民生承德直營店"
				"area" => "大台北區"
				"saleDate" => '2026-01-01'
				"qty" => 29
			]
		]
		*/
		
		$params->set('top', []);
		$params->set('last', []);
		
		$baseData	= $params->baseData;
		$endDate 	= $params->endDate;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		#排名是依最後一天的值
		$result = collect($baseData)->groupBy('shopId')->map(function($items, $key) use($endDate) {
			#需考量沒有訂單的狀況
			$dayData = $items->groupBy('saleDate')->get($endDate, collect([]))->first();
			
			$temp = $items->first(); #當基底資料
			#$temp['saleDate'] 	= $endDate;
			$temp['qty']		= intval(data_get($dayData, 'qty', 0)); 
			unset($temp['saleDate'], $temp['areaId']);
			
			return $temp;
		});
		
		$top = $result->sortByDesc('qty')->groupBy('qty')->take(10)->values()->toArray();
		$last = $result->sortBy('qty')->groupBy('qty')->take(10)->values()->toArray();
		
		$params->set('top', $top);
		$params->set('last', $last);
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export new release data-?'));
		
		try
		{
			$sourceData = Cache::get($cacheKey);
			
			#Build export data for sheets
			$export['區域彙總'] 		= $this->_buildExportArea($sourceData['area']);
			$export['店別明細'] 		= $this->_buildExportShop($sourceData['shop']);
			$export['當日銷售前10名'] = $this->_buildExportRanking($sourceData['top'], $sourceData['endDate']);
			$export['當日銷售後10名']	= $this->_buildExportRanking($sourceData['last'], $sourceData['endDate']);
			
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
		$header = $areaData['header'];
		$export[] = $header;
		
		foreach($areaData['data'] as $areaId => $data)
		{
			$row = [];
			
			foreach($header as $key => $headName)
			{
				$row[] = data_get($data, $key);
			}
			
			$export[]= $row;
		}
		
		return $export;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportShop($shopData)
	{
		$header = Arr::flatten($shopData['header']);
		$export[] = $header;
		
		foreach($shopData['data'] as $shopId => $data)
		{
			$row = [];
			$row[] = data_get($data, 'areaName');
			$row[] = data_get($data, 'shopId');
			$row[] = data_get($data, 'shopName');
			
			foreach($shopData['header']['dayQty'] as $date)
			{
				$row[] = data_get($data, "dayQty.{$date}", 0);
			}
			
			$row[] = data_get($data, 'totalQty');
			$row[] = data_get($data, 'totalAvg');
			
			$export[]= $row;
		}
		
		return $export;
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	private function _buildExportRanking($rankingData, $targeDate)
	{
		$export[] = array_merge(['區域', '門店代號', '門店名稱'], [$targeDate], ['排名']);
		
		foreach($rankingData as $ranking => $shopList)
		{
			#同一排名會有重複
			foreach($shopList as $index => $data)
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
