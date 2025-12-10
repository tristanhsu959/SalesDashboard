<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Traits\AuthorizationTrait;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class UserService
{
	use AuthorizationTrait;
	
	private $_groupKey	= 'authManager';
	private $_actionKey = 'users';
	
	private $_title = '帳號管理';
	private $_repository;
    
	public function __construct(UserRepository $userRepository)
	{
		$this->_repository = $userRepository;
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
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
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
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return ResponseLib::initialize()->fail('查詢時發生錯誤');
		}
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: int
	 * @return: boolean
	 */
	public function createUser($adAccount, $displayName, $areaIds, $roleId)
	{
		try
		{
			#Create data
			$this->_repository->insertUser($adAccount, $displayName, $areaIds, $roleId);
		
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return ResponseLib::initialize()->fail('新增帳號失敗');
		}
	}
	
	/* Update:取User Data
	 * @params: int
	 * @return: object array
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
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return ResponseLib::initialize()->fail('讀取帳號資料發生錯誤');
		}
	}
	
	/* Update User
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: int
	 * @params: int
	 * @return: boolean
	 */
	public function updateUser($adAccount, $displayName, $areaIds, $roleId, $userId)
	{
		try
		{
			$this->_repository->updateUser($adAccount, $displayName, $areaIds, $roleId, $userId);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return ResponseLib::initialize()->fail('編輯帳號失敗');
		}
	}
	
	/* Remove User
	 * @params: int
	 * @return: boolean
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
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
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
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return [];
		}
	}
	
	/* CRUD Permission Check for Page
	 * @params: int
	 * @return: boolean
	 */
	 public function getOperationPermission()
	 {
		try
		{
			return $this->allowOperationPermissionList($this->_groupKey, $this->_actionKey);
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return [];
		}
	 }
}
