<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\NewReleaseRepository;
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
use Exception;


class NewReleaseService
{
	private $_statistics	= [];
   
	public function __construct(protected NewReleaseRepository $_repository)
	{
		$this->_statistics = [
			'startDate'	=> '', #Y-m-d
            'endDate'   => '',
			'dayHeaer'	=> [],
			'shop' 		=> [],
			'area' 		=> [],
			'top' 		=> [],
			'last' 		=> [],
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
	 * @params: string
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
	public function getNewItemOptions($brandId)
	{
		$result = $this->_repository->getNewItemOptions($brandId);
		$result = collect($result)->keyBy('id')->all();
		
		return $result;
	}
	
	/* ====================== 主流程 ====================== */
	/* 取新品銷售統計
	 * @params: enums
	 * @params: integer
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchNewItemId, $searchStDate, $searchEndDate)
	{
		try
		{
			#1. Calc time
			$stDate		= (new Carbon($searchStDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($searchEndDate))->format('Y-m-d 23:59:59');
			
			#儲存頁面計算天數用日期
			$this->_statistics['startDate'] = (new Carbon($searchStDate))->format('Y-m-d'); #這裏只存日期
			$this->_statistics['endDate'] 	= (new Carbon($searchEndDate))->format('Y-m-d');
			
			#2. Get product params
			list($primaryIds, $secondaryIds, $tastes) = $this->_getParams($searchNewItemId);
						
			#3. Get all shops with area permission
			$shopList = $this->_getShopList($brand);
			
			#4. Get POS data
			$saleData = $this->_getDataFromDB($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes);
			
			#5. Build base data
			$baseData = $this->_buildBaseData($shopList, $saleData);
			
			dd( $this->_outputReport($baseData));
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	/* ====================== 主流程 End ====================== */
	
	
	/* 取ErpNo及條件
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _getParams($searchNewItemId)
	{
		try
		{
			$tastes = $this->_repository->getTasteById($searchNewItemId);
			$result = $this->_repository->getErpNoById($searchNewItemId);
			
			$result = collect($result)->groupBy('isPrimary');
			$primaryIds		= $result[1]->pluck('erpNo')->toArray();
			$secondaryIds 	= empty($result[0]) ? [] : $result[0]->pluck('erpNo')->toArray();
			
			return [$primaryIds, $secondaryIds, $tastes];
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
	private function _getDataFromDB($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes)
	{
		try
		{
			$cacheKey = implode(':', [$brand->code(), $stDate, $endDate]);
		
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get sales data from cache');
				return Cache::get($cacheKey);
			}
			else
			{
				$saleData = $this->_repository->getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $tastes);
				Cache::put($cacheKey, $saleData, now()->addMinutes(60));
				Log::channel('appServiceLog')->info('Get sales data from DB');
				
				return $saleData;
			}
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS DB資料失敗');
		}
	}
	
	/* 取店家並過濾區域權限
	 * @params: collection
	 * @return: array
	 */
	private function _getShopList($brand)
	{
		$currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser['roleArea'];
		$authAreaIds = ($brand == Brand::BAFANG) ? Area::toBafangId($userAreaIds) :  Area::toBuygoodId($userAreaIds);
		
		#Filter區域權限
		$shopList = $this->_repository->getShopList($brand, $authAreaIds);
		
		return $shopList;
	}
	
	/* 先分組成可共用的基底資料
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($shopList, $saleData)
	{
		#要改成所有店家統計,以門店為基礎的資料
		$groupSaleData = collect($saleData)->groupBy('shopId');
		
		$baseData = collect($shopList)->groupBy('shopId')->map(function($item, $key) use($groupSaleData) {
			$temp['shopId']		= $item->pluck('shopId')->first();
			$temp['shopName'] 	= $item->pluck('shopName')->first();
			$temp['areaId'] 	= Area::toId($item->pluck('areaId')->first());
			
			$shopSalesData = data_get($groupSaleData, $temp['shopId'], FALSE);
			
			if ($shopSalesData)
			{
				$temp['dayQty'] = $shopSalesData->mapWithKeys(function($item, $key){
					return [$item['saleDate'] => $item['totalQty']];
				})->toArray();
			}
			else
				$temp['dayQty'] = [];
			
			return $temp; 
		})->toArray();
		
		return $baseData;
		
		/*
		[
		330002 => [
		  "shopId" => "330002"
		  "shopName" => "御廚桃園中山東店"
		  "area" => 1
		  "dayQty" =>  [
			"2025-09-15" => 6.0
			"2025-09-14" => 7.0
			]
		]
		*/
	}
	
	
	
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($baseData)
	{
		try
		{
			#1.計算查詢範圍總天數 (use Date not DateTime)
			$this->_buildDayHeader();
			
			dd($this->_statistics);
			#2.Build base data(所有資料By ShopId)
			$baseData = $this->_buildBaseData($srcData);
			
			#3.Filter By Area (By User Permission)
			$baseData = $this->_filterByAreaPermission($baseData);
			
			/* Statistics Start */
			#6.店別每日銷售
			$this->_statistics['shop'] = $this->_parsingByShop($baseData, $diffDays);
				
			#7.區域彙總
			$this->_statistics['area'] = $this->_parsingByArea($baseData, $diffDays);
				
			#8.當日銷售前10名 | 當日銷售後10名
			list($this->_statistics['top'], $this->_statistics['last']) = $this->_parsingByRanking($baseData, $this->_statistics['endDate']);
			
			return ResponseLib::initialize($this->_statistics)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize($this->_statistics)->fail('解析報表資料發生錯誤');
		}
	}
	
	/* 計算日期天數
	 * @params: 
	 * @return: array
	 */
	private function _buildDayHeader()
	{
		$startDate 	= new Carbon($this->_statistics['startDate']);
		$endDate 	= new Carbon($this->_statistics['endDate']);
		$diffDays = $startDate->diffInDays($endDate) + 1; 
	}
	
	
	
	/* 店別每日銷售
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByShop($baseData, $diffDays)
	{
		/* Output
		[
		330002 => [
		  "shopId" => "330002"
		  "shopName" => "御廚桃園中山東店"
		  "area" => "桃竹苗區"
		  "dayQty" =>  [
			"2025-09-15" => 6.0
			"2025-09-14" => 7.0
			]
		  "totalQty" => 13.0
		  "totalAvg" => 6.5
		]
		*/
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		#基本資料已有, 只要再計算=>銷售總量|平均銷售數量
		$result = Arr::map($baseData, function($value, $key) use($diffDays) {
			
			$value['totalQty'] = array_sum($value['dayQty']); #所有日銷售量總和/店
			$value['totalAvg'] = round($value['totalQty'] / $diffDays, 1); #銷售量總和
			return $value;
		});
		
		$result = Arr::sort($result);
		
		return $result;
	}
	
