<?php

namespace App\Services;

use App\Repositories\LunarRepository;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;


class LunarService
{
	private $_result;
	
	public function __construct(protected LunarRepository $_repository)
	{
		$this->_result = [
			'tp' => [],
			'ts' => [],
		];
	}
	
	/* 查詢車次
	 * @params: date
	 * @return: array
	 */
	public function searchCarNo($searchDate)
	{
DB::connection('BFPosErp')->table($table)->lock('WITH(NOLOCK)');
DB::connection('BGPosErp')->table($table)->lock('WITH(NOLOCK)');
		try
		{
			list($tpSettings, $tsSettings) = $this->_getSetting($searchDate); #八方#御廚
			
			#2. Update to order system
			$this->_result['tp'] = $this->_repository->getTpCarNo($tpSettings); #台北
			$this->_result['ts'] = $this->_repository->getTsCarNo($tsSettings); #屯山
			
			return ResponseLib::initialize($this->_result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize($this->_result)->fail($e->getMessage());
		}
	}
	
	/* 設定新車次
	 * @params: date
	 * @return: array
	 */
	public function assignCarNo($assignDate)
	{
		try
		{
			#1. Get local setting
			list($tpSettings, $tsSettings) = $this->_getSetting($assignDate); #八方#御廚
			
			#2. Update to order system
			$this->_updateTpCarNo($tpSettings); #台北
			$this->_updateTsCarNo($tsSettings); #屯山
			
			return ResponseLib::initialize($this->_result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize($this->_result)->fail($e->getMessage());
		}
	}
	
	/* 取新車次設定
	 * @params: date
	 * @return: array
	 */
	private function _getSetting($assignDate)
	{
		try
		{
			$tpCarNos = $this->_repository->getBfSetting($assignDate); #八方
			$tsCarNos = $this->_repository->getBgSetting($assignDate); #御廚
			
			return [$tpCarNos, $tsCarNos];
			
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取車次設定失敗');
		}
	}
	
	/* 更新台北車次設定
	 * @params: date
	 * @return: array
	 */
	private function _updateTpCarNo($settings)
	{
		try
		{
			#要注意測試時間
			$this->_repository->updateTpCarNo($settings); 
			return TRUE;
			
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('更新台北車次設定失敗');
		}
	}
	
	/* 更新屯山車次設定
	 * @params: date
	 * @return: array
	 */
	private function _updateTsCarNo($settings)
	{
		try
		{
			#要注意測試時間
			$this->_repository->updateTsCarNo($settings); 
			return TRUE;
			
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('更新屯山車次設定失敗');
		}
	}
	
	/* 恢復原車次
	 * @params: date
	 * @return: array
	 */
	public function restoreCarNo($restoreDate)
	{
		try
		{
			#1. Get local setting
			list($tpSettings, $tsSettings) = $this->_getOriSetting($restoreDate); #八方#御廚
			
			#2. Update to order system
			$this->_updateTpCarNo($tpSettings); #台北
			$this->_updateTsCarNo($tsSettings); #屯山
			
			return ResponseLib::initialize($this->_result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize($this->_result)->fail($e->getMessage());
		}
	}
	
	/* 取原車次設定
	 * @params: date
	 * @return: array
	 */
	private function _getOriSetting($restoreDate)
	{
		try
		{
			$tpCarNos = $this->_repository->getBfOriSetting($restoreDate); #八方
			$tsCarNos = $this->_repository->getBgOriSetting($restoreDate); #御廚
			
			return [$tpCarNos, $tsCarNos];
			
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取原車次設定失敗');
		}
	}
}
