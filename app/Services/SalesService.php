<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Facades\PosManager;
use App\Repositories\SalesRepository;
use App\Services\Sales\StoreService;
use App\Services\Sales\AreaService;
use App\Libraries\ResponseLib;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use App\Enums\Functions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Exception;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;


#Service BF | BG 共用
class SalesService
{
	private $_statistics	= [];
	
	public function __construct(protected SalesRepository $_repository)
	{
		#default
		$this->_statistics = [
			'brandId'		=> '', #export
			'startDate'		=> '', #Y-m-d
            'endDate'   	=> '',
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
	
	/* ====================== 主流程 ====================== */
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
			if (AppManager::hasAreaPermission() === FALSE)
				return ResponseLib::initialize($this->_statistics)->fail('此使用者無區域瀏覽權限');
			
			#Params都用pass(保留service可複用空間)
			$params = $this->_initParams($brand, $searchStDate, $searchEndDate, $searchCategory, $searchProductIds);
			
			if (Cache::has($params->cacheKey))
			{
				Log::channel('appServiceLog')->info('Get sales data from cache');
				
				$statistics = Cache::get($params->cacheKey); #cache data is response format
				
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get sales data from db');
				
				#Prepare data(object default called by reference)
				$this->_prepareData($params);
				
				#Statistics
				$this->_outputReport($params);
				
				#Create output
				$this->_generateStatistics($params);
				
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
	 * @params: string
	 * @params: string
	 * @params: integer
	 * @params: array
	 * @return: array
	 */
	private function _initParams($brand, $searchStDate, $searchEndDate, $searchCategory, $searchProductIds)
	{
		$params = new Fluent();
		
		$currentUser = AppManager::getCurrentUser();
		$userAreaIds = $currentUser->roleArea;
		
		$searchEndDate 	= empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
		$functions 		= $this->parsingFunction($brand);
		$cacheKey 		= HelperLib::buildCacheKey([$functions->value, $userAreaIds, $searchStDate, $searchEndDate, $searchCategory, $searchProductIds]);
		
		$params->brand($brand)->userAreaIds($userAreaIds)
				->stDate($searchStDate)->endDate($searchEndDate)
				->category($searchCategory)->productIds($searchProductIds)
				->cacheKey($cacheKey);
		
		return $params;
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']		= $params->brand->value;
		$this->_statistics['brandCode']		= $params->brand->code();
		$this->_statistics['startDate'] 	= $params->stDate;
		$this->_statistics['endDate']		= $params->endDate;
		$this->_statistics['shop']			= $params->shop;
		$this->_statistics['area']			= $params->area;
		$this->_statistics['productList']	= $params->productList;
		
		#無值不cache
		if (! empty(Arr::flatten($this->_statistics['shop'])))
		{
			$this->_statistics['exportToken'] = bin2hex($params->cacheKey); #hex2bin
			Cache::put($params->cacheKey, $this->_statistics, now()->addMinutes(10));
		}
	}
	
	/* Get search data
	 * @params: array
	 * @return: array
	 */
	private function _prepareData($params)
	{
		try
		{
			#1. Get product id list for sql
			$this->_getProductParams($params);
			
			#2. Get all shops with area permission
			$this->_buildProductMap($params);
			
			#3. Get all shops with area permission
			$this->_getStoreList($params);
			
			#4.build to base data
			$this->_buildBaseData($params);
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
	private function _getProductParams($params)
	{
		try
		{
			#Dashboard product id
			$productList = $this->_repository->getProductByIds($params->productIds);
			
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
			
			$params->productList	= $productList;
			$params->primaryIds		= $primaryIds;
			$params->secondaryIds 	= $secondaryIds;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析銷售參數發生錯誤');
		}
	}
	
	/* Product list header
	 * @params: collection
	 * @return: array
	 */
	private function _buildProductMap($params)
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
		
		$productList = $params->productList;
		
		#是以DB product table有設定的產品為基礎
		$header =  collect($productList)->mapWithKeys(function ($item, $key) {
			return [$item['productId'] => $item['productName']];
		})->toArray();
		
		$params->productHeader = $header;
	}
	
	/* 門店資料
	 * @params: collection
	 * @return: array
	 */
	private function _getStoreList($params)
	{
		$params->allShopList 	= PosManager::getAllStores($params->brand, $params->userAreaIds); #all shops
		$params->activeShopList = PosManager::getActiveStores($params->brand, $params->userAreaIds); #only active shops
	}
	
	/* Rebuild data format
	 * @params: Fluent
	 * @params: array
	 * @return: array
	 */
	private function _buildBaseData($params)
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
		
		$saleData = array_filter($this->_getDataFromDB($params));
		
		#要改成所有店家統計
		#這裏只要先補全店家資料(無銷售訂單)及所需欄位
		$productList = $params->productList; 
		$allShopList = collect($params->allShopList)->groupBy('shopId');
		
		#過濾無效店家
		$saleData = PosManager::filterExceptStore($params->brand, $saleData);
		
		$baseData = collect($saleData)->map(function($item, $key) use($productList, $allShopList) {
			$shop = $allShopList->get($item['shopId'])->first(); 
			$product = data_get($productList, $item['erpNo'], NULL);
			
			$item['shopName'] 	= is_null($shop) ? 'NotFound' : $shop['shopName'];
			$item['areaId'] 	= is_null($shop) ? 0 : $shop['areaId'];
			$item['areaName']	= $shop['areaName'];
			
			#轉換成系統設定Id and Name
			$item['productId']	= empty($product) ? 0 : $product['productId'];
			$item['productName']= empty($product) ? '' : $product['productName'];
			
			return $item;
		});
		
		#補全未有銷售的門店資料(closedown = 0)
		$saleShopIds = $baseData->pluck('shopId')->unique()->values()->toArray();
		$filloutShops = PosManager::getFillOutStore($params->activeShopList, $saleShopIds);
		
		#因每個統計內容不同, 故無法寫在trait class
		$filloutShops = collect($filloutShops)->map(function($item, $key) {
			$temp['shopId'] 	= $item['shopId'];
			$temp['shopName'] 	= $item['shopName'];
			$temp['erpNo'] 		= '';
			$temp['price_sum'] 	= 0;
			$temp['qty_sum'] 	= 0;
			$temp['areaId'] 	= $item['areaId'];
			$temp['areaName']	= $item['areaName'];
			$temp['productId'] 	= 0;
			$temp['productName']= '';
			
			return $temp;
		});
		
		$params->baseData = $baseData->merge($filloutShops)->toArray();
	}
	
	/* Get buy good data
	 * @params: fluent
	 * @return: array
	 */
	private function _getDataFromDB($params)
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
			
			$brand 			= $params->brand;
			$stDate			= (new Carbon($params->stDate))->format('Y-m-d 00:00:00');
			$endDate 		= (new Carbon($params->endDate))->addDay()->format('Y-m-d H:i:s');
			$primaryIds 	= $params->primaryIds;
			$secondaryIds 	= $params->secondaryIds;
			$userAreaIds 	= $params->userAreaIds;
			
			$result = $this->_repository->getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $userAreaIds);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取POS系統訂單資料失敗');
		}
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.區域
			$areaService = app(AreaService::class);
			$areaService->parsing($params);
			
			#2.店別
			$storeService = app(StoreService::class);
			$storeService->parsing($params);
							
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
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
		$cacheKey = hex2bin($token);
		
		if (! Cache::has($cacheKey))
			return ResponseLib::initialize()->fail('資料已過期，請重新查詢後下載'); #暫不做重查的動作
		
		$currentUser = AppManager::getCurrentUser();
		Log::channel('appServiceLog')->info(Str::replaceArray('?', [$currentUser->getAvailableName(), $cacheKey], '[?]Export sales data-?'));
		
		try
		{
			$areaService	= app(AreaService::class);
			$storeService 	= app(StoreService::class);
			
			$sourceData = Cache::get($cacheKey);
			
			#Build export data
			list($export['區域彙總-數量'], $export['區域彙總-金額']) = $areaService->buildExport($sourceData['area']);
			list($export['店別明細-數量'], $export['店別明細-金額']) = $storeService->buildExport($sourceData['shop']);
			
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

}
