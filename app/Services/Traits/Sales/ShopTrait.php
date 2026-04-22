<?php

namespace App\Services\Traits\Sales;

use App\Repositories\SalesRepository;
use App\Enums\Brand;
use Illuminate\Support\Str;

/* 	門店處理 Common function
	處理訂單門市過濾/補全門店
	Sales data沒有先過濾無效門店如:0030
*/
trait ShopTrait
{
	/* 取店家並過濾區域權限
	 * @params: collection
	 * @return: array
	 */
	private function _getShopList($brand, $userAreaIds)
	{
		$this->_shopList['all']		= [];
		$this->_shopList['active'] 	= [];
			
		try
		{
			#會Filter區域權限及無效門店
			$shopList 		= $this->_repository->getShopList($brand, $userAreaIds); #all shops
			$activeShopList = $this->_repository->getHptransShopList($brand, $userAreaIds); #only active shops
			
			$this->_shopList['all']		= $shopList;
			$this->_shopList['active'] 	= $activeShopList;
			
			return TRUE;
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