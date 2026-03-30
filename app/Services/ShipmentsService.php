<?php

namespace App\Services;

use App\Services\Shipments\FactoryService;
use App\Services\Shipments\StoreService;
use App\Facades\AppManager;
use App\Repositories\ShipmentsRepository;
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

#當主Service
class ShipmentsService
{
	private $_statistics = [];
	
	public function __construct(protected ShipmentsRepository $_repository)
	{
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
	
	/* 取分類by brand
	 * @params: int
	 * @return: string
	 */
	public function getProductTypes($brandId)
	{
		$result = $this->_repository->getProductTypes($brandId);
		
		$result = collect($result)->mapWithKeys(function($item, $key){
			return [$item['No'] => $item['Name']];
		})->toArray();
		
		return $result;
	}
	
	/* 取分類及產品by brand
	 * @params: int
	 * @return: string
	 */
	public function getCategoryAndProduct($brandId)
	{
		$result = $this->_repository->getProductWithType($brandId);
		$result = collect($result)->groupBy('catNo');
		
		#Build category
		$category = $result->map(function($item, $no){
			return $item->pluck('catName')->first();
		});
		
		#Build product mapping
		$products = $result->map(function($items, $no){
			$items = $items->mapWithKeys(function($item, $key){
				return [$item['productNo'] => $item['productName']];
			})->toArray();
			
			return $items;
		});
		
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
	public function getStatistics($brand, $function, $searchStDate, $searchEndDate, $searchProductName, $searchType, $searchCalc)
	{
		try
		{
			#Check cache
			$functions = $this->parsingFunction($brand);
			$searchEndDate = empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
			$cacheKey = implode(':', [$functions->value, $searchStDate, $searchEndDate, $searchProductName, $searchType, $searchCalc]);
			
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get shipments data from cache');
				
				$statistics = Cache::get($cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get shipments data from db');
				
				if ($searchType == 'store')
					$service = app(StoreService::class);
				else
					$service = app(FactoryService::class);
				
				$productIds = $this->_getProductIdByName($brand->value, $searchProductName);
				
				#執行統計
				$this->_statistics = $service->analysis($brand->value, $searchStDate, $searchEndDate, $productIds, $searchType, $searchCalc);
				
				#無值不cache
				if (! empty($this->_statistics['data']))
				{
					$this->_statistics['exportToken'] 	= bin2hex($cacheKey); #hex2bin
					$this->_statistics['exportName']	= $searchProductName;
					Cache::put($cacheKey, $this->_statistics, now()->addMinutes(1));
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
	private function _getProductIdByName($brandId, $productName)
	{
		try
		{
			$ids = $this->_repository->getProductIdByName($brandId, $productName);
			
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->displayName, $cacheKey], '[?]Export new release data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$modeType = $sourceData['modeType'];
		
		if ($modeType == 'store')
			$service = app(StoreService::class);
		else
			$service = app(FactoryService::class);
		
		return $service->export($sourceData);
	}
}
