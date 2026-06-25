<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Services\QuickOrder\StoreService;
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

#當主Service
class QuickOrderService
{
	private $_statistics = [];
	
	public function __construct(protected QuickOrderRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', 
			'brandCode'		=> '',
			'type'			=> '',
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'areaIds'		=> [],
			'storeName'		=> '',
			'data'			=> [],
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
			Brand::BAFANG	=> Functions::BF_QUICK_ORDER, 
			Brand::BUYGOOD	=> Functions::BG_QUICK_ORDER,
        };
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, $searchStoreName)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$params = $this->_initParams($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, $searchStoreName);
			
			$statistics = Cache::remember($params->cacheKey, 600, function () use($params){
				
				Log::channel('appServiceLog')->info('Get quick order statistics from db');
				
				#Prepare params for query(無PosId判別)
				if ($this->_beforePrepare($params) === FALSE)
					return NULL;
				
				#get data from db
				$this->_prepareData($params);
				
				#Statistics
				$this->_outputReport($params);
				
				#Create output to var statistics
				$this->_generateStatistics($params);
				
				return $this->_statistics;
			});
			
			#無資料狀況
			if (empty($statistics))
				Cache::forget($params->cacheKey);
			
			$this->statistics = $statistics;
			
			return ResponseLib::initialize($this->_statistics)->success();
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Init input params
	 * @params: enums
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	private function _initParams($brand, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, $searchStoreName)
	{
		$params = new Fluent();
		
		$currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser->roleArea;
		
		$functions 		= $this->parsingFunction($brand);
		$cacheKey 		= HelperLib::buildCacheKey([$functions->value, $userAreaIds, $searchType, $searchStDate, $searchEndDate, $searchAreaIds, $searchStoreName]);
		
		$params->brand($brand)->userAreaIds($userAreaIds)
				->type($searchType)
				->stDate($searchStDate)->endDate($searchEndDate)
				->areaIds($searchAreaIds)->storeName($searchStoreName)
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
		$this->_statistics['type']		= $params->type;
		$this->_statistics['startDate'] = $params->stDate;
		$this->_statistics['endDate']	= $params->endDate;
		$this->_statistics['shop']		= $params->shop;
		$this->_statistics['area']		= $params->area;
	}
	
	/* ====================== Before Prepare ====================== */
	/* PrepareParams
	 * @params: array
	 * @return: array
	 */
	private function _beforePrepare($params)
	{
		try
		{
			#因不同來源要對齊,故先轉換成PosId, 且要先做判別
		
			#1. Get all shops with area permission
			$this->_getActiveStoreList($params);
			
			#2. Get posIds
			$this->_getPosIdsBySearch($params);
			
			if ($params->type != 'all' && empty($params->posIds))
				return FALSE;
		
			#3. 取要統計的門店清單
			$this->_getOutputStores($params);
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
		
		#須濾除廠區學區店, 因為沒有posid, 無法有銷售對應
		$brandId = $params->brand->value;
		$excepts = array_merge(config("web.quick_order.store.factoryStore.{$brandId}"), config("web.quick_order.store.except.{$brandId}"));
		
		$storeList = collect($storeList)->reject(function($item, $key) use($excepts){
			return in_array($item['storeKey'], $excepts) OR empty($item['posId']);
		})->all();
		/* ->mapWithKeys(function($item, $key) {
			return [$item['posId'] => $item];
		}) */
		
		$params->allStores = $storeList;
	}
	
	/* Get posIds
	 * @params: fluent
	 * @return: array
	 */
	private function _getPosIdsBySearch($params)
	{
		#因八方點無area,且store來源不同, 故統一用posid來取值
		
		$posIds 	= [];
		$areaIds 	= $params->areaIds;
		$storeName 	= $params->storeName;
		$stores		= collect($params->allStores);
		
		if (! empty($areaIds))
		{
			$posIds = $stores->filter(function($item, $key) use($areaIds){
				return in_array($item['areaId'], $areaIds);
			})->pluck('posId')->all();
		}
		else if (! empty($storeName))
		{
			$posIds = $stores->filter(function($item, $key) use($storeName){
				return Str::contains($item['storeName'], $storeName);
			})->pluck('posId')->all();
		}
		
		$params->posIds = $posIds;
	}
	
	/* Get store list for output
	 * @params: fluent
	 * @return: array
	 */
	private function _getOutputStores($params)
	{
		#依條件過濾最終要顯示的門店
		$posIds 	= $params->posIds;
		$areaIds 	= $params->areaIds;
		$allStores	= collect($params->allStores);
		$storeList = [];
		
		if ($params->type == 'all')
			$storeList = $allStores;
		else if ($params->type == 'area')
		{
			$storeList = $allStores->filter(function($item, $key) use($areaIds){
				return in_array($item['areaId'], $areaIds);
			})->all();
		}
		else if ($params->type == 'storeName')
		{
			$storeList = $allStores->filter(function($item, $key) use($posIds){
				return in_array($item['posId'], $posIds);
			})->all();
		}
		
		$params->storeList = $storeList;
	}
	/* ====================== Before Prepare End ====================== */
	
	/* ====================== Prepare Data ====================== */
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get data from pos
			$posData = $this->_getDataFromPos($params);
			
			#2.build to base data
			$this->_buildPosBaseData($params, array_filter($posData)); 
			
			#3. Get data from quick order
			$qoData = $this->_getDataFromQuickOrder($params);
			
			#4.build to base data
			$this->_buildQuickOrderBaseData($params, array_filter($qoData)); 
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Get pos data
	 * @params: fluent
	 * @return: array
	 */
	private function _getDataFromPos($params)
	{
		/* 0 => array:7 [
			"shopId" => "0795"
			"saleDate" => "2026-06-24"
			"customers" => "59"
			"amount" => "8537.0000"
			"totalSales" => "8538.0000"
			"totalExtra" => ".0000"
			"totalDischarge" => "-1.0000"
		] 
		*/
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$posIds 		= $params->posIds;
			
			#帶入的是查詢的area
			$result = $this->_repository->getSaleFromPos($brand, $stDate, $endDate, $posIds);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	/* POS基底資料(DB已計算Sum)
	 * @params: collection
	 * @return: array
	 */
	private function _buildPosBaseData($params, $posData)
	{
		#用PosId先處理對齊Key值
		$storeList = collect($params->storeList)->mapWithKeys(function($item, $key){
			return [$item['posId'] => $item['storeKey']];
		});
		
		$baseData = collect($posData)->map(function($item, $key) use($storeList){
			$temp['storeKey'] 	= data_get($storeList, $item['shopId'], '');
			
			#二取一因可能有空值
			#發票金額 = amount OR totalSales + totalDischarge
			#實銷金額 = totalSales + totalExtra + totalDischarge
			$amount 	= floatval(data_get($item, 'amount', 0));
			$totalSales = floatval(data_get($item, 'totalSales', 0) + data_get($item, 'totalDischarge', 0));
			$temp['amount'] 	= empty($amount) ? $totalSales : $amount;
			$temp['customers']	= data_get($item, 'customers', 0);
			
			$temp['saleDate']	= $item['saleDate'];
			
			return $temp; 
		})->toArray();
		
		$params->posBaseData = $baseData;
	}
	
	/* 取統計相關參數
	 * @params: enums
	 * @params: integer
	 * @return: array
	 */
	private function _getDataFromQuickOrder($params)
	{
		try
		{
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$posIds 		= $params->posIds;
			
			#帶入的是查詢的area
			$result = $this->_repository->getSaleFromQuickOrder($brand, $stDate, $endDate, $posIds);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取八方點訂單資料失敗');
		}
	}
	
	/* QuickOrder基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildQuickOrderBaseData($params, $qoData)
	{
		#QuickOrder門店同訂貨
		$storeList = collect($params->storeList)->mapWithKeys(function($item, $key){
			return [$item['storeKey'] => $item['storeKey']];
		});
		
		$baseData = collect($qoData)->map(function($item, $key) use($storeList){
			
			$temp['storeKey'] 	= data_get($storeList, $item['storeId'], '');
			$temp['amount'] 	= floatval(data_get($item, 'amount', 0));
			$temp['customers']	= data_get($item, 'customers', 0);
			$temp['saleDate']	= $item['saleDate'];
			
			return $temp; 
		})->toArray();
		
		$params->qoBaseData = $baseData;
	}
	/* ====================== Prepare Data End ====================== */
	
	/* ========================== 統計 ========================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: fluent object
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.區域彙總
			$areaService = app(AreaService::class);
			$areaService->parsing($params);
			
			#2.店別每日銷售
			$storeService = app(StoreService::class);
			$storeService->parsing($params);
						
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	/* ========================== 統計 End ========================== */
	
	
	/* Export data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function export($token)
	{
		$cacheKey = hex2bin($token);
		
		if (! Cache::has($cacheKey))
			return ResponseLib::initialize()->fail('資料已過期，請重新查詢後下載');
		
		$currentUser = AppManager::getCurrentUser();
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export shipment data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$modeType = $sourceData['modeType'];
		
		if ($modeType == 'store')
			$service = app(StoreService::class);
		else if ($modeType == 'aov')
			$service = app(aovService::class);
		else
			return ResponseLib::initialize('檔案下載失敗，請重新查詢')->fail();
		
		return $service->export($sourceData);
	}
}
