<?php

namespace App\Services;

use App\Services\Shipments\FactoryService;
use App\Services\Shipments\StoreService;
use App\Services\Traits\Purchase\ProductTrait;
use App\Facades\AppManager;
use App\Repositories\ShipmentsRepository;
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

#當主Service
class ShipmentsService
{
	use ProductTrait;
	private $_statistics = [];
	
	public function __construct(protected ShipmentsRepository $_repository)
	{
		$this->_statistics = [
			'modeType'		=> '',
			'modeCalc'		=> '',
			'modeBy'		=> '',
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
		$productMapping = $this->_repository->getProductShortCode($brandId);
		$productMapping = collect($productMapping)->mapWithKeys(function($item, $key){
			return [$item['productNo'] => $item['productName']];
		})->toArray();
		
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
			
			$category = $this->getGroupByShortCode($item['shortCode']);
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
	public function getStatistics($brand, $function, $params)
	{
		try
		{
			if (AppManager::hasAreaAuth() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			#Check cache
			$functions = $this->parsingFunction($brand);
			$searchEndDate = empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
			
			$this->_statistics['modeType']	= $params['searchType'];
			$this->_statistics['modeCalc']	= $params['searchCalc']; 
			$this->_statistics['modeBy']	= $params['searchBy']; 
			$this->_statistics['brandId']	= $brand->value; 
			$this->_statistics['startDate'] = (new Carbon($params['searchStDate']))->format('Y-m-d'); 
			$this->_statistics['endDate'] 	= (new Carbon($params['searchEndDate']))->format('Y-m-d');
			
			if ($params['searchBy'] == 'keyword')
				$cacheKey = implode(':', [$functions->value, $params['searchStDate'], $params['searchEndDate'], $params['searchKeyword'], $params['searchType'], $params['searchCalc'], $params['searchBy']]);
			else
				$cacheKey = implode(':', [$functions->value, $params['searchStDate'], $params['searchEndDate'], $params['searchCategory'], implode('-', $params['searchShortCodes']), $params['searchType'], $params['searchCalc'], $params['searchBy']]);
		
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get shipments data from cache');
				
				$statistics = Cache::get($cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get shipments data from db');
				
				if ($params['searchType'] == 'store')
					$service = app(StoreService::class);
				else
					$service = app(FactoryService::class);
				
				if ($params['searchBy'] == 'keyword')
					$this->_statistics['productIds'] = $this->_getProductIdByName($brand->value, $params['searchKeyword']);
				else
					$this->_statistics['productIds'] = $this->_getProductIdByShortCode($brand->value, $params['searchShortCodes']);
				
				#執行統計
				$this->_statistics = $service->analysis($this->_statistics);
				
				#無值不cache
				if (! empty($this->_statistics['data']))
				{
					$this->_statistics['exportToken'] 	= bin2hex($cacheKey); #hex2bin
					$this->_statistics['exportName']	= ($params['searchBy'] == 'keyword') ? $params['searchKeyword'] : '分類';
					Cache::put($cacheKey, $this->_statistics, now()->addMinutes(10));
				}
				
				return ResponseLib::initialize($this->_statistics)->success();
			}
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* Name to proudct id
	 * @params: int
	 * @return: array
	 */
	private function _getProductIdByName($brandId, $keyword)
	{
		try
		{
			$ids = $this->_repository->getProductIdByName($brandId, $keyword);
			
			if (empty($ids))
				throw new Exception('無此產品名稱');
			
			return $ids;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* Name to proudct id
	 * @params: int
	 * @return: array
	 */
	private function _getProductIdByShortCode($brandId, $shortCodes)
	{
		try
		{
			if (empty($shortCodes))
				return [];
			
			$ids = $this->_repository->getProductIdByShortCode($brandId, $shortCodes);
			
			if (empty($ids))
				throw new Exception('無對應的產品');
			
			return $ids;
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->displayName, $cacheKey], '[?]Export shipment data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$modeType = $sourceData['modeType'];
		
		if ($modeType == 'store')
			$service = app(StoreService::class);
		else
			$service = app(FactoryService::class);
		
		return $service->export($sourceData);
	}
}
