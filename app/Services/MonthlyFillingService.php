<?php

namespace App\Services;

use App\Services\MonthlyFilling\FactoryService;
use App\Services\MonthlyFilling\StoreService;
use App\Facades\AppManager;
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
class MonthlyFillingService
{
	private $_statistics = [];
	
	public function __construct()
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
			Brand::BAFANG	=> Functions::BF_MONTHLY_FILLING, 
        };
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: enum
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function getStatistics($brand, $function, $searchStDate, $searchEndDate, $searchType, $searchRange)
	{
		try
		{
			#轉換日期
			if ($searchRange == 'year')
			{
				$year = Carbon::now()->year;
				$searchStDate 	= Carbon::create($year)->startOfYear()->toDateString();
				$searchEndDate 	= Carbon::create($year)->endOfYear()->toDateString();
			}
			else if ($searchRange == 'month')
			{
				$searchStDate = Carbon::createFromFormat('!Y-m', $searchStDate)->toDateString();
				$searchEndDate = Carbon::createFromFormat('!Y-m', $searchEndDate)->endOfMonth()->toDateString();
			}
			
			#Check cache
			$functions = $this->parsingFunction($brand);
			$cacheKey = implode(':', [$functions->value, $searchStDate, $searchEndDate, $searchType, $searchRange]);
			
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get monthly filling data from cache');
				
				$statistics = Cache::get($cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get monthly filling data from db');
				
				if ($searchType == 'store')
					$service = app(StoreService::class);
				else
					$service = app(FactoryService::class);
				
				#執行統計
				$this->_statistics = $service->analysisStatisticsData($brand->value, $searchStDate, $searchEndDate, $searchType, $searchRange);
				
				#無值不cache
				if (! empty($this->_statistics['data']))
				{
					$this->_statistics['exportToken'] 	= bin2hex($cacheKey); #hex2bin
					$this->_statistics['exportName']	= '月初報表';
					Cache::put($cacheKey, $this->_statistics, now()->addMinutes(30));
				}
				
				return ResponseLib::initialize($this->_statistics)->success();
			}
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->displayName, $cacheKey], '[?]Export monthly filling data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$modeType = $sourceData['modeType'];
		
		if ($modeType == 'store')
			$service = app(StoreService::class);
		else
			$service = app(FactoryService::class);
		
		return $service->export($sourceData);
	}
}
