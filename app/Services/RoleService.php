<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use App\Libraries\ResponseLib;
use App\Libraries\LoggerLib;
use App\Traits\MenuTrait;
use App\Traits\RolePermissionTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Exception;

class RoleService
{
	use MenuTrait, RolePermissionTrait;
	
	private $_title = '身份管理';
	private $_repository;
    
	public function __construct(RoleRepository $roleRepository)
	{
		$this->_repository = $roleRepository;
	}
	
	/* 取Role清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getList()->toArray();
			
			return $list;
		}
		catch(Exception $e)
		{
			LoggerLib::initialize()->sysLog($this->_title, __class__, __function__, $e->getMessage());
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
		$permissions = $this->getPermissions($settingList);
		$this->_repository->create($roleName, $roleGroup, $permissions);
		
		return ResponseLib::initialize($data)->success()->get();
	}
}
