<?php

namespace App\Services;

use App\Repositories\SalesSettingRepository;
use App\Libraries\ResponseLib;
use App\Enums\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class SalesSettingService
{
	
	public function __construct(protected SalesSettingRepository $_repository)
	{
	}
	
	/* 取銷售設定清單
	 * @params: 
	 * @return: array
	 */
	public function getSettings()
	{
		try
		{
			$setting = $this->_repository->getSettings();
			
			if (empty($setting))
			{
				$setting[Brand::BAFANG->value]	= [];
				$setting[Brand::BUYGOOD->value] = [];
			}
			else
			{	
				$setting = collect($setting)->groupBy('brandId')->map(function($item, $key){
					return $item->pluck('productId')->toArray();
				})->toArray();
			}
			
			return ResponseLib::initialize($setting)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取銷售設定時發生錯誤');
		}
	}
	
	/* 取Product清單
	 * @params: 
	 * @return: array
	 */
	public function getProductOptions()
	{
		try
		{
			$list = $this->_repository->getProductList();
			$list = collect($list)->groupBy('productBrand')->sortKeys()->toArray();
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return [];
		}
	}
	
	/* Update Role
	 * @params: array
	 * @return: array
	 */
	public function updateSetting($settings)
	{
		try
		{
			$this->_repository->update($settings);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('銷售設定更新失敗');
		}
	}
}
