<?php

namespace App\Services;

use App\Repositories\PurchaseRepository;
use App\Libraries\ShopLib;
use App\Libraries\ResponseLib;
use App\Traits\AuthorizationTrait;
use App\Traits\MenuTrait;
use App\Enums\Brand;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;


class PurchaseService
{
	use AuthorizationTrait;
	
	private $_statistics	= [];
    
	public function __construct(protected PurchaseRepository $_repository)
	{
		$this->_statistics = [
			'brand'		=> '',
			'header'	=> [],
			'shop' 		=> [],
			'area' 		=> [],
		];
	}
	
	/* 取新品銷售統計-入口
	 * @params: string
	 * @params: date
	 * @params: date
	 * @return: array
	 */
	public function search($searchBrand, $searchStDate, $searchEndDate)
	{
		try
		{
			#目前暫只有梁社漢
			if ($searchBrand != Brand::BUYGOOD->value)
				throw new Exception('無品牌代碼');
			
			$this->_statistics['brand'] = $searchBrand;
			
			#1. Get data
			$srcData = [];
			$srcData = $this->_getBaseDataByBg($searchStDate, $searchEndDate);
			
			/*
			if ($searchBrand = Brand::BUYGOOD)
				$srcData = $this->_getBaseDataByBg($searchStDate, $searchEndDate);
			else
				$srcData = $this->_getBaseDataByBf($searchStDate, $searchEndDate);
			*/
			
			$statistics = $this->_outputReport($srcData);
			
			return $statistics;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
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
			
			$baseData = $this->_rebuildBaseData($result);
			
			return $baseData;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取DB資料失敗');
		}
	}
	
	/* Rebuild data format
	 * @params: array
	 * @return: array
	 */
	private function _rebuildBaseData($source)
	{
		/* 重整資料格式, 取區域
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
		
		foreach($source as $row)
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
	private function _outputReport($srcData)
	{
		try
		{
			#1.Filter By Area (By User Permission)
			$baseData = $this->_filterByAreaPermission($srcData);
			
			/* Statistics Start */
			#2.建共用Header, by product
			$this->_statistics['header'] = $this->_buildListHeader($baseData);
			
			#3.By店別
			$this->_statistics['shop'] = $this->_parsingByShop($baseData);
			
			#4.區域彙總
			$this->_statistics['area'] = $this->_parsingByArea($baseData);
				
			return ResponseLib::initialize($this->_statistics)->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize($this->_statistics)->fail('解析報表資料發生錯誤');
		}
	}
	
	/* 區域權限過濾
	 * @params: collection
	 * @return: array
	 */
	private function _filterByAreaPermission($srcData)
	{
		$userInfo = $this->getSigninUserInfo();
		$userAreaIds = $userInfo['area'];
		
		$baseData = Arr::reject($srcData, function ($item, $key) use($userAreaIds) {
			return ! in_array($item['areaId'], $userAreaIds);
		});
		
		return $baseData;
	}
	
	/* List header
	 * @params: collection
	 * @return: array
	 */
	private function _buildListHeader($sourceData)
	{
		$sourceData = collect($sourceData);
		$header = $sourceData->mapToGroups(function (array $item, int $key){
			return [$item['productNo'] => $item['productName']];
		})->map(function($item, int $key){
			return $item[0];
		})->sortKeys()->toArray();
		
		return $header;
	}
	/* By店別進貨統計
	 * @params: collection
	 * @return: array
	 */
	private function _parsingByShop($sourceData)
	{
		/* 重整資料格式
		[
			"orderDate" => "2026-01-08"
			"shopId" => "330005"
			"shopName" => "御廚桃園桃鶯直營店"
			"areaId" => 1
			"area" => "桃竹苗區"
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
		$sourceData = collect($sourceData);
		$result = $sourceData->groupBy('shopId')
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
				"products" => [
					'productNo'
					'productName'
					'unit'
					'quantity'
					'amount'
				]
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
