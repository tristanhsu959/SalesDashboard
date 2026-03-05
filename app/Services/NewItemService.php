<?php

namespace App\Services;

use App\Repositories\NewItemRepository;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class NewItemService
{
	
	public function __construct(protected NewItemRepository $_repository)
	{
	}
	
	/* 取Role清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getList();
			$list = collect($list)->groupBy('newItemBrand')->toArray();
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取新品設定時發生錯誤');
		}
	}
	
	/* Get product for options
	 * @params: int
	 * @return: array
	 */
	public function getProductOptions()
	{
		try
		{
			$list = $this->_repository->getProductSettings();
			$list = collect($list)->groupBy('productBrand')->toArray();
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return [];
		}
	}
	
	/* Create new item
	 * @params: string
	 * @params: int
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function createNewItem($id, $brand, $productId, $name, $saleDate, $tasteKeyWord, $status)
	{
		try
		{
			$tastes = Str::of($tasteKeyWord)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$this->_repository->insert($brand, $productId, $name, $saleDate, $tastes, $status);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('新品新增失敗');
		}
	}
	
	/* Get product by id
	 * @params: int
	 * @return: array
	 */
	public function getNewItemById($id)
	{
		try
		{
			$data = $this->_repository->getById($id);
			
			return ResponseLib::initialize($data)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取新品設定時資料發生錯誤');
		}
	}
	
	/* Update Role
	 * @params: int
	 * @params: string
	 * @params: int
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function updateNewItem($id, $brand, $productId, $name, $saleDate, $tasteKeyWord, $status)
	{
		try
		{
			$tastes = Str::of($tasteKeyWord)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$this->_repository->update($id, $brand, $productId, $name, $saleDate, $tastes, $status);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('編輯新品失敗');
		}
	}
	
	/* Remove Role
	 * @params: int
	 * @return: array
	 */
	public function deleteProduct($id)
	{
		try
		{
			$this->_repository->remove($id);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('刪除產品失敗');
		}
	}
	
}
