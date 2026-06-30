<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Services\EzOrderPos\StoreService;
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
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#當主Service
class EzOrderPosService
{
	private $_statistics = [];
	
	public function __construct(protected EzOrderPosRepository $_repository)
	{
		$this->_statistics = [
			'brandId'		=> '', 
			'brandCode'		=> '',
			'type'			=> '',
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'areaIds'		=> [],
			'data'			=> [],
			'hasResult'		=> FALSE,
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
			Brand::BAFANG	=> Functions::BF_EZORDER_POS, 
			Brand::BUYGOOD	=> Functions::BG_EZORDER_POS,
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
	public function getStatistics($brand, $searchType, $searchStDate, $searchEndDate)
	{
		try
		{
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			$params = $this->_initParams($brand, $searchType, $searchStDate, $searchEndDate);
			
			$statistics = Cache::remember($params->cacheKey, 600, function () use($params){
				
				Log::channel('appServiceLog')->info('Get ezorder statistics from db');
				
				if ($params->type == 'store')
					$service = app(StoreService::class);
				else
					throw new Exception('無法識別查詢類別');
				
				#執行統計
				return $service->analysis($params);
			});
			
			#無資料狀況==============改寫
			if (! $statistics['hasResult'])
				Cache::forget($params->cacheKey);
			
			$this->_statistics = $statistics;
			
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
	private function _initParams($brand, $searchType, $searchStDate, $searchEndDate)
	{
		$params = new Fluent();
		
		$currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser->roleArea;
		
		$functions 		= $this->parsingFunction($brand);
		$cacheKey 		= HelperLib::buildCacheKey([$functions->value, $userAreaIds, $searchType, $searchStDate, $searchEndDate]);
		
		$params->brand($brand)->userAreaIds($userAreaIds)
				->type($searchType)
				->stDate($searchStDate)->endDate($searchEndDate)
				->cacheKey($cacheKey);
		
		return $params;
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export ezorder-pos data-?'));
		
		$sourceData = Cache::get($cacheKey);
		
		$type = $sourceData['type'];
		
		if ($type == 'store')
			$service = app(StoreService::class);
		else
			return ResponseLib::initialize('檔案下載失敗，請重新查詢')->fail();
		
		return $service->export($sourceData);
	}
}
