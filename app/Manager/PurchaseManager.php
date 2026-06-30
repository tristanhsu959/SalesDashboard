<?php

namespace App\Manager;

use App\Manager\Repositories\PurchaseRepository;
use App\Libraries\Purchase\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/* New Order sys Common */
class PurchaseManager
{
	public function __construct(protected PurchaseRepository $_repository)
	{
	}
	
	/******************** Store ********************/
	/* Get store data by brand
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getStoreList($brand, $userAreaIds, $stDate = NULL, $endDate = NULL)
	{
		/*0 => array:9 [▼
			"areaId" => 1
			"storeNo" => "TP10600172"
			"storeName" => "老蘿蔔店"
			"posId" => ""
			"closeDate" => null
			"openDate" => "2007-10-02"
			"areaName" => "大台北區"
			"storeKey" => "1060017"
		]
		*/
		
		try
		{
			#取回的close date已+8
			#八方不含蘿蔔(因storeNo是相同的,且不用顯示,若要顯示時只有特殊的蘿蔔要處理)
			$store = $this->_repository->getStoreList($brand, $userAreaIds);
			
			$store = $this->_filterActiveStoreByDate($store, $stDate, $endDate);
			
			return $this->_formatStoreOutput($brand, $store);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* Get store data by brand with LB stores(月初報表才會顯示特殊的蘿蔔店, 其它目前沒有顯示)
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getStoreListWithLb($brand, $userAreaIds, $stDate = NULL, $endDate = NULL)
	{
		try
		{
			#取回data已排除開閉店
			$storeList = $this->getStoreList($brand, $userAreaIds, $stDate, $endDate);
			
			#八方才有蘿蔔
			if ($brand == Brand::BAFANG)
			{
				$lbStoreList = $this->_repository->getLbStoreList($brand, $userAreaIds);
				
				$lbStoreList = $this->_filterActiveStoreByDate($lbStoreList, $stDate, $endDate);
				
				$lbStoreList = $this->_formatStoreOutput($brand, $lbStoreList);
				
				return $this->_mergeStoreOutput($brand, $storeList, $lbStoreList);
			}
			else
				return $storeList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* 開閉店排除依日期
	 * @params: array
	 * @return: array
	 */
	private function _filterActiveStoreByDate($storeList, $stDate, $endDate)
	{
		#排除閉店:有值才檢查,start/end都要有
		if (! empty($stDate) && ! empty($endDate))
		{
			#明日開店,前一天可訂貨, 故要加一天
			$stDate	= Carbon::parse($stDate);
			$endDate= Carbon::parse($endDate)->addDay();
			
			$storeList = collect($storeList)->reject(function($item, $key) use($stDate, $endDate) {
				
				$openDate 	= empty($item['openDate']) ? NULL : Carbon::parse($item['openDate']);
				$closeDate 	= empty($item['closeDate']) ? NULL : Carbon::parse($item['closeDate']);
				
				#排除在開始時間前已閉店
				if (! is_null($closeDate) && $closeDate->lte($stDate))
					return TRUE;
				
				#排除在結束時間後才開店
				if (! is_null($openDate) && $openDate->gt($endDate))
					return TRUE;
				
				return FALSE;
			})->toArray();
		}
		
		return $storeList;
	}
	
	/* 排除廠區學區店(因依情境不同手動呼叫,只針對沒有POS的)
	 * @params: array
	 * @return: array
	 */
	public function filterFactoryStore($storeList)
	{
		return collect($storeList)->reject(function($item, $key) {
			return empty($item['posId']) OR $item['posId'] == 'null';
		})->toArray();
	}
	
	/* Format store output
	 * @params: array
	 * @return: array
	 */
	private function _formatStoreOutput($brand, $storeList)
	{
		#To key-value
		$store = collect($storeList)->map(function($item, $key) use($brand) {
			
			if (is_null($item['posId']) OR $item['posId'] == 'null')
				$item['posId'] =  '';
			
			$area = AreaLib::toArea(intval($item['areaId']));
			
			$item['storeId']	= intval($item['storeId']);
			$item['areaId']		= $area->value;
			$item['areaName'] 	= $area->label();
			#$item['area'] = Str::replace('-八方', '', $item['area']);
			#$item['area'] = Str::replace('-御廚', '', $item['area']);
			
			#要改成有包含蘿蔔, 故要用No來當Key => 只有八方, 御廚不適用, 最後一碼 1=>八方, 2=>蘿蔔
			#台北:10碼, 高雄:9碼(八方/蘿蔔已合併)=>全處理成7碼與舊系統同,才好mapping
			#有些No沒有TP/KH要注意
								
			#存下storeKey
			$item['storeKey'] = $this->buildStoreKey($item['storeNo']);
			
			return $item;
		})->sortBy('areaId')->values()->all();
		
		return $store;
	}
	
	/* Format store output
	 * @params: array
	 * @return: array
	 */
	private function _mergeStoreOutput($brand, $storeList, $lbStoreList)
	{
		$storeKeys = collect($storeList)->pluck('storeKey')->toArray();
		
		#取出單蘿蔔店(如老蘿蔔沒有八方,所以沒有對應的storeKey)
		$lbSpecials = collect($lbStoreList)->filter(function($item, $key) use($storeKeys) {
			return !in_array($item['storeKey'], $storeKeys);
		});
		
		#Merge獨立的蘿蔔店
		$stores = $lbSpecials->merge($storeList)->sortBy('areaId')->toArray();
		
		return $stores;
	}
	
	/* Build store key(新舊系統Mapping)
	 * @params: string
	 * @return: array
	 */
	public function buildStoreKey($storeNo)
	{
		#特殊的蘿蔔店,因不符規則編碼規則,故要先處理
		$lbSpecialStoreNos = config('web.purchase.store.lbSpecialStore');
		$convertNo = data_get($lbSpecialStoreNos, $storeNo, NULL);
		$storeNo = empty($convertNo) ? $storeNo : $convertNo;
		
		#新系統有前置碼/八方有蘿蔔尾碼1&2
		$storeKey = Str::of($storeNo)->replaceStart('TP', '')->replaceStart('KH', '')->replaceStart('TS', '')->replaceStart('RL', '');
		$storeKey = Str::take($storeKey, 7);
		
		return $storeKey;
	}
	
	/* 過濾不計算的門店(如員購)
	 * @params: string
	 * @return: array
	 */
	public function filterOrderByStoreNo($brandId, $baseData)
	{
		$excepts = config("web.purchase.store.except.{$brandId}");
		
		$result = collect($baseData)->filter(function($item, $key) use($excepts){
			return ! in_array($item['storeNo'], $excepts);
		});
		
		return $result;
	}
	
	/* 過濾門店By posId (銷售功能呼叫用)
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function filterStoreByPosId($storeList, $posIds)
	{
		return collect($storeList)->reject(function($item, $key) use($posIds){
			return in_array($item['posId'], $posIds);
		})->all();
	}
	
	/******************** Factory ********************/
	/* 取工廠清單
	 * @params: int
	 * @return: array
	 */
	public function getFactoryList($brandId, $returnMapping = TRUE)
	{
		$factory = $this->_repository->getFactoryList($brandId);
		
		#To key-value
		if ($returnMapping === TRUE)
		{
			$factory = collect($factory)->mapWithKeys(function($item, $key){
				return [$item['factoryNo'] => $item['factoryName']];
			})->toArray();
		}
			
		return $factory;
	}
	
	/******************** Product ********************/
	/* Get product id */
	public function getProductIdByName($brandId, $name)
	{
		$result = $this->_repository->getProductIdByName($brandId, $name);
		return $result;
	}
	
	public function getProductIdByShortCode($brandId, $shortCodes)
	{
		$result = $this->_repository->getProductIdByShortCode($brandId, $shortCodes);
		
		#format to int
		$ids = collect($result)->map(function($item, $key){
			return (int)$item;
		})->toArray();
		
		return $ids;
	}
	
	/* 取對應的Product&Short code mapping
	 * @params: int
	 * @params: boolean
	 * @return: array
	 */
	public function getProductShortCodeMapping($brandId, $returnMapping = TRUE)
	{
		$productMapping = $this->_repository->getProductShortCode($brandId);
		
		if ($returnMapping === TRUE)
		{
			$productMapping = collect($productMapping)->mapWithKeys(function($item, $key){
				return [$item['productNo'] => $item['productName']];
			})->toArray();
		}
		
		return $productMapping;
	}
	
	/* 取對應的Group設定值
	 * @params: string
	 * @return: array
	 */
	public function getGroupByShortCode($brandId, $code)
	{
		$groupConfig = config("web.purchase.product_type.groupPrefix.{$brandId}");
		
		foreach($groupConfig as $config)
		{
			if (Str::startsWith($code, $config['pattern']))
			{
				$group['groupId'] 	= $config['id'];
				$group['groupName'] = $config['name'];
				
				return $group;
			}
		}
		
		return ['groupId' => '', 'groupName' => ''];
	}
	
	/* 取對應的Group設定值
	 * @params: string
	 * @return: array
	 */
	public function getPackagingScale($code)
	{
		$config = config('web.purchase.product_type.packagingScale');
		
		return data_get($config, $code, 1);
	}
	
	
	
}