<?php

namespace App\Services;

use App\Traits\AuthorizationTrait;
use App\Traits\RolePermissionTrait;
use Illuminate\Support\Arr;

class AppService
{
	use AuthorizationTrait, RolePermissionTrait;
	
	public function __construct()
	{
		// $this->_repository = $partyRepository;
	}
	
	/* 取有授權的Menu (登入驗後)
	 * @params: 
	 * @return: array
	 */
	public function getAuthorizeMenu()
	{
		#1.取登入User Permission
		$signinUser = $this->getSigninUserInfo();
		$userPermission = $signinUser['Permission'];
		
		#2.取功能選單-ALL
		$menuConfig = $this->getMenu();
		
		#3.驗證有權限的選單, 只要驗證到功能即可
		$authMenu = [];
		
		foreach($menuConfig as $key => $group)
		{
			if ($this->hasGroupPermission($group['groupCode'], $userPermission))
			{
				$authMenu[$key] = $group;
				$authItems = [];
				
				foreach($group['items'] as $action)
				{
					if ($this->hasActionPermission($group['groupCode'], $action['actionCode'], $userPermission))
						$authItems[] = $action;
				}
				
				$authMenu[$key]['items'] = $authItems;
			}
		}
		
		return $authMenu;
	}
}
