<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Traits\AuthorizationTrait;
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
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
		}
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
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
		}
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: int
	 * @params: int
	 * @return: boolean
	 */
	public function createUser($adAccount, $displayName, $areaId, $roleId)
	{
		try
		{
			#Create data
			$this->_repository->insertUser($adAccount, $displayName, $areaId, $roleId);
		
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
		}
	}
	
	/* 取User Data
	 * @params: int
	 * @return: object array
	 */
	public function getUserById($id)
	{
		try
		{
			$result = $this->_repository->getUserById($id);
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
		}
	}
	
	/* Update User
	 * @params: string
	 * @params: string
	 * @params: int
	 * @params: int
	 * @params: int
	 * @return: boolean
	 */
	public function updateUser($adAccount, $displayName, $areaId, $roleId, $userId)
	{
		try
		{
			$this->_repository->updateUser($adAccount, $displayName, $areaId, $roleId, $userId);
		
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
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
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
		}
	}
	
	/* CRUD Permission Check for Page
	 * @params: int
	 * @return: boolean
	 */
	 public function getOperationPermission()
	 {
		 return $this->allowOperationPermissionList($this->_groupKey, $this->_actionKey);
	 }
}
