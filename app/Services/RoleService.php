<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use App\Traits\AuthorizationTrait;
use App\Traits\RolePermissionTrait;
use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class RoleService
{
	use AuthorizationTrait, RolePermissionTrait;
	
	private $_groupKey	= 'authManager';
	private $_actionKey = 'roles';
	private $_repository;
    
	public function __construct(RoleRepository $roleRepository)
	{
		$this->_repository = $roleRepository;
	}
	
	/* 取Role清單(Get ALL)
	 * @params: 
	 * @return: object array
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
	
	/* Create Role
	 * @params: string
	 * @params: string
	 * @params: hex string
	 * @return: array
	 */
	public function createRole($roleName, $roleGroup, $settingList)
	{
		try
		{
			#Create data & Build permission setting
			$permissionList = $this->buildPermissionByFunction($settingList);
			$this->_repository->insertRole($roleName, $roleGroup, $permissionList);
		
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return ResponseLib::initialize()->fail('新增身份失敗');
		}
	}
	
	/* 取Role Data
	 * @params: int
	 * @return: object array
	 */
	public function getRoleById($id)
	{
		try
		{
			$result = $this->_repository->getRoleById($id);
			return ResponseLib::initialize($result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return ResponseLib::initialize()->fail('讀取身份設定資料發生錯誤');
		}
	}
	
	/* Update Role
	 * @params: string
	 * @params: string
	 * @params: hex string
	 * @return: array
	 */
	public function updateRole($roleName, $roleGroup, $settingList, $roleId)
	{
		try
		{
			#Build permission setting
			$permissionList = $this->buildPermissionByFunction($settingList);
			$this->_repository->updateRole($roleName, $roleGroup, $permissionList, $roleId);
		
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return ResponseLib::initialize()->fail('編輯身份失敗');
		}
	}
	
	/* Remove Role
	 * @params: int
	 * @return: boolean
	 */
	public function deleteRole($roleId)
	{
		try
		{
			$this->_repository->removeRole($roleId);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return ResponseLib::initialize()->fail('刪除身份失敗');
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