	/* 區域彙總
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByArea($baseData, $diffDays)
	{
		/*
		"area" => [
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
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		$collection = collect($baseData);
		$data = $collection->groupBy('area')->map(function($item, $key) use($diffDays) {
			$temp['shopCount']		= count($item); #店家數
			$temp['totalQty'] 		= intval($item->pluck('dayQty')->flatten()->sum()); #區域銷售總量
			$temp['avgDayQty'] 		= round($temp['totalQty'] / $diffDays, 1); #區域平均日銷售量: 區域銷售總量/天數
			$temp['avgShopQty'] 	= round($temp['totalQty'] / $temp['shopCount'], 1); #區域每店平均銷量: 區域銷售總量/店家數
			$temp['avgDayShopQty'] 	= round($temp['totalQty'] / $diffDays / $temp['shopCount'], 1); #區域每店平均日銷量: 區域銷售總量/店家數/天數
			
			return $temp;
		})->toArray();
		
		#重排區域的順序以保持顯示一致(系統跑會依抓到資料的順序)
		$result['大台北區'] 	= data_get($data, Area::TAIPEI->value, []);
		$result['宜蘭區'] 	= data_get($data, Area::YILAN->value, []);
		$result['桃竹苗區'] 	= data_get($data, Area::TCM->value, []);
		$result['中彰投區'] 	= data_get($data, Area::CCT->value, []);
		$result['雲嘉南區'] 	= data_get($data, Area::YCN->value, []);
		$result['大高雄區'] 	= data_get($data, Area::KAOHSIUNG->value, []);
		
		$result['total']['shopCount'] 	= collect($data)->pluck('shopCount')->sum(); 
		$result['total']['totalQty'] 	= collect($data)->pluck('totalQty')->sum();
		$result['total']['avgDayQty'] 	= collect($data)->pluck('avgDayQty')->sum();
		$result['total']['avgShopQty'] 	= round($result['total']['totalQty'] / $result['total']['shopCount'], 1); #totalQty / shopCount
		$result['total']['avgDayShopQty']	= round($result['total']['avgDayQty'] / $result['total']['shopCount'], 1); #avgDayQty / shopCount
		
		return $result;
	}
	
	/* 當日銷售前10名
	 * @params: array
	 * @params: date
	 * @return: array
	 */
	private function _parsingByRanking($baseData, $endDate)
	{
		/* 以銷售量來group shop
		[
		29 => [
			0 => [
				"shopId" => "103001"
				"shopName" => "御廚民生承德直營店"
				"area" => "大台北區"
				"dayQty" => array:5 [▶]
				"todayQty" => 29
			]
		]
		*/
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [[], []];
		
		#排名是依最後一天的值
		$collection = collect($baseData);
		$result = $collection->map(function($item, $key) use($endDate) {
			$item['todayQty'] = intval(data_get($item, "dayQty.{$endDate}"));
			return $item;
		});
		
		$top = $result->sortByDesc('todayQty')->groupBy('todayQty')->take(10)->values()->toArray();
		$last = $result->sortBy('todayQty')->groupBy('todayQty')->take(10)->values()->toArray();
		
		return [$top, $last];
	}
	
