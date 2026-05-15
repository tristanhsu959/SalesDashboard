<?php

namespace App\Services\Traits\Purchase;

use App\Libraries\Purchase\AreaLib;
use App\Enums\Brand;
use Illuminate\Support\Str;

/* nOrder Common */
trait StoreServiceTrait
{
	/* Get store data by brand
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getStoreList($brand, $userAreaIds)
	{
		try
		{
			$store = $this->_repository->getStoreList($brand, $userAreaIds);
			
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
		$store = collect($storeList)->mapWithKeys(function($item, $key) use($brand) {
			
			if (is_null($item['posId']) OR $item['posId'] == 'null')
				$item['posId'] =  '';
			
			$area = AreaLib::toArea(intval($item['areaId']));
			$item['areaId']		= $area->value;
			$item['areaName'] 	= $area->label();
			#$item['area'] = Str::replace('-八方', '', $item['area']);
			#$item['area'] = Str::replace('-御廚', '', $item['area']);
			
			#要改成有包含蘿蔔, 故要用No來當Key => 只有八方, 御廚不適用, 最後一碼 1=>八方, 2=>蘿蔔
			#台北:10碼, 高雄:9碼(八方/蘿蔔已合併)
			if ($brand == Brand::BAFANG)
				$storeKey = Str::take($item['storeNo'], 9);
			else
				$storeKey = $item['storeNo'];
			
			return [$storeKey => $item];
		})->sortBy('areaId')->toArray();
		
		return $store;
	}
}