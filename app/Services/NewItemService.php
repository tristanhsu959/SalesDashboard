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
			
			$list = collect($list)->groupBy('productBrand')->map(function ($item, $key){
				$item = $item->map(function ($item, $key){
					return ['id' => $item['productId'], 'name' => $item['productName']];
				});
				return $item;
			})->toArray();
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return [];
		}
	}
	
	/* Create role
	 * @params: string
	 * @params: int
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function createProduct($brand, $name, $primaryNo, $secondaryNo, $tasteNo, $status)
	{
		try
		{
			$primaryNo = Str::of($primaryNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$secondaryNo = Str::of($secondaryNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$tasteNo = Str::of($tasteNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$this->_repository->insert($brand, $name, $primaryNo, $secondaryNo, $tasteNo, $status);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('新增產品失敗');
		}
	}
	
	/* Get product by id
	 * @params: int
	 * @return: array
	 */
	public function getProductById($id)
	{
		try
		{
			$data = $this->_repository->getById($id);
			
			$result = [];
			#重整資料
			if (! empty($data))
			{
				$collection = collect($data);
				
				$result['productId'] 	= $collection->pluck('productId')->first();
				$result['productBrand'] = $collection->pluck('productBrand')->first();
				$result['productName'] 	= $collection->pluck('productName')->first();
				$result['primaryNo'] 	= $collection->where('isPrimary', TRUE)->pluck('erpNo')->toArray();
				$result['secondaryNo'] 	= $collection->where('isPrimary', FALSE)->pluck('erpNo')->toArray();
				$result['tasteNo'] 		= json_decode($collection->pluck('productTaste')->first(), TRUE);
				$result['productStatus']= $collection->pluck('productStatus')->first();
			}
			
			return ResponseLib::initialize($result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取身份設定資料發生錯誤');
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
	public function updateProduct($id, $brand, $name, $primaryNo, $secondaryNo, $tasteNo, $status)
	{
		try
		{
			$primaryNo = Str::of($primaryNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$secondaryNo = Str::of($secondaryNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$tasteNo = Str::of($tasteNo)->explode("\r\n")
				->reject(function ($value, $key) {
					return empty($value);
			})->toArray();
			
			$this->_repository->update($id, $brand, $name, $primaryNo, $secondaryNo, $tasteNo, $status);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('編輯產品失敗');
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
