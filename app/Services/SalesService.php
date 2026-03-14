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
				
				$this->_statistics['brandId']	= $brand->value; 
				$this->_statistics['startDate'] = (new Carbon($searchStDate))->format('Y-m-d'); 
				$this->_statistics['endDate'] 	= (new Carbon($searchEndDate))->format('Y-m-d');
				
				$response = $this->_analysisStatisticsData($brand);
				
				#無值不cache, 只判斷一個就可
				if (! empty($this->_statistics['shop']))
				{
					$this->_statistics['exportToken'] = bin2hex($cacheKey); #hex2bin
					Cache::put($cacheKey, $this->_statistics, now()->addMinutes(60));
				}
				dd($this->_statistics);
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
	private function _analysisStatisticsData($brand)
	{
		try
		{
			#1. Calc time
			$stDate		= (new Carbon($this->_statistics['startDate']))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($this->_statistics['endDate']))->format('Y-m-d 23:59:59');
			
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
			$idType 		= collect($productList)->groupBy('isPrimary');
			$primaryIds		= $idType[1]->pluck('erpNo')->toArray();
			$secondaryIds 	= empty($idType[0]) ? [] : $idType[0]->pluck('erpNo')->toArray();
			
			#重整product list為key-value
			$productList = collect($productList)->groupBy('erpNo')->map(function($item, $key) {
				return $item[0];
			})->toArray();
			
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
			"shopId" => "100001"
			"erpNo" => "UC00000042"
			"price_sum" => "360.0"
			"qty_sum" => "3"
			"shopName" => "御廚中正南昌店"
			"areaId" => 1
			"areaName" => "大台北區"
			"productId" => 2
			"productName" => "橙汁排骨"
		]
		*/
		
		#要改成所有店家統計
		#這裏只要先補全店家資料(無銷售訂單)及所需欄位
		$productList = $this->_statistics['productList'];
		$groupShopList = collect($shopList)->groupBy('shopId');
		
		$baseData = collect($saleData)->map(function($item, $key) use($productList, $groupShopList) {
			$shop = $groupShopList->get($item['shopId']);
			$product = data_get($productList, $item['erpNo'], NULL);
			
			$item['shopName'] 	= $shop->pluck('shopName')->first();
			$item['areaId'] 	= Area::toId($shop->pluck('areaId')->first());
			$item['areaName']	= (Area::tryFrom($item['areaId']))->label();
			$item['productId']	= empty($product) ? 0 : $product['productId'];
			$item['productName']= empty($product) ? '' : $product['productName'];
			
			return $item;
		})->toArray();
		
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
			
			#2.By店別
			$this->_statistics['shop'] = $this->_parsingByShop($baseData);
				
			#3.By區域
			$this->_statistics['area'] = $this->_parsingByArea($baseData);
			dd($this->_statistics);
							
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
		
		#是以DB product table有設定的產品為基礎
		$productList = collect($this->_statistics['productList'])->groupBy('productId');
		$baseData = collect($baseData);
		
		$header = $productList->map(function ($item, $id) use($baseData) {
			$data = $baseData->where('productId', $id);
			
			$temp['productName']= $item->pluck('productName')->first();
			$temp['totalQty'] 	= $data->pluck('qty_sum')->sum();
			$temp['totalAmount']= $data->pluck('price_sum')->sum();
			
			return $temp;
			
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
			$temp['shopId'] 	= $item->pluck('shopId')->get(0);
			$temp['shopName'] 	= $item->pluck('shopName')->get(0);
			$temp['areaId'] 	= $item->pluck('areaId')->get(0);
			$temp['areaName'] 	= $item->pluck('area')->get(0);
				
			$temp['products'] 	= $item->groupBy('productId')->map(function($item, $key){
				$temp['productId'] 		= $item->pluck('productId')->get(0);
				$temp['productName'] 	= $item->pluck('productName')->get(0);
				$temp['totalQty'] 		= $item->sum('qty_sum');
				$temp['totalAmount'] 	= $item->sum('price_sum');
				
				return $temp;
			})->toArray();
				
			return $temp;	
		})->toArray();
	
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
		
		$data = collect($baseData)->groupBy('areaId')
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
