<?php

namespace App\Services;

use App\Repositories\SalesRepository;
use App\Libraries\ShopLib;
use App\Libraries\ResponseLib;
use App\Traits\AuthTrait;
use App\Enums\Brand;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Exception;

#Service BF | BG 共用
class SalesService
{
	use AuthTrait;
	
	private $_statistics	= [];
    
	public function __construct(protected SalesRepository $_repository)
	{
		#default
		$this->_statistics = [
			'exportToken'	=> '',
			'header'		=> [],
			'shop' 			=> [],
			'area' 			=> [],
		];
	}
	
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function search($searchBrand, $searchStDate, $searchEndDate)
	{
		#Check cache
		$cacheKey = implode(':', [$searchBrand->value, $searchStDate, $searchEndDate]);
		
		if (Cache::has($cacheKey))
		{
			Log::channel('appServiceLog')->info('Get sales data from cache');
			return Cache::get($cacheKey); #cache data is response format
		}
		else
		{
			Log::channel('appServiceLog')->info('Get sales data from db');
			
			$this->_statistics['exportToken'] = Crypt::encryptString($cacheKey); #同字串會不同,匯出用
			$response = $this->_analysisSalesData($searchBrand, $searchStDate, $searchEndDate);
			
			#成功才存
			if ($response->status === TRUE)
				Cache::put($cacheKey, $response, now()->addMinutes(60));
			
			return $response;
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
		#取資料邏輯共用
		$cacheKey = Crypt::decryptString($token);
		
		if (Cache::has($cacheKey))
		{
			Log::channel('appServiceLog')->info('Export sales data from cache');
			$response = Cache::get($cacheKey);
			dd($response);
			#Process export
		}
		else
			return ResponseLib::initialize($this->_statistics)->fail('資料已過期，請重新查詢後下載'); #暫不做重查的動作
	}
	
	
	/* 取新品銷售統計-入口
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _analysisSalesData($searchBrand, $searchStDate, $searchEndDate)
	{
		try
		{
			#目前暫只有梁社漢
			#1.Check brand code
			if ($searchBrand->value != Brand::BAFANG->value && $searchBrand->value != Brand::BUYGOOD->value)
				throw new Exception('無品牌代碼');
			
			#2. Get data from DB
			$srcData = [];
			
			/*if ($searchBrand = Brand::BAFANG)
				$srcData = $this->_getBaseDataByBf($searchStDate, $searchEndDate);
			else*/
			$srcData = $this->_getBaseDataByBg($searchStDate, $searchEndDate);
			
			#3.refactor source data format
			$baseData = $this->_rebuildBaseData($srcData);
			
			#4.Filter by area (By User Permission)
			$baseData = $this->_filterByAreaPermission($baseData);
			
			#4-1.Filter by product : 排除不統計的項目
			#$baseData = $this->_filterByProduct($baseData);
			
			/* Statistics Start */
			#5.建共用Header, by product
			$this->_statistics['header'] = $this->_buildListHeader($baseData);
			
			#6.By店別
			$this->_statistics['shop'] = $this->_parsingByShop($baseData);
			
			#7.區域彙總
			$this->_statistics['area'] = $this->_parsingByArea($baseData);
				
			return ResponseLib::initialize($this->_statistics)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize($this->_statistics)->fail($e->getMessage());
		}
	}
	
	/* Get buy good data
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _getBaseDataByBg($searchStDate, $searchEndDate)
	{
		try
		{
			/* Return format */
			/*
			array:9 [
			  "SHOP_ID" => "100002"
			  "PROD_ID" => "UC06100003"
			  "QTY" => "1.0000"
			  "SALE_PRICE" => "125.0000"
			  "ITEM_DISC" => ".0000"
			  "TASTE_MEMO" => ""
			  "SALE_DATE" => "2026-01-15 08:29:54.000"
			  "SHOP_NAME" => "御廚中正濟南店"
			  "PROD_NAME1" => "滷排骨飯"
			]
			*/
			
			$result = $this->_repository->getBgSaleData($searchStDate, $searchEndDate);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取DB資料失敗');
		}
	}
	
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($srcData)
	{
		try
		{
			
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize($this->_statistics)->fail('解析報表資料發生錯誤');
		}
	}
	
	/* Rebuild data format
	 * @params: array
	 * @return: array
	 */
	private function _rebuildBaseData($srcData)
	{
		/* 重整資料格式/命名/區域
		array:11 [
			"orderDate" => "2026-01-16"
			"shopId" => "328001"
			"shopName" => "御廚桃園新坡店"
			"productNo" => "UC06000031"
			"productName" => "椒麻雞飯"
			"price" => 138
			"quantity" => 3
			"discount" => 0
			"taste" => ""
			"areaId" => 3
			"area" => "桃竹苗區"
		]
		*/
		
		$baseData = [];
		
		foreach($srcData as $key=>$row)
		{
			$item = [];
			$item['orderDate']	= Str::before($row['SALE_DATE'], ' '); #Y-m-d
			$item['shopId']		= $row['SHOP_ID'];
			$item['shopName'] 	= $row['SHOP_NAME'];
			$item['productNo'] 	= $row['PROD_ID'];
			$item['productName']= $row['PROD_NAME1'];
			$item['price'] 		= intval($row['SALE_PRICE']);
			$item['quantity'] 	= intval($row['QTY']);
			$item['discount'] 	= intval($row['ITEM_DISC']); #折扣
			$item['hasGravy'] 	= Str::contains($row['TASTE_MEMO'], '秘製滷肉汁'); 
			$item['areaId']		= ShopLib::getAreaIdByShopId($item['shopId']); #過濾用
			$item['area']		= ShopLib::getAreaByShopId($item['shopId']);
			
			$baseData[] = $item;
		}
		
		return $baseData;
	}
	
	/* 區域權限過濾
	 * @params: collection
	 * @return: array
	 */
	private function _filterByAreaPermission($baseData)
	{
		$currentUser = $this->getCurrentUser();
		$userAreaIds = $currentUser->roleArea;
		
		$baseData = Arr::reject($baseData, function ($item, $key) use($userAreaIds) {
			return ! in_array($item['areaId'], $userAreaIds);
		});
		
		return $baseData;
	}
	
	/* 銷售過濾-暫保留還沒用到
	 * @params: collection
	 * @return: array
	 */
	/*private function _filterByProduct($srcData)
	{
		#過濾物品類(目前不確定規則)
		$baseData = Arr::reject($srcData, function ($item, $key) {
			$productNo = intval($item['productNo']);
			return ($productNo >= 6000 && $productNo <= 9999);
		});
		
		return $baseData;
	}*/
	
	/* List header
	 * @params: collection
	 * @return: array
	 */
	private function _buildListHeader($baseData)
	{
		/*
		"UC00000001" => array:3 [▼
			"productName" => "炸排骨(單點)"
			"totalQty" => 6
			"totalAmount" => 560
		]
		*/
		
		$collection = collect($baseData);
		$header = $collection->mapToGroups(function (array $item, int $key){
			$temp['productName']= $item['productName'];
			$temp['quantity'] 	= $item['quantity'];
			$temp['amount'] 	= $item['price'] * $item['quantity'] + $item['discount']; #單價會不同? discount是負數
			return [$item['productNo'] => $temp];
			
		})->map(function($item, $key){
			$data = collect($item);
			$temp['productName']= $data->pluck('productName')->first();
			$temp['totalQty'] 	= $data->pluck('quantity')->sum();
			$temp['totalAmount']= $data->pluck('amount')->sum();
			return $temp;
		})->sortKeys()->toArray();
		
		return $header;
	}
	
	/* By店別進貨統計
	 * @params: collection
	 * @return: array
	 */
	private function _parsingByShop($baseData)
	{
		/* 重整資料格式
		array:6 [
			"orderDate" => "2026-01-16"
			"shopId" => "328001"
			"shopName" => "御廚桃園新坡店"
			"areaId" => 3
			"area" => "桃竹苗區"
			"products" => array:34 [▼
				"UC00000001" => array:4 [▼
					"productNo" => "UC00000001"
					"productName" => "炸排骨(單點)"
					"quantity" => 1
					"amount" => 95
				]
			]...
		]
		*/
		$collection = collect($baseData);
		$result = $collection->groupBy('shopId')
			->map(function($item, $key) {
				$temp['orderDate'] 	= $item->pluck('orderDate')->get(0);
				$temp['shopId'] 	= $item->pluck('shopId')->get(0);
				$temp['shopName'] 	= $item->pluck('shopName')->get(0);
				$temp['areaId'] 	= $item->pluck('areaId')->get(0);
				$temp['area'] 		= $item->pluck('area')->get(0);
				
				$temp['products'] 	= $item->groupBy('productNo')
					->map(function($item, $key){
						$temp['productNo'] 		= $item->pluck('productNo')->get(0);
						$temp['productName'] 	= $item->pluck('productName')->get(0);
						$temp['quantity'] 		= $item->sum('quantity');
						$temp['amount'] 		= $item->sum(function($item){
							return $item['price'] * $item['quantity'] + $item['discount']; #單價會不同? discount是負數
						});
						
						$item = $temp;
						return $item;
					})->sortKeys()->toArray();
				
				$item = $temp;	
				return $item;
				
			})->toArray();
			
		#全轉成array回傳
		return $result;
	}
	
	/* 區域彙總
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByArea($baseData)
	{
		/*
		"area" => [
			"大台北區" => [
				"totalAmount" => 101
				"products" => productNo => [
					'productNo'
					'productName'
					'unit'
					'quantity'
					'amount'
				], ....
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
		
		/* 重整資料格式/命名/區域
		array:11 [
			"orderDate" => "2026-01-16"
			"shopId" => "328001"
			"shopName" => "御廚桃園新坡店"
			"productNo" => "UC06000031"
			"productName" => "椒麻雞飯"
			"price" => 138
			"quantity" => 3
			"discount" => 0
			"taste" => ""
			"areaId" => 3
			"area" => "桃竹苗區"
		]
		*/
		
		$collection = collect($baseData);
		$data = $collection->groupBy('areaId')
			->map(function($item, $key) {
				$temp['products'] 	= $item->groupBy('productNo')
					->map(function($item, $key){
						$temp['productNo'] 		= $item->pluck('productNo')->get(0);
						$temp['productName'] 	= $item->pluck('productName')->get(0);
						$temp['quantity'] 		= $item->sum('quantity');
						$temp['amount'] 		= $item->sum(function($item){
							return $item['price'] * $item['quantity'] + $item['discount']; #單價會不同? discount是負數
						});
						
						$item = $temp;
						return $item;
					})->sortKeys()->toArray();
				
				$item = $temp;	
				return $item;
				
			})->toArray();
		
		#重排區域的順序以保持顯示一致(系統跑會依抓到資料的順序)
		$result[Area::TAIPEI->label()] 		= data_get($data, Area::TAIPEI->value, []);
		$result[Area::YILAN->label()] 		= data_get($data, Area::YILAN->value, []);
		$result[Area::TCM->label()] 		= data_get($data, Area::TCM->value, []);
		$result[Area::CCT->label()] 		= data_get($data, Area::CCT->value, []);
		$result[Area::YCN->label()] 		= data_get($data, Area::YCN->value, []);
		$result[Area::KAOHSIUNG->label()] 	= data_get($data, Area::KAOHSIUNG->value, []);
		
		return $result;
	}
}
