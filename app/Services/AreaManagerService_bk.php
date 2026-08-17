<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\StoreManager;
use App\Facades\PurchaseManager;
use App\Services\AreaManager\TemplateService;
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

class AreaManagerService
{
	private $_statistics = [];
   
	public function __construct()
	{
		$this->_statistics = [
			'brandId'		=> '', #export
			'type'			=> '',
			'startDate'		=> '', #Y-m-d
			'endDate'		=> '', #Y-m-d
			'opCenterIds'	=> [],
			'areaIds'		=> [],
            'info' 			=> [],
			'dayoff' 		=> [],
			'areaDayoff'	=> [],
			'exportToken'	=> '', #export
		];
	}
	
	/* ====================== 主流程 ====================== */
	/* Search data
	 * @params: enum
	 * @params: string
	 * @params: date
	 * @return: array
	 */
	public function getList($brandId, $areaIds)
	{
		try
		{
			$params = $this->_initParams($brandId, $areaIds);
			
			dd($params);
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get mechant data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get mechant data from db');
				
				if ($searchType == 'info')
					$service = app(InfoService::class);
				else if ($searchType == 'dayOff')
					$service = app(DayoffService::class);
				else
					throw new Exception('無法識別查詢條件');
				
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
	 * @params: array
	 * @return: array
	 */
	private function _initParams($brandId, $areaIds)
	{
		$params = new Fluent();
		
		$functions	= Functions::AREA_MANAGER;
		$cacheKey 	= HelperLib::buildCacheKey([$functions->value, $brandId, $areaIds]);
		
		$brand = Brand::tryFrom($brandId);
		
		#此功能不分區, 但取門店需要此參數
		$allowOpCenterIds	= PurchaseManager::getAllowOpCenters($brand); #只有取門店需要,無需代參數
		$allowAreaIds		= PurchaseManager::getAllowAreas($areaIds); #整併查詢參數
		
		$params->brand($brand)
				->allowOpCenterIds($allowOpCenterIds)->allowAreaIds($allowAreaIds)
				->cacheKey($cacheKey);
		
		return $params;
	}
	
	/* Export data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
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
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export merchant data-?'));
		
		$sourceData = Cache::get($cacheKey);
		$type = $sourceData['type'];
		
		if ($type == 'info')
			$service = app(InfoService::class);
		else
			$service = app(DayoffService::class);
		
		return $service->export($sourceData);
	}
}
