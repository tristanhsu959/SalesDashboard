<?php

namespace App\Services\PurchaseSales;

use App\Facades\AppManager;
use App\Repositories\PurchaseSalesRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Purchase\AreaLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Carbon\CarbonPeriod;
use Exception;

#partial Service
class StoreService
{
	private $_userAreaIds	= FALSE;
	private $_statistics	= [];
   
	public function __construct(protected PurchaseSalesRepository $_repository)
	{
		$this->_statistics = [
			'brandId'			=> '', 
			'searchDate'		=> '', #Y-m-d
			'searchStoreName' 	=> '',
            'storeList' 		=> [],
			'exportToken'		=> '',
		];
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function getActiveList($params)
	{
		try
		{
			$this->_getListFromDB($params);
			
			$this->_buildOutput($params);
			
			$this->_generateStatistics($params);
			
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	
	
	/* 取Active store list
	 * @params: int
	 * @return: string
	 */
	private function _getListFromDB($params)
	{
		try
		{
			#以訂貨系統的門店為基準(不含蘿蔔,因不見得有POS對應, 取資料時才處理蘿蔔店)
			$result = $this->_repository->getActiveStoreListFromNOrder($params->brand, $params->userAreaIds, $params->searchStoreName);
			
			$result = collect($result)->map(function($item, $key){
				$area = AreaLib::toArea(intval($item['areaId']));
				
				$item['areaId']		= $area->value;
				$item['areaName']	= $area->label();
				$item['openDate']	= Carbon::parse($item['openDate'])->format('Y-m-d');
				
				return $item;
			})->toArray();
			
			$params->set('storeList.data', $result);
			
			return $params;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料發生錯誤');
		}
	}
	
	/* Format output
	 * @params: 
	 * @return: 
	 */
	private function _buildOutput($params)
	{
		$params->set('storeList.header', ['PosId', '區域', '門店代號', '門店名稱', '地址', '加盟主', '開店日期', '查看']);
			
		$data = collect($params->storeList['data'])->sortBy('areaId')->values();
		$params->set('storeList.data', $data);
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$this->_statistics['brandId']			= $params->brand->value;
		$this->_statistics['brandCode']			= $params->brand->code();
		$this->_statistics['searchDate']		= $params->searchDate;
		$this->_statistics['searchStoreName']	= $params->searchStoreName;
		$this->_statistics['storeList']			= $params->storeList;
		$this->_statistics['exportToken']		= NULL; #保留供判斷用
	}
	
	public function saveToSession($function)
	{
		session()->put("SESS::{$function}", $this->_statistics);
	}
	
	
	public function getFromSession($function)
	{
		if (session()->missing("SESS::{$function}"))
			return FALSE;
		
		return session()->get("SESS::{$function}");
	}
	
}
