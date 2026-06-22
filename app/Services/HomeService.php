<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Services\Home\QuickOrderService;
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
class HomeService
{
	private $_statistics = [];
	
	public function __construct()
	{
		#預留有多個不同來源在首頁
		$this->_statistics = [
			'quickOrder'	=> [], 
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @params: string
	 * @return: array
	 */
	public function getStatistics()
	{
		try
		{
			/* if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限'); */
			
			#1. quick order
			$this->_statistics['quickOrder'] = $this->_getQuickOrderStatistics();	
			
			dd($this->_statistics);
			return ResponseLib::initialize($this->_statistics)->success();
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* Quick order
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _getQuickOrderStatistics()
	{
		/* $currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser->roleArea; */ 
		
		$quickOrder = app(QuickOrderService::class);
		
		$statistics = $quickOrder->analysis();	
		
		return $statistics;
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
