<?php

namespace App\Services;

use App\Services\Shipments\FactoryService;
use App\Services\Shipments\StoreService;
use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\ShipmentsRepository;
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
class ShipmentsService
{
	private $_statistics = [];
	
	public function __construct(protected ShipmentsRepository $_repository)
	{
		$this->_statistics = [
			'modeType'		=> '',
			'modeCalc'		=> '',
			'modeBy'		=> '',
			'brandId'		=> '', #export
			'brandCode'		=> '', 
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'productIds'	=> [],
			'dateList'		=> [],
			'productList'	=> [],
			'storeList'		=> [],
			'factoryList'	=> [],
			'data'			=> [],
			'exportName'	=> '', #export
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
			Brand::BAFANG	=> Functions::BF_SHIPMENTS, 
			Brand::BUYGOOD	=> Functions::BG_SHIPMENTS,
        };
	}
	
	/* 取出貨產品設定, 有啟用的產品清單 - purchase product setting(後台設定)
	 * @params: int
	 * @return: string
	 */
	public function getEnableProducts($brandId)
	{
		$enableProducts = $this->_repository->getEnableProducts($brandId);
		
		#Build product mapping
		$productMapping = PurchaseManager::getProductShortCodeMapping($brandId);
		
		#Build options
		/*array:4 [
			"shortCode" => "0001"
			"productName" => "招牌餡"
			"groupId" => 1
			"groupName" => "餡類"
		]
		*/
		$list = collect($enableProducts)->map(function($item, $key) use($productMapping) {
			$item['productName']= data_get($productMapping, "{$item['shortCode']}", '');
			
			$category = PurchaseManager::getGroupByShortCode($item['shortCode']);
			$item['groupId']	= $category['groupId'];
			$item['groupName'] 	= $category['groupName'];
			unset($item['brandId']);
			return $item;
		})->toArray();
			
		#要分成category & product對應
		$category = collect($list)->groupBy('groupId')->map(function($items, $key){
			$temp['catId'] = $items->pluck('groupId')->unique()->first();
			$temp['catName'] = $items->pluck('groupName')->unique()->first();
			
			return $temp;
		})->mapWithKeys(function($item, $key){
			return [$item['catId'] => $item['catName']];
		})->toArray();
		
		#Build product
		$products = collect($list)->groupBy('groupId')->map(function($items, $key){
			return $items->map(function($item, $key){
				unset($item['groupId']);
				unset($item['groupName']);
				
				return $item;
			});
			
			return $items;
		})->toArray();
		
		return [$category, $products];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($brand, $searchType, $searchCalc, $searchStDate, $searchEndDate, $searchBy, $searchKeyword, $searchCategory, $searchShortCodes)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$params = $this->_initParams($brand, $searchType, $searchCalc, $searchStDate, $searchEndDate, $searchBy, $searchKeyword, $searchCategory, $searchShortCodes);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get shipments data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get shipments data from db');
				
				$this->_getProductId($params);
				
				if ($params->type == 'store')
					$service = app(StoreService::class);
				else if ($params->type == 'factory')
					$service = app(FactoryService::class);
				else
					throw new Exception('查詢訂貨總量時發生錯誤');
				
				#執行統計
				$this->_statistics = $service->analysis($params);
				
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
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	private function _initParams($brand, $searchType, $searchCalc, $searchStDate, $searchEndDate, $searchBy, $searchKeyword, $searchCategory, $searchShortCodes)
	{
		$params = new Fluent();
		
		$currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser->roleArea;
		
		$searchEndDate 	= empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
		$functions 		= $this->parsingFunction($brand);
		
		if ($searchBy == 'keyword')
			$cacheKey = HelperLib::buildCacheKey([$functions->value, $userAreaIds, $searchType, $searchCalc, $searchStDate, $searchEndDate, $searchBy, $searchKeyword]);
		else
			$cacheKey = HelperLib::buildCacheKey([$functions->value, $userAreaIds, $searchType, $searchCalc, $searchStDate, $searchEndDate, $searchBy, $searchCategory, $searchShortCodes]);
		
		$params->brand($brand)->userAreaIds($userAreaIds)
				->stDate($searchStDate)->endDate($searchEndDate)
				->type($searchType)->calc($searchCalc)->by($searchBy)
				->keyword($searchKeyword)->category($searchCategory)->shortCodes($searchShortCodes)
				->cacheKey($cacheKey);
		
		return $params;
	}
	
	private function _getProductId($params)
	{
		try
		{
			if ($params->by == 'keyword')
				$params->productIds = PurchaseManager::getProductIdByName($params->brand->value,  $params->keyword);
			else
				$params->productIds = PurchaseManager::getProductIdByShortCode($params->brand->value, $params->shortCodes);
		
			if (empty($params->productIds))
				throw new Exception('查無此產品');
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
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
		else
			$service = app(FactoryService::class);
		
		return $service->export($sourceData);
	}
}
