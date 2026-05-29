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
	
	/* 取對應nOrder的設定值
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getOpCenterNo($brandId)
	{
		#台北/高雄
		if ($brandId == Brand::BAFANG->value OR $brandId == Brand::BUYGOOD->value)
			return OpCenter::toValueArray();
		
		return [];
	}
	
	public function getBrandNo($brandId)
	{
		$brand = Brand::tryFrom($brandId);
		return $brand->shortCode();
	}
	
	public function getFactoryNo($brandId)
	{
		$brand = Brand::tryFrom($brandId);
		if ($brandId == Brand::BAFANG->value)
			return [Factory::TP->value, Factory::KH->value];
		else
			return [Factory::TS->value, Factory::RL->value];
	}
	
	/* Get store data by brand
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getStoreList($brand, $userAreaIds, $stDate = NULL, $endDate = NULL)
	{
		try
		{
			#取回的close date已+8
			$store = $this->_repository->getStoreList($brand, $userAreaIds);
			
			#排除閉店:有值才檢查,start/end都要有
			if (! empty($stDate) && ! empty($endDate))
			{
				$stDate	= Carbon::parse($stDate);
				$endDate= Carbon::parse($endDate);
				
				$store = collect($store)->reject(function($item, $key) use($stDate, $endDate) {
					
					$openDate 	= empty($item['openDate']) ? NULL : Carbon::parse($item['openDate']);
					$closeDate 	= empty($item['closeDate']) ? NULL : Carbon::parse($item['closeDate']);
					
					#在開始時間前己閉店
					if (! is_null($closeDate) && $closeDate->lte($stDate))
						return TRUE;
					
					#在結束時間後才開店
					if (! is_null($openDate) && $openDate->gt($endDate))
						return TRUE;
				});
			}
			
			return $this->_formatStoreOutput($brand, $store);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* Get store data by brand with LB stores
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getStoreListWithLb($brand, $userAreaIds)
	{
		try
		{
			$storeList = $this->_repository->getStoreList($brand, $userAreaIds);
			
			#八方才有蘿蔔
			if ($brand == Brand::BAFANG)
			{
				$lbStoreList = $this->_repository->getLbStoreList($brand, $userAreaIds);
				return $this->_mergeStoreOutput($brand, $storeList, $lbStoreList);
			}
			else
				return $this->_formatStoreOutput($brand, $storeList);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* Format store output
	 * @params: array
	 * @return: array
	 */
	private function _mergeStoreOutput($brand, $storeList, $lbStoreList)
	{
		#To key-value
		$storeList 		= $this->_formatStoreOutput($brand, $storeList);
		$lbStoreList 	= $this->_formatStoreOutput($brand, $lbStoreList);
		
		$lbSpecialStore = config('web.purchase.store.lbSpecialStore');
		
		#特殊的蘿蔔店(只有蘿蔔的情境)
		$lbExcepts = collect($lbStoreList)->filter(function($item, $key) use($lbSpecialStore) {
			return in_array($item['storeNo'], array_keys($lbSpecialStore));
		})->toArray();
		
		$storeKeys = array_merge(array_keys($storeList), array_keys($lbExcepts));
		
		$lbStoreList = collect($lbStoreList)->filter(function($item, $key) use($storeKeys) {
			return ! in_array($key, $storeKeys);
		});
		
		$stores = $lbStoreList->merge($storeList)->sortBy('areaId')->toArray();
		
		#過濾出獨立的蘿蔔店
		return $stores;
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
			
			$item['areaId']		= $area->value;
			$item['areaName'] 	= $area->label();
			#$item['area'] = Str::replace('-八方', '', $item['area']);
			#$item['area'] = Str::replace('-御廚', '', $item['area']);
			
			#要改成有包含蘿蔔, 故要用No來當Key => 只有八方, 御廚不適用, 最後一碼 1=>八方, 2=>蘿蔔
			#台北:10碼, 高雄:9碼(八方/蘿蔔已合併)=>全處理成7碼與舊系統同,才好mapping
			#有些No沒有TP/KH要注意
				
			$storeKey = Str::of($item['storeNo'])->replaceStart('TP', '')->replaceStart('KH', '')
							->replaceStart('TS', '')->replaceStart('RL', '');
			$storeKey = Str::take($storeKey, 7);
								
			#存下storeKey
			$item['storeKey'] = $storeKey;
			
			return $item;
		})->sortBy('areaId')->values()->all();
		
		return $store;
	}
	
	/* 取工廠清單
	 * @params: int
	 * @return: array
	 */
	public function getFactoryList($brandId)
	{
		$factory = $this->_repository->getFactoryList($brandId);
		
		#To key-value
		$factory = collect($factory)->mapWithKeys(function($item, $key){
			return [$item['factoryNo'] => $item['factoryName']];
		})->toArray();
			
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
		return $result;
	}
	
	/* 取對應的Product&Short code mapping
	 * @params: string
	 * @return: array
	 */
	public function getProductShortCodeMapping($brandId)
	{
		$result = $this->_repository->getProductShortCode($brandId);
		
		#default format
		$productMapping = collect($result)->mapWithKeys(function($item, $key){
			return [$item['productNo'] => $item['productName']];
		})->toArray();
		
		return $productMapping;
	}
	
	/* 取對應的Group設定值
	 * @params: string
	 * @return: array
	 */
	public function getGroupByShortCode($code)
	{
		$groupConfig = config('web.purchase.product_type.groupPrefix');
		
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