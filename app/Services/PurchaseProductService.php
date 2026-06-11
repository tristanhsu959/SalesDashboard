<?php

namespace App\Services;

use App\Facades\PurchaseManager;
use App\Repositories\PurchaseProductRepository;
use App\Libraries\ResponseLib;
use App\Enums\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class PurchaseProductService
{
	public function __construct(protected PurchaseProductRepository $_repository)
	{
	}
	
	/* 取設定清單(要整合Name)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$bfBrandId = Brand::BAFANG->value;
			$bgBrandId = Brand::BUYGOOD->value;
			
			$productMapping[$bfBrandId] = PurchaseManager::getProductShortCodeMapping($bfBrandId);
			$productMapping[$bgBrandId] = PurchaseManager::getProductShortCodeMapping($bgBrandId);
			
			$list = $this->_repository->getSetting();
			
			$list = collect($list)->groupBy('brandId')->map(function($items, $key) use($productMapping) {
				return $items->map(function($item, $key) use($productMapping){
					$item['productName'] = data_get($productMapping, "{$item['brandId']}.{$item['productCode']}", '');
					unset($item['brandId']);
					return $item;
				});
			})->toArray();
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取出貨產品設定清單發生錯誤');
		}
	}
	
	/* Get product for options from new order system(called by vm for options)
	 * called by update
	 * @params: 
	 * @return: array
	 */
	public function getProductList()
	{
		try
		{
			$bfBrandId = Brand::BAFANG->value;
			$bgBrandId = Brand::BUYGOOD->value;
			
			
			#要分開取, 因short code是不分brand
			$list[$bfBrandId] = PurchaseManager::getProductShortCodeMapping($bfBrandId, FALSE);
			$list[$bgBrandId] = PurchaseManager::getProductShortCodeMapping($bgBrandId, FALSE);
			
			#下架沒有被設定成stop, 但erpNo似乎會是空值, 目前是全取
			$list = collect($list)->map(function($items, $brandId) {
				return collect($items)->unique('productNo')->map(function($item, $key) use($brandId){
					
					$group = PurchaseManager::getGroupByShortCode($brandId, $item['productNo']);
					return array_merge($item, $group);
				})->groupBy('groupId')->map(function($items, $key){
					$temp['groupName'] 	= $items->pluck('groupName')->first();
					$temp['products']	= $items->mapWithKeys(function($item, $key) {
						return [$item['productNo'] => $item['productName']];
					})->toArray();
					
					return $temp;
				});
			})->toArray();
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return [];
		}
	}
	
	/* 取設定(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getSetting()
	{
		try
		{
			$list = $this->_repository->getSetting();
			$list = collect($list)->groupBy('brandId')->map(function($items, $key){
				return $items->pluck('productCode');
			})->toArray();
			
			#default init
			if (empty($list[Brand::BAFANG->value]))
				$list[Brand::BAFANG->value] = [];
			
			if (empty($list[Brand::BUYGOOD->value]))
				$list[Brand::BUYGOOD->value] = [];
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取出貨產品設定清單時發生錯誤');
		}
	}
	
	/* Update product
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function updateSetting($productCodes)
	{
		try
		{
			$this->_repository->update($productCodes);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('出貨產品設定失敗');
		}
	}
}
