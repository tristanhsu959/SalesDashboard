<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use App\Traits\AuthorizationTrait;
use App\Traits\RolePermissionTrait;
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
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
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
		
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
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
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
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
		
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
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
