<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Libraries\ResponseLib;
use App\Enums\RoleGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class UserService
{
	public function __construct(protected UserRepository $_repository)
	{
	}
	
	/* 取帳號清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getList();
			
			$list = Arr::map($list, function ($item, string $key) {
				$item['roleArea'] = empty($item['roleArea']) ? [] : json_decode($item['roleArea'], TRUE);
				return $item;
			});
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取帳號清單時發生錯誤');
		}
	}
	
	/* 取帳號清單 By Query Conditions
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function searchList($searchAd, $searchName, $searchArea)
	{
		try
		{
			$list = $this->_repository->getList($searchAd, $searchName, $searchArea);
			
			$list = Arr::map($list, function ($item, string $key) {
				$item['roleArea'] = empty($item['roleArea']) ? [] : json_decode($item['roleArea'], TRUE);
				return $item;
			});
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('查詢時發生錯誤');
		}
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function createUser($adAccount, $displayName, $roleId)
	{
		try
		{
			#Create data
			$this->_repository->insertUser($adAccount, $displayName, $roleId);
		
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('新增帳號失敗');
		}
	}
	
	/* Update:取User Data
	 * @params: int
	 * @return: array
	 */
	public function getUserById($id)
	{
		try
		{
			$result = $this->_repository->getUserById($id); 
			return ResponseLib::initialize($result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取帳號資料發生錯誤');
		}
	}
	
	/* Update User
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function updateUser($userId, $adAccount, $displayName, $roleId)
	{
		try
		{
			$this->_repository->updateUser($userId, $adAccount, $displayName, $roleId);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('編輯帳號失敗');
		}
	}
	
	/* Remove User
	 * @params: int
	 * @return: array
	 */
	public function deleteUser($userId)
	{
		try
		{
			$this->_repository->removeUser($userId);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('刪除帳號失敗');
		}
	}
	
	/* 取可設定的Role選項清單
	 * @params: 
	 * @return: array
	 */
	public function getRoleOptions()
	{
		try
		{
			$list = $this->_repository->getRoleList();
			$list = Arr::where($list, function ($item, int $key) {
				return ($item['roleGroup'] != RoleGroup::SUPERVISOR->value);
			});
			
			$list = Arr::mapWithKeys($list, function (array $item, int $key) {
				return [$item['roleId'] => $item['roleName']];
			});
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return [];
		}
	}
	
}
