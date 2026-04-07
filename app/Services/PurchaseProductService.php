<?php

namespace App\Services;

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
	
	/* Get product for options from new order system
	 * @params: int
	 * @return: array
	 */
	public function getProductList()
	{
		try
		{
			$bfBrandId = Brand::BAFANG;
			$bgBrandId = Brand::BUYGOOD;
			$list = $this->_repository->getProductShortCode($bfBrandId);
			dd($list);
			$list = collect($list)->groupBy('productBrandId')->map(function($items, $key){
				return $items->groupBy('productCategory')->map(function($items, $key){
					return $items->map(function($item, $key){
						$brandId = $item['productBrandId'];
						$catId = $item['productCategory'];
						$item['categoryName'] = config("web.category.{$brandId}.{$catId}");
						return $item;
					});
					return $items;
				});
				
				return $items;
			})->toArray();
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return [];
		}
	}
	
	/* 取設定清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getSetting()
	{
		try
		{
			$list = $this->_repository->getSetting();
			$list = collect($list)->groupBy('salesBrandId')->toArray();
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取銷售設定清單發生錯誤');
		}
	}
	
	
	
	/* Create new item
	 * @params: string
	 * @params: int
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function createSetting($id, $brandId, $name, $status, $productIds)
	{
		try
		{
			$this->_repository->insert($brandId, $name, $status, $productIds);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('銷售設定新增失敗');
		}
	}
	
	/* Get setting by id
	 * @params: int
	 * @return: array
	 */
	public function getSettingById($id)
	{
		try
		{
			$data = $this->_repository->getById($id);
			$data = collect($data)->groupBy('salesId')->map(function($items, $key){
				$items = collect($items);
				
				$temp['salesId'] 		= $items->pluck('salesId')->first();
				$temp['salesBrandId'] 	= $items->pluck('salesBrandId')->first();
				$temp['salesName'] 		= $items->pluck('salesName')->first();
				$temp['salesStatus'] 	= $items->pluck('salesStatus')->first();
				$temp['updateAt'] 		= $items->pluck('updateAt')->first();
				$temp['productIds'] 	= $items->pluck('productId')->values()->filter()->toArray();
				
				return $temp;
			})->first();
			
			return ResponseLib::initialize($data)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取銷售設定時發生錯誤');
		}
	}
	
	/* Update Role
	 * @params: int
	 * @params: int
	 * @params: string
	 * @params: boolean
	 * @params: array
	 * @return: array
	 */
	public function updateSetting($id, $brandId, $name, $status, $productIds)
	{
		try
		{
			$this->_repository->update($id, $brandId, $name, $status, $productIds);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('編輯銷售設定失敗');
		}
	}
	
	/* Remove Role
	 * @params: int
	 * @return: array
	 */
	public function deleteSetting($id)
	{
		try
		{
			$this->_repository->remove($id);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('刪除銷售設定失敗');
		}
	}
	
}
