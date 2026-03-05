<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class ProductService
{
	
	public function __construct(protected ProductRepository $_repository)
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
			return ResponseLib::initialize()->fail('讀取帳號清單時發生錯誤');
		}
	}
	
	/* Create role
	 * @params: string
	 * @params: int
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	public function createProduct($brand, $name, $primaryNo, $secondaryNo, $status)
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
			
			$this->_repository->insert($brand, $name, $primaryNo, $secondaryNo, $status);
			
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
	public function updateProduct($id, $brand, $name, $primaryNo, $secondaryNo, $status)
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
			
			$this->_repository->update($id, $brand, $name, $primaryNo, $secondaryNo, $status);
			
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
