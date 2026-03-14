<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\SalesRepository;
use App\Libraries\ShopLib;
use App\Libraries\ResponseLib;
use App\Traits\AuthTrait;
use App\Enums\Brand;
use App\Enums\Area;
use App\Enums\Functions;
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
	private $_statistics	= [];
    
	public function __construct(protected SalesRepository $_repository)
	{
		#default
		$this->_statistics = [
			'brandId'		=> '', #export
			'productList'	=> [],
			'exportToken'	=> '',
			'header'		=> [],
			'shop' 			=> [],
			'area' 			=> [],
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
	
	/* Search data
	 * @params: enum
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function getStatistics($brand, $searchStDate, $searchEndDate)
	{
		try
		{
			#Check cache
			$functions = $this->parsingFunction($brand);
			$searchEndDate = empty($searchEndDate) ? now()->format('Y-m-d') : $searchEndDate;
			$cacheKey = implode(':', [$functions->value, $searchStDate, $searchEndDate]);
			
			if (Cache::has($cacheKey))
			{
				Log::channel('appServiceLog')->info('Get sales data from cache');
				
				$statistics = Cache::get($cacheKey); #cache data is response format
				return ResponseLib::initialize($statistics)->success();
			}
			else
			{
				Log::channel('appServiceLog')->info('Get sales data from db');
				
				$this->_statistics['brandId'] =	$brand->value; 
				$response = $this->_analysisSalesData($brand, $searchStDate, $searchEndDate);
				
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
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _analysisSalesData($brand, $searchStDate, $searchEndDate)
	{
		try
		{
			#1. Calc time
			$stDate		= (new Carbon($searchStDate))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($searchEndDate))->format('Y-m-d 23:59:59');
			
			#2. Get product list
			list($productList, $primaryIds, $secondaryIds) = $this->_getParams($brand);
			$this->_statistics['productList'] = $productList;
			
			$currentUser = AppManager::getCurrentUser();
			$userAreaIds = $currentUser['roleArea']; #
					
			#3. Get all shops with area permission
			$shopList = $this->_getShopList($brand, $userAreaIds);
			
			#4. Get data from DB
			$saleData = $this->_getDataFromDB($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $userAreaIds);
			
			#5-1.Filter by product : 排除不統計的項目
			#$baseData = $this->_filterByProduct($baseData);
			
			#5.build to base data
			$baseData = $this->_buildBaseData($shopList, array_filter($saleData));
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
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	private function _getParams($brand)
	{
		try
		{
			$productList = $this->_repository->getProductList($brand);
			
			#for primary secondary
			$idType = collect($productList)->groupBy('isPrimary');
			$primaryIds		= $idType[1]->pluck('erpNo')->toArray();
			$secondaryIds 	= empty($idType[0]) ? [] : $idType[0]->pluck('erpNo')->toArray();
			
			#重整product list為key-value
			$productList = collect($productList)->groupBy('erpNo')->map(function($item, $key) {
				return $item[0];
			});
			
			return [$productList, $primaryIds, $secondaryIds];
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析產品參數發生錯誤');
		}
	}
	
	/* 取店家並過濾區域權限
	 * @params: collection
	 * @return: array
	 */
	private function _getShopList($brand, $userAreaIds)
	{
		try
		{
			#會Filter區域權限
			$shopList = $this->_repository->getShopList($brand, $userAreaIds);
		
			return $shopList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料發生錯誤');
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
				#"price" => "128"
				#"qty" => "1"
				#"discount" => ".0000"
				"amount" => 111 => price * qty + discount
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
			throw new Exception('讀取DB資料失敗');
		}
	}
	
	/* Rebuild data format
	 * @params: collection
	 * @return: array
	 */
	private function _buildBaseData($shopList, $saleData)
	{
		/* 重整資料格式/命名/區域
		array:11 [
			"shopId" => "103002"
			"shopName" => "御廚重慶北直營店"
			"productId" => "UC06000002"
			"productName" => "炸雞腿飯"
			"amount" => "128"
			"areaId" => 1
			"areaName" => "大台北區"
		]
		*/
		
		#要改成所有店家統計,以門店為基礎補全資料
		$productList = $this->_statistics['productList'];
		$groupSaleData = collect($saleData)->groupBy('shopId');
		
		$baseData = collect($shopList)->map(function($item, $key) use($productList, $groupSaleData) {
			$temp['shopId']		= $item['shopId'];
			$temp['shopName'] 	= $item['shopName'];
			$temp['areaId'] 	= Area::toId($item['areaId']);
			
			#取出此門店的sale db data
			$shopSalesData = data_get($groupSaleData, $temp['shopId'], FALSE);
			
			if (empty($shopSalesData))
			{
				$temp['products'] = [];
			}
			else
			{
				$temp['products'] = $shopSalesData->map(function($item, $key) use($productList) {
					$product = data_get($productList, $item['erpNo'], FALSE);
				
					$item['productId'] = $product['productId'];
					$item['productName'] = $product['productName'];
					return $item;
				})->toArray();
			}
			return $temp; 
		})->toArray();
		dd($baseData[0]);
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
			$this->_statistics['header'] = $this->_buildHeader($baseData);
			dd($this->_statistics);
			
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
	private function _buildHeader($baseData)
	{
		/*
		"UC00000001" => array:3 [▼
			"productName" => "炸排骨(單點)"
			"totalQty" => 6
			"totalAmount" => 560
		]
		*/
		
		$header = collect($baseData)->mapToGroups(function (array $item, int $key){
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
		
		#要改成所有店家統計,以門店為基礎的資料
		/* $groupSaleData = collect($saleData)->groupBy('shopId');
		
		$baseData = collect($shopList)->groupBy('shopId')->map(function($item, $key) use($groupSaleData) {
			$temp['shopId']		= $item->pluck('shopId')->first();
			$temp['shopName'] 	= $item->pluck('shopName')->first();
			$temp['areaId'] 	= Area::toId($item->pluck('areaId')->first());
			
			$shopSalesData = data_get($groupSaleData, $temp['shopId'], FALSE);
			
			if ($shopSalesData)
			{
				$temp['productAmount'] = $shopSalesData->mapWithKeys(function($item, $key){
					return [$item['productId'] => $item['amount']];
				})->toArray();
			}
			else
				$temp['productAmount'] = [];
			
			return $temp; 
		})->toArray();
		
		return $baseData; */
		
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
		/* Output
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
		
		if (! Cache::has($cacheKey))
			return ResponseLib::initialize()->fail('資料已過期，請重新查詢後下載'); #暫不做重查的動作
		
		Log::channel('appServiceLog')->info('Export sales data');
		
		try
		{
			$response = Cache::get($cacheKey);
			$sourceData = $response->data;
			#Build export data
			$area = $this->_buildExportArea($sourceData['header'], $sourceData['area']);
			$shop = $this->_buildExportShop($sourceData['header'], $sourceData['shop']);
			$export = array_merge($area, $shop);
			
			#Write export to file
			$fileName = Str::replace(':', '_', $cacheKey); 
			$fileName = "銷售_{$fileName}.xlsx";
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			foreach($export as $key => $data)
			{
				$sheetName = $this->_getSheetName($key);
				$sheet = ($key == 'areaAmount') ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
				$sheet->setName($sheetName);
				$writer->addRows($data);
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
	 * @params: string
	 * @return: string
	 */
	private function _getSheetName($key)
	{
		return match($key)
		{
			'areaAmount'	=> '區域-金額',
			'areaQty'		=> '區域-數量',
			'shopAmount'	=> '門店-金額',
			'shopQty'		=> '門店-數量',
			default 		=> '統計',
		};
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
		#金額及數量要分開
		$export['areaAmount'] 	= [];
		$export['areaQty'] 		= [];
		
		#Add header
		$headerKeys	= array_keys($header);
		$headerName	= Arr::pluck($header, 'productName');
		$headerName	= Row::fromValues(array_merge(['區域'], $headerName));
		
		#Header相同
		$export['areaAmount'][] = $headerName;
		$export['areaQty'][]	= $headerName;
		
		foreach($areaData as $areaName => $data)
		{
			$rowAmount 	= [];
			$rowQty		= [];
			
			$rowAmount[] = $areaName;
			$rowQty[]	 = $areaName;
			
			foreach($headerKeys as $no)
			{
				$rowAmount[]= Number::currency(intval(data_get($data, "products.{$no}.amount")), precision: 0);
				$rowQty[]	= intval(data_get($data, "products.{$no}.quantity"));
			}
			
			$export['areaAmount'][] = Row::fromValues($rowAmount);
			$export['areaQty'][]	= Row::fromValues($rowQty);
		}
		
		#Total
		/* $row = [];
		foreach($header as $no => $product)
		{
			$rowData = intval(data_get($data, "products.{$no}.{$totalKey}"));
			$row[] 	= ($isCurrency) ? Number::currency($rowData, precision: 0) : $rowData;
		}
		
		$export[] = Row::fromValues($row); */
		
		return $export;
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
		#金額及數量要分開
		$export['shopAmount'] 	= [];
		$export['shopQty'] 		= [];
		
		#Add header
		$headerKeys = array_keys($header);
		$headerName = Arr::pluck($header, 'productName');
		$headerName = Row::fromValues(array_merge(['區域', '門店代號', '門店名稱'], $headerName));
		
		#Header相同
		$export['shopAmount'][] = $headerName;
		$export['shopQty'][]	= $headerName;
		
		foreach($shopData as $data)
		{
			$rowAmount 	= [];
			$rowQty		= [];
			
			$rowAmount[]= $data['area'];
			$rowAmount[]= $data['shopId'];
			$rowAmount[]= $data['shopName'];
			$rowQty[]	= $data['area'];
			$rowQty[]	= $data['shopId'];
			$rowQty[]	= $data['shopName'];
				
			foreach($headerKeys as $no)
			{
				$rowAmount[]= Number::currency(intval(data_get($data, "products.{$no}.amount")), precision: 0);
				$rowQty[]	= intval(data_get($data, "products.{$no}.quantity"));
			}
			
			$export['shopAmount'][]	= Row::fromValues($rowAmount);
			$export['shopQty'][]	= Row::fromValues($rowQty);
		}
		
		return $export;
	}
}