	/* ===== 暫廢棄 ===== */
	/* CRUD Permission Check for Page
	 * @params: int
	 * @return: boolean
	 *
	 public function getOperationPermission()
	 {
		try
		{
			return $this->allowOperationPermissionList($this->_groupKey, $this->_configKey);
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return [];
		}
	 }*/
	 
	/* ===== 原Business Login, 流程已變更 - 暫廢棄 ===== */
	/* 取新品銷售統計-主流程
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	/*public function processStatistics($cacheKey)
	{
		#initialize
		$statistics = [
			'productName' 	=> '',
			'saleDate' 		=> '',
			'saleEndDate' 	=> '',
			'startDate' 	=> '',
			'endDate' 		=> '',
			'shop' 	=> [],
			'area' 	=> [],
			'top' 	=> [],
			'last' 	=> [],
		];
			
			
		try 
		{
			#1.取新品參數 & Initialize 
			list($productName, $saleDate, $saleEndDate, $startDateTime, $endDateTime, $productIds, $bfProductIds) = $this->_getParams();
			
			$statistics['productName']	= $productName;
			$statistics['saleDate'] 	= (new Carbon($saleDate))->format('Y-m-d');
			$statistics['saleEndDate'] 	= (new Carbon($saleEndDate))->format('Y-m-d');
			$statistics['startDate'] 	= (new Carbon($startDateTime))->format('Y-m-d');
			$statistics['endDate'] 		= (new Carbon($endDateTime))->format('Y-m-d');
			
			#取查詢範圍總天數
			$startDate = new Carbon($startDateTime);
			$endDate = new Carbon($endDateTime);
			$diffDays = $startDate->diffInDays($endDate) + 1; 
			
			#2.Get data from DB(不同品牌DB不同)
			$data = $this->_getData($startDateTime, $endDateTime, $productIds, $bfProductIds);
			
			#3.Parsing to base data(所有資料By ShopId)
			$baseData = $this->_buildBaseData($data);
			
			/* Statistics Start *
			#4.店別每日銷售
			$statistics['shop'] = $this->_parsingByShop($baseData, $diffDays);
			
			#5.區域彙總
			$statistics['area'] = $this->_parsingByArea($baseData, $diffDays);
			
			#6.當日銷售前10名 | 當日銷售後10名
			list($statistics['top'], $statistics['last']) = $this->_parsingByRanking($baseData, $statistics['endDate']);
			
			#7.Save to Cache
			Cache::put($cacheKey, $statistics, now()->addMinutes(30));
			
			return ResponseLib::initialize($statistics)->success();
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize($statistics)->fail($e->getMessage());
		}
	}*/
	/*==============================================================================*/

}
