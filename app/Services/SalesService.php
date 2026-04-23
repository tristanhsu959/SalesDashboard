<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\SalesRepository;
use App\Libraries\ResponseLib;
use App\Traits\AuthTrait;
use App\Services\Traits\Sales\ShopTrait;
use App\Enums\Brand;
use App\Enums\Area;
use App\Enums\Functions;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Exception;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;


#Service BF | BG 共用
class SalesService
{
	use ShopTrait;
	
	private $_statistics	= [];
	private $_shopList		= [];
    
	public function __construct(protected SalesRepository $_repository)
	{
		#default
		$this->_statistics = [
			'brandId'		=> '', #export
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
			'header'		=> [],
			'shop' 			=> [],
			'area' 			=> [],
			'productList'	=> [],
			'exportToken'	=> '',
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
			Brand::BAFANG	=> Functions::BF_SALES, 
			Brand::BUYGOOD	=> Functions::BG_SALES,
        };
	}
	
	/* 取銷售產品設定, 有啟用的產品清單 - sales product setting
	 * @params: int
	 * @return: string
	 */
	public function getEnableProducts($brandId)
	{
		/*0 => array:3 [
			"productId" => 1
			"productName" => "招牌鍋貼"
			"categoryId" => 1
		]*/
		$enableProducts = $this->_repository->getEnableProducts($brandId);
		
		#Build category & product mapping
		#Category list
		$category = collect($enableProducts)->pluck('categoryId')->unique()->mapWithKeys(function($item, $key) use($brandId){
			$name = config("web.sales.category.{$brandId}.$item");
			return [$item => $name];
		})->toArray();
		
		#Product list
		$products = collect($enableProducts)->groupBy('categoryId')->map(function($items, $key){
			return $items->map(function($item, $key){
				$temp['id']		= $item['productId'];
				$temp['name'] 	= $item['productName'];
				return $temp;
			});
			
			return $items;
		})->toArray();
		
		return [$category, $products];
	}
	
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchStDate, $searchEndDate, $searchCategory, $searchProductIds)
	{
		try
		{
			#Check cache
			$functions = $this->parsingFunction($brand);
			$searchEndDate = empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
			$cacheKey = implode(':', [$functions->value, $searchStDate, $searchEndDate, $searchCategory, implode('-', $searchProductIds)]);
			
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get sales data from cache');
				
				$statistics = Cache::get($cacheKey); #cache data is response format
				
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get sales data from db');
				
				$this->_statistics['brandId']	= $brand->value; 
				$this->_statistics['startDate'] = (new Carbon($searchStDate))->format('Y-m-d'); 
				$this->_statistics['endDate'] 	= (new Carbon($searchEndDate))->format('Y-m-d');
				
				$response = $this->_analysisStatisticsData($brand, $searchProductIds); #true/false
				
				#destroy var
				unset($this->_statistics['productList']);
				
				#無值不cache, 只判斷一個就可
				if (! empty($this->_statistics['shop']))
				{
					$this->_statistics['exportToken'] = bin2hex($cacheKey); #hex2bin
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
	
	/* Get search data
	 * @params: enum
	 * @return: array
	 */
	private function _analysisStatisticsData($brand, $productIds)
	{
		try
		{
			#1. Calc time
			$stDate		= (new Carbon($this->_statistics['startDate']))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($this->_statistics['endDate']))->format('Y-m-d 23:59:59');
			
			#2. Get product id list for sql
			list($productList, $primaryIds, $secondaryIds) = $this->_getParams($productIds);
			$this->_statistics['productList'] = $productList;
			
			$currentUser = AppManager::getCurrentUser();
			$userAreaIds = $currentUser['roleArea']; #
					
			#3. Get all shops with area permission
			$this->_getShopList($brand, $userAreaIds);
			
			#4. Get data from DB
			$saleData = $this->_getDataFromDB($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $userAreaIds);
			
			#5.build to base data
			$baseData = $this->_buildBaseData($brand, array_filter($saleData));
			unset($saleData);
			
			#6. output statistics
			return $this->_outputReport($baseData);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception($e->getMessage());
		}
	}
	
	/* 取ErpNo
	 * @params: eunums
	 * @return: array
	 */
	private function _getParams($productIds)
	{
		try
		{
			$productList = $this->_repository->getProductByIds($productIds);
			
			#分開primary & secondary
			$primaryIds = collect($productList)->filter(function($item, $key){
				return $item['isPrimary'];
			})->pluck('erpNo')->toArray();
			
			$secondaryIds = collect($productList)->filter(function($item, $key){
				return ! $item['isPrimary'];
			})->pluck('erpNo')->toArray();
			
			#建立product list為key-value by erpNo, 做為取回資料mapping用
			$productList = collect($productList)->groupBy('erpNo')->map(function($item, $key) {
				$temp['productId'] 	= $item->pluck('productId')->first();
				$temp['productName']= $item->pluck('productName')->first();
				
				return $temp;
			})->toArray();
			
			return [$productList, $primaryIds, $secondaryIds];
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析銷售參數發生錯誤');
		}
	}
	
	
	
	/* Get buy good data
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _getDataFromDB($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $userAreaIds)
	{
		try
		{
			/* Return format */
			/*
			array:9 [
				"shopId" => "103002"
				"productId" => "UC06000002"
				"price_sum" => 111 => price * qty + discount
				"qty_sum" => 99
				"shopName" => "御廚重慶北直營店"
				"gid" => "A01"
				"productName" => "炸雞腿飯"
			]
			*/
			$result = $this->_repository->getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $userAreaIds);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	/* Rebuild data format
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($brand, $saleData)
	{
		/* 重整資料格式/命名/區域
		array:11 [
			"shopId" => "100001"
			"shopName" => "御廚中正南昌店"
			"erpNo" => "UC00000042"
			"price_sum" => "360.0"
			"qty_sum" => "3"
			"areaId" => 1
			"areaName" => "大台北區"
			"productId" => 2
			"productName" => "橙汁排骨"
		]
		*/
		
		#要改成所有店家統計
		#這裏只要先補全店家資料(無銷售訂單)及所需欄位
		$productList = $this->_statistics['productList']; 
		$allShopList = collect($this->_shopList['all'])->groupBy('shopId');
		
		$saleData = $this->_filterDataByShop($brand, $saleData);
		
		$baseData = collect($saleData)->map(function($item, $key) use($productList, $allShopList) {
			$shop = $allShopList->get($item['shopId']); 
			$product = data_get($productList, $item['erpNo'], NULL);
			
			$item['shopName'] 	= is_null($shop) ? '' : $shop->pluck('shopName')->first(); #因group後是array故用pluck
			$item['areaId'] 	= is_null($shop) ? 0 : AreaLib::toId($shop->pluck('areaId')->first());
			$item['areaName']	= (Area::tryFrom($item['areaId']))->label();
			
			#轉換成系統設定Id and Name
			$item['productId']	= empty($product) ? 0 : $product['productId'];
			$item['productName']= empty($product) ? '' : $product['productName'];
			
			return $item;
		});
		
		#補全未有銷售的門店資料(closedown = 0)
		$saleShopIds = $baseData->pluck('shopId')->unique()->values()->toArray();
		$filterShops = $this->_getFillShop($saleShopIds);
		
		#因每個統計內容不同, 故無法寫在trait class
		$filterShops = collect($filterShops)->map(function($item, $key) {
			$temp['shopId'] 	= $item['shopId'];
			$temp['shopName'] 	= $item['shopName'];
			$temp['erpNo'] 		= '';
			$temp['price_sum'] 	= 0;
			$temp['qty_sum'] 	= 0;
			$temp['areaId'] 	= AreaLib::toId($item['areaId']);
			$temp['areaName']	= (Area::tryFrom($temp['areaId']))->label();
			$temp['productId'] 	= 0;
			$temp['productName']= '';
			
			return $temp;
		});
		
		$baseData = $baseData->merge($filterShops)->toArray();
		
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
			$this->_statistics['header'] = $this->_buildHeader();
			
			#2.By店別
			$this->_statistics['shop'] = $this->_parsingByShop($baseData);
				
			#3.By區域
			$this->_statistics['area'] = $this->_parsingByArea($baseData);
							
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* List header
	 * @params: collection
	 * @return: array
	 */
	private function _buildHeader()
	{
		/*
		[ productId => productName
			2 => "橙汁排骨"
			3 => "蕃茄牛三寶"
			4 => "老皮嫩肉"
			5 => "主廚秘製滷肉飯"
			7 => "牛小排飯"
		]
		*/
		
		#是以DB product table有設定的產品為基礎
		$header =  collect($this->_statistics['productList'])->groupBy('productId')->map(function ($item, $id) {
			return $item->pluck('productName')->first();
		})->toArray();
		
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
			"shopId" => "100001"
			"shopName" => "御廚中正南昌店"
			"areaId" => 1
			"areaName" => null
			"products" => array:5 [▼
				2 => array:1 [▼
					"productId" => 2
					"productName" => "橙汁排骨"
					"totalQty" => 15
					"totalAmount" => 2260.0
				]...
			]
		]
		*/
		
		$result = collect($baseData)->groupBy('shopId')->map(function($item, $key) {
			#$temp['shopId'] 	= $item->pluck('shopId')->get(0);
			$temp['shopName'] 	= $item->pluck('shopName')->get(0);
			$temp['areaId'] 	= $item->pluck('areaId')->get(0);
			$temp['areaName'] 	= $item->pluck('areaName')->get(0);
				
			$temp['products'] 	= $item->groupBy('productId')->map(function($item, $key){
				#$temp['productId'] 	= $item->pluck('productId')->get(0);
				#$temp['productName'] 	= $item->pluck('productName')->get(0);
				$temp['totalQty'] 		= $item->sum('qty_sum');
				$temp['totalAmount'] 	= $item->sum('price_sum');
				
				return $temp;
			})->toArray();
				
			return $temp;	
		})->sortBy('areaId')->toArray();
	
		return $result;
	}
	
	/* 區域彙總
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	private function _parsingByArea($baseData)
	{
		/* Output
		"area" => [
			"大台北區" => [
				"totalQty" => 101
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
		
		$result = collect($baseData)->groupBy('areaId')->map(function($item, $key) {
			#區域總計
			$temp['areaName'] 	= $item->pluck('areaName')->get(0);
			$temp['shopCount']	= $item->pluck('shopId')->unique()->count(); #店家數
			
			#By product
			$temp['products'] 	= $item->groupBy('productId')->map(function($item, $key){
				if ($key == 0)
					return [];
				
				$temp['totalQty'] 	= $item->sum('qty_sum');
				$temp['totalAmount']= $item->sum('price_sum');
				
				return $temp;
			})->toArray();
				
			return $temp;
			
		})->sortKeys()->toArray();
		
		#這裏是依header
		$result['total']['areaName']	= '全區合計';
		$result['total']['shopCount'] 	= collect($baseData)->pluck('shopId')->unique()->count(); 
		$result['total']['products'] = collect($baseData)->groupBy('productId')->map(function($item, $key){
			$temp['totalQty'] 	= $item->sum('qty_sum');
			$temp['totalAmount']= $item->sum('price_sum');
			
			return $temp;
		})->toArray();
		
		return $result;
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
		$cacheKey = hex2bin($token);
		
		if (! Cache::has($cacheKey))
			return ResponseLib::initialize()->fail('資料已過期，請重新查詢後下載'); #暫不做重查的動作
		
		$currentUser = AppManager::getCurrentUser();
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->displayName, $cacheKey], '[?]Exportsales data-?'));
		
		try
		{
			$sourceData = Cache::get($cacheKey);
			
			#Build export data
			list($export['區域彙總-數量'], $export['區域彙總-金額']) = $this->_buildExportArea($sourceData['header'], $sourceData['area']);
			list($export['店別明細-數量'], $export['店別明細-金額']) = $this->_buildExportShop($sourceData['header'], $sourceData['shop']);
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['startDate'], $sourceData['endDate']], '?_銷售_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			foreach($export as $sheetName => $sheetData)
			{
				$sheet = ($sheetName == '區域彙總-數量') ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				
				foreach($sheetData as $data)
				{
					$row =  Row::fromValues($data);
					$writer->addRow($row);
				}
			}
			
			$writer->close();
			return ResponseLib::initialize($fileName)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize('檔案下載失敗，請重新查詢')->fail();
		}
	}
	
	/* Build data for export
	 * @params: array
	 * @params: array
	 * @params: array
	 * @params: boolean
	 * @return: array
	 */
	private function _buildExportArea($header, $areaData)
	{
		#標頭都相同, 但要產生數量及金額兩個sheets
		$export['areaQty'] 		= [];
		$export['areaAmount'] 	= [];
		
		$headerProducts = array_merge(['區域', '店家數'], array_values($header));
		
		#Header相同
		$export['areaQty'][]	= $headerProducts;
		$export['areaAmount'][] = $headerProducts;
		
		foreach($areaData as $areaId => $data)
		{
			$rowQty		= [];
			$rowAmount 	= [];
			
			$rowQty[]	 = $data['areaName'];
			$rowAmount[] = $data['areaName'];
			
			$rowQty[]	 = $data['shopCount'];
			$rowAmount[] = $data['shopCount'];
			
			#須依header的順序取資料
			foreach($header as $productId => $productName)
			{
				$rowQty[]	= intval(data_get($data, "products.{$productId}.totalQty", 0));
				$rowAmount[]= Number::currency(intval(data_get($data, "products.{$productId}.totalAmount", 0)), precision: 0);
			}
			
			$export['areaQty'][]	= $rowQty;
			$export['areaAmount'][] = $rowAmount;
		}
		
		return [$export['areaQty'], $export['areaAmount']] ;
	}
	
	/* Build data for export
	 * @params: array
	 * @params: array
	 * @params: array
	 * @params: boolean
	 * @return: array
	 */
	private function _buildExportShop($header, $shopData)
	{
		#標頭都相同, 但要產生數量及金額兩個sheets
		$export['shopQty'] 		= [];
		$export['shopAmount'] 	= [];
		
		$headerProducts = array_merge(['區域', '門店代號', '門店名稱'], array_values($header));
		
		#Header相同
		$export['shopQty'][]	= $headerProducts;
		$export['shopAmount'][] = $headerProducts;
		
		foreach($shopData as $shopId => $data)
		{
			$rowQty		= [];
			$rowAmount 	= [];
			
			$rowQty[]	= $data['areaName'];
			$rowQty[]	= $shopId;
			$rowQty[]	= $data['shopName'];
			
			$rowAmount[]= $data['areaName'];
			$rowAmount[]= $shopId;
			$rowAmount[]= $data['shopName'];
			
			foreach($header as $productId => $productName)
			{
				$rowQty[]	= intval(data_get($data, "products.{$productId}.totalQty", 0));
				$rowAmount[]= Number::currency(intval(data_get($data, "products.{$productId}.totalAmount", 0)), precision: 0);
			}
			
			$export['shopQty'][]	= $rowQty;
			$export['shopAmount'][] = $rowAmount;
		}
		
		return [$export['shopQty'], $export['shopAmount']] ;
	}
}
