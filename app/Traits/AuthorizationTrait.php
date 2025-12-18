<?php

namespace App\Traits;

use App\Enums\RoleGroup;
use Illuminate\Support\Str;

/* 授權 */
trait AuthorizationTrait
{
	const SESS_AUTH_USER = 'SessAuthUserInfo';
	const SESS_AUTH_MENU = 'SessAuthMenu';
	
	/* 儲存登入資訊
	 * @params: array
	 * @params: array
	 * @return: 
	 */
	public function saveUserToSession($adInfo, $userInfo)
	{
		$sessionData = array_merge($adInfo, $userInfo);
		session()->put(self::SESS_AUTH_USER, $sessionData);
		
		return TRUE;
	}
	
	public function getSigninUserInfo()
	{
		if (session()->missing(self::SESS_AUTH_USER))
			return FALSE;
		
		return session()->get(self::SESS_AUTH_USER);
	}
	
	public function removeSigninUserInfo()
	{
		session()->forget(self::SESS_AUTH_USER);
		return TRUE;
	}
	
	public function getSigninUserPermission()
	{
		$signinUser = $this->getSigninUserInfo();
		
		if (empty($signinUser))
			return [];
		
		$userPermission = $signinUser['Permission'];
		
		return $userPermission;
	}
	
	public function isSupervisor()
	{
		$currentUser = $this->getSigninUserInfo();
		
		return ($currentUser['RoleGroup'] == RoleGroup::SUPERVISOR->value);
	}
	
	/* 取All Menu List (權限設定用)
	 * @params: 
	 * @return: array
	 */
	public function getAvailableMenu()
	{
		$menu 		= [];
		$groups 	= config('web.menu.groups');
		$functions 	= config('web.menu.functions');
		
		foreach($groups as $key => $group)
		{
			$items = [];
			foreach($group['items'] as $itemKey)
			{
				$items[] = data_get($functions, $itemKey, '');
			}
			
			$group['items'] = $items;
			$menu[$key] = $group;
		}
		
		return $menu;
	}
	
	/* 取已授權的Menu (登入驗後) : AppServiceProvider
	 * @params: 
	 * @return: array
	 */
	public function getAuthorizedMenu()
	{
		$authMenu = [];
		
		#1.若有取過, 直接取Session
		if (session()->has(self::SESS_AUTH_MENU))
			return session()->get(self::SESS_AUTH_MENU);
		
		#2.取功能選單-ALL
		$menuConfig = $this->getAvailableMenu();
		
		#3.驗證有權限的選單, 只要驗證到功能即可
		
		foreach($menuConfig as $key => $group)
		{
			$authMenu[$key] = $group;
			$authItems = [];
				
			foreach($group['items'] as $functions)
			{
				if ($this->hasFunctionPermission($functions['code']))
					$authItems[] = $functions;
			}
				
			$authMenu[$key]['items'] = $authItems;
		}
		
		session()->put(self::SESS_AUTH_MENU, $authMenu);
		
		return $authMenu;
	}
	
	public function removeAuthMenu()
	{
		session()->forget(self::SESS_AUTH_MENU);
		return TRUE;
	}
	
	/* Auth Function Permission
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasFunctionPermission($functionCode)
	{
		if ($this->isSupervisor())
			return TRUE;
		
		$userPermission		= $this->getSigninUserPermission();
		$functionCodeList	= array_keys($userPermission); #Key same as code
		
		return in_array($functionCode, $functionCodeList);
	}
	
	/* Auth CRUD Permission
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasOperationPermission($functionCode, $operationValue)
	{
		if ($this->isSupervisor())
			return TRUE;
		
		$userPermission = $this->getSigninUserPermission();
		$userOperations = data_get($userPermission, $functionCode, []); #array
		
		return in_array($operationValue, $userOperations);
	}
	
	/* 取使用者在此頁面的CRUD權限清單
	 * @params: int
	 * @return: array
	 */
	 public function getOperationPermissionsByFunction($functionCode)
	 {
		$canPermission = [];
		
		#取此功能有效的操作Define
		$functionConfig = config("web.menu.functions.{$functionCode}");
		$operations 	= data_get($functionConfig, 'operation');
		
		foreach($operations as $enumOperation)
		{
			if ($this->isSupervisor() OR $this->hasOperationPermission($functionCode, $enumOperation->value))
				$canPermission[] = $enumOperation->value;
		}
		
		return $canPermission;
	 }
	 
	 
}