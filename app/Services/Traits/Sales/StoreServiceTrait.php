<?php

namespace App\Services\Traits\Sales;

use App\Repositories\SalesRepository;
use App\Libraries\HelperLib;
use App\Enums\Brand;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/* 	門店處理 Common function
	處理訂單門市過濾/補全門店
	Sales data沒有先過濾無效門店如:0030
	(取代原來的ShopTrait)
*/
trait StoreServiceTrait
{
	/* 取全部店家
	 * @params: enum
	 * @params: array
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	private function _getAllStores($brand, $userAreaIds, $type = FALSE, $name = FALSE)
	{
		try
		{
			$cacheKey = HelperLib::buildCacheKey([$brand->value, $userAreaIds, $type, $name, 'pos', 'all-stores']);
			
			if (Cache::has($cacheKey))
				return Cache::get($cacheKey); #cache data is response format
			
			#會Filter區域權限及無效門店
			$storeList = $this->_repository->getStoreList($brand, $userAreaIds, $type, $name); #all shops
			Cache::put($cacheKey, $storeList, now()->addMinutes(60));
			
			return $storeList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料發生錯誤');
		}
	}
	
	/* 取有效店家
	 * @params: enum
	 * @params: array
	 * @params: array
	 * @params: string
	 * @return: array
	 */
	private function _getActiveStores($brand, $userAreaIds, $type = FALSE, $name = FALSE)
	{
		try
		{
			#必須要有全部的值, 尤其是areaid
			$cacheKey = HelperLib::buildCacheKey([$brand->value, $userAreaIds, $type, $name, 'pos', 'active-stores']);
			
			if (Cache::has($cacheKey))
				return Cache::get($cacheKey); #cache data is response format
			
			#會Filter區域權限及無效門店
			$storeList = $this->_repository->getHptransStoreList($brand, $userAreaIds, $type, $name); #all shops
			Cache::put($cacheKey, $storeList, now()->addMinutes(60));
			
			return $storeList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料發生錯誤');
		}
	}
	
	/* Filter data by shop
	 * @params: collection
	 * @return: array
	 */
	private function _filterDataByShop($brand, $data)
	{
		$code = $brand->code();
		$excepts = config("web.sales.shop.except.{$code}");
		
		$result = collect($data)->filter(function($item, $key) use($excepts){
			return ! in_array($item['shopId'], $excepts);
		});
		
		return $result;
	}
	
	/* 補全門店判別
	 * @params: collection
	 * @return: array
	 */
	private function _getFillShop($saleShopIds)
	{
		#改用active shop來判過濾即可
		$activeShopList = $this->_shopList['active'];
		
		$result = collect($activeShopList)->filter(function($item, $key) use($saleShopIds) {
			#過濾出無銷售且為active門店
			return ! in_array($item['shopId'], $saleShopIds);
		});
		
		return $result;
	}
}