<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\PurchaseSalesRepository;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use App\Libraries\Purchase\AreaLib;
use App\Services\PurchaseSales\StoreService;
use App\Services\PurchaseSales\DataService;
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

class PurchaseSalesService
{
	private $_statistics = [];
	
	public function __construct(protected PurchaseSalesRepository $_repository)
	{
		$this->_statistics = [
			'brandId'			=> '', 
			'searchDate'		=> '', #Y-m-d
			'searchStoreName' 	=> '',
            'storeList' 		=> [],
			'purchaseData' 		=> [], 
			'salesData'			=> [],
			'exportToken'		=> '', #export
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
			Brand::BAFANG	=> Functions::BF_PURCHASE_SALES, 
			Brand::BUYGOOD	=> Functions::BG_PURCHASE_SALES,
        };
	}
	
	/*************** Ger active store list ***************/
	/* Get store list
	 * @params: enums
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function getStoreList($brand, $searchDate, $searchStoreName)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$service = app(StoreService::class);
			
			$params = $this->_initStoreParams($brand, $searchDate, $searchStoreName);
			$this->_statistics = $service->getActiveList($params);
			
			$functions = ($this->parsingFunction($brand))->value;
			$service->saveToSession($functions);
			
			return ResponseLib::initialize($this->_statistics)->success();
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
	 * @return: array
	 */
	private function _initStoreParams($brand, $searchDate, $searchStoreName)
	{
		#參數各自獨立, 故寫在partial service
		$params = new Fluent();
		
		$currentUser	= AppManager::getCurrentUser();
		$userAreaIds	= $currentUser->roleArea;
		
		$params->brand($brand)->userAreaIds($userAreaIds)
				->searchDate($searchDate)->searchStoreName($searchStoreName);
		
		return $params;
	}
	
	/* 取Last store list
	 * @params: int
	 * @return: string
	 */
	public function getLastSearchData($function)
	{
		$service = app(StoreService::class);
		
		$data = $service->getFromSession($function->value);
		
		if ($data === FALSE)
			return ResponseLib::initialize($this->_statistics)->fail();
		
		#data is statistics format
		return ResponseLib::initialize($data)->success();
	}
	
	/*************** Ger purchase & sales data ***************/
	/* Get statistics from order & pos
	 * @params: enum
	 * @params: int
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchDate, $searchStoreId)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			#Params都用pass(保留service可複用空間)
			$params = $this->_initDetailParams($brand, $searchDate, $searchStoreId);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get purchase & sales data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get purchase & sales data from db');
				
				$service = app(DataService::class);
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
	 * @params: integer
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	private function _initDetailParams($brand, $searchDate, $searchStoreId)
	{
		$params = new Fluent();
		
		$currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser->roleArea;
		
		$functions 		= $this->parsingFunction($brand);
		$cacheKey 		= HelperLib::buildCacheKey([$functions->value, $userAreaIds, $searchDate, $searchStoreId]);
		
		$params->brand($brand)->userAreaIds($userAreaIds)
				->searchDate($searchDate)->searchStoreId($searchStoreId)
				->cacheKey($cacheKey);
		
		return $params;
	}
}
