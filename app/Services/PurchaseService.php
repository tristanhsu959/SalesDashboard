<?php

namespace App\Services;

use App\Repositories\PurchaseRepository;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Exception;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;


#Service BF | BG 共用
class PurchaseService
{
	use AuthTrait;
	
	private $_statistics	= [];
    
	public function __construct(protected PurchaseRepository $_repository)
	{
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
	
	/* Get search data
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
			
			#3. Rebuild data format
			$baseData = $this->_rebuildBaseData($srcData);
			
			#4.Filter by area (By User Permission)
			$baseData = $this->_filterByAreaPermission($baseData);
			
			#5.Filter by product : 排除不統計的項目
			$baseData = $this->_filterByProduct($baseData);
			
			/* Statistics Start */
			#6.建共用Header, by product
			$this->_statistics['header'] = $this->_buildListHeader($baseData);
			
			#7.By店別
			$this->_statistics['shop'] = $this->_parsingByShop($baseData);
			
			#8.區域彙總
			$this->_statistics['area'] = $this->_parsingByArea($baseData);
				
			return ResponseLib::initialize($this->_statistics)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
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
			array:8 [
			  "OrderDate" => "2026-01-08 00:00:00.000"
			  "AccNo" => "330U005"
			  "AccName" => "御廚桃園桃鶯直營店"
			  "ProductNo" => "2052"
			  "ProductName" => "紅蘿蔔絲(御)"
			  "Unit" => "包"
			  "Amount" => "1.00"
			  "Money" => "36.00"
			]
			*/
			
			$tsData = $this->_repository->getDataFromTS($searchStDate, $searchEndDate);
			$rlData = $this->_repository->getDataFromRL($searchStDate, $searchEndDate);	
			$result = array_merge($tsData, $rlData);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取DB資料失敗');
		}
	}
	
	/* Rebuild data format
	 * @params: array
	 * @return: array
	 */
	private function _rebuildBaseData($srcData)
	{
		/* 重整資料格式/命名/區域
		[
			"orderData" => "2026-01-08"
			"shopId" => "106004"
			"shopName" => "御廚大安一直營店"
			"productNo" => "4402"
			"productName" => "八方御露油膏"
			"unit" => "罐"
			"quantity" => "1.00"
			"amount" => "170.00"
			"areaId" => 1
			"area" => "大台北區"
		]
		*/
		
		$baseData = [];
		
		foreach($srcData as $row)
		{
			$item = [];
			$item['orderDate']	= Str::before($row['OrderDate'], ' ');
			$item['shopId']		= Str::replace('U', '', $row['AccNo']);;
			$item['shopName'] 	= $row['AccName'];
			$item['productNo'] 	= $row['ProductNo'];
			$item['productName']= $row['ProductName'];
			$item['unit'] 		= $row['Unit'];
			$item['quantity'] 	= $row['Amount'];
			$item['amount'] 	= $row['Money'];
			$item['areaId']		= ShopLib::getAreaIdByShopId($item['shopId']); #過濾用
			$item['area']		= ShopLib::getAreaByShopId($item['shopId']);
			
			$baseData[] = $item;
		}
		
		return $baseData;
	}
	
	/* 取使用者可讀取區域資料(原主邏輯不動)
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($baseData)
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
	
	/* Product過濾
	 * @params: collection
	 * @return: array
	 */
	private function _filterByProduct($baseData)
	{
		#過濾物品類(目前不確定規則)
		$baseData = Arr::reject($baseData, function ($item, $key) {
			$productNo = intval($item['productNo']);
			return ($productNo >= 6000 && $productNo <= 9999);
		});
		
		return $baseData;
	}
	
	/* List header
	 * @params: collection
	 * @return: array
	 */
	private function _buildListHeader($baseData)
	{
		$collection = collect($baseData);
		$header = $collection->mapToGroups(function (array $item, int $key){
			$temp['productName']= $item['productName'];
			$temp['unit']		= $item['unit'];
			$temp['quantity'] 	= $item['quantity'];
			$temp['amount'] 	= $item['amount'];
			return [$item['productNo'] => $temp];
			
		})->map(function($item, int $key){
			$data = collect($item);
			$temp['productName']= $data->pluck('productName')->first();
			$temp['unit']		= $data->pluck('unit')->first();
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
		[
			"orderDate" => "2026-01-08"
			"shopId" => "330005"
			"shopName" => "御廚桃園桃鶯直營店"
			"areaId" => 1
			"area" => "桃竹苗區"
			"totalAmount" => 123
			"products" => array:43 [
				2052 => array:5 [
					"productNo" => "2052"
					"productName" => "紅蘿蔔絲(御)"
					"unit" => "包"
					"quantity" => 2.0
					"amount" => 72.0
				], ...
			]
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
				$temp['totalAmount']= $item->pluck('amount')->sum();
				
				$temp['products'] 	= $item->groupBy('productNo')
					->map(function($item, $key){
						$temp['productNo'] 		= $item->pluck('productNo')->get(0);
						$temp['productName'] 	= $item->pluck('productName')->get(0);
						$temp['unit'] 			= $item->pluck('unit')->get(0);
						$temp['quantity'] 		= $item->sum('quantity');
						$temp['amount'] 		= $item->sum('amount');
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
		
		$collection = collect($baseData);
		$data = $collection->groupBy('areaId')
			->map(function($item, $key) {
				$temp['totalAmount']	= $item->pluck('amount')->sum();
				$temp['totalQuantity']	= $item->pluck('quantity')->sum();
				
				$temp['products'] 	= $item->groupBy('productNo')
					->map(function($item, $key){ 
						$temp['productNo'] 		= $item->pluck('productNo')->get(0);
						$temp['productName'] 	= $item->pluck('productName')->get(0);
						$temp['unit'] 			= $item->pluck('unit')->get(0);
						$temp['quantity'] 		= $item->sum('quantity');
						$temp['amount'] 		= $item->sum('amount');
						
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
			$fileName = "出貨_{$fileName}.xlsx";
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			#目前需求不顯示金額
			foreach($export as $key => $data)
			{
				$sheetName = $this->_getSheetName($key);
				$sheet = ($key == 'areaQty') ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
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
		#$export['areaAmount'] 	= [];
		$export['areaQty'] 		= [];
		
		#Add header
		$headerKeys	= array_keys($header);
		$headerName	= Arr::pluck($header, 'productName');
		$headerName	= Row::fromValues(array_merge(['區域'], $headerName));
		
		#Header相同
		#$export['areaAmount'][] = $headerName;
		$export['areaQty'][]	= $headerName;
		
		foreach($areaData as $areaName => $data)
		{
			#$rowAmount 	= [];
			$rowQty		= [];
			
			#$rowAmount[] = $areaName;
			$rowQty[]	 = $areaName;
			
			foreach($headerKeys as $no)
			{
				#$rowAmount[]= Number::currency(intval(data_get($data, "products.{$no}.amount")), precision: 0);
				$rowQty[]	= intval(data_get($data, "products.{$no}.quantity"));
			}
			
			#$export['areaAmount'][] = Row::fromValues($rowAmount);
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
		#$export['shopAmount'] 	= [];
		$export['shopQty'] 		= [];
		
		#Add header
		$headerKeys = array_keys($header);
		$headerName = Arr::pluck($header, 'productName');
		$headerName = Row::fromValues(array_merge(['區域', '門店代號', '門店名稱'], $headerName));
		
		#Header相同
		#$export['shopAmount'][] = $headerName;
		$export['shopQty'][]	= $headerName;
		
		foreach($shopData as $data)
		{
			#$rowAmount 	= [];
			$rowQty		= [];
			
			#$rowAmount[]= $data['area'];
			#$rowAmount[]= $data['shopId'];
			#$rowAmount[]= $data['shopName'];
			$rowQty[]	= $data['area'];
			$rowQty[]	= $data['shopId'];
			$rowQty[]	= $data['shopName'];
				
			foreach($headerKeys as $no)
			{
				#$rowAmount[]= Number::currency(intval(data_get($data, "products.{$no}.amount")), precision: 0);
				$rowQty[]	= intval(data_get($data, "products.{$no}.quantity"));
			}
			
			#$export['shopAmount'][]	= Row::fromValues($rowAmount);
			$export['shopQty'][]	= Row::fromValues($rowQty);
		}
		
		return $export;
	}
}
