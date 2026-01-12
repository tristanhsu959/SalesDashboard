<?php

namespace App\Traits;

use App\Enums\RoleGroup;
use App\Libraries\MenuLib;
use Illuminate\Support\Str;

/* 負責登入後相關授權邏輯判別, 只針對登入使用者 */
trait AuthorizationTrait
{
	const SESS_AUTH_USER = 'SessAuthUserInfo';
	const SESS_AUTH_MENU = 'SessAuthMenu';
	
	
	/* 儲存登入資訊
	 * @params: array
	 * @params: array
	 * @return: boolean
	 */
	public function saveUserToSession($adInfo, $userInfo)
	{
		$sessionData = array_merge($adInfo, $userInfo);
		session()->put(self::SESS_AUTH_USER, $sessionData);
		
		return TRUE;
	}
	
	/* Get current user
	 * @params: 
	 * @return: array
	 */
	public function getSigninUserInfo()
	{
		if (session()->missing(self::SESS_AUTH_USER))
			return FALSE;
		
		return session()->get(self::SESS_AUTH_USER);
	}
	
	/* Get current user permissions
	 * @params: 
	 * @return: array
	 */
	public function getSigninUserPermission()
	{
		$signinUser = $this->getSigninUserInfo();
		
		if (empty($signinUser))
			return [];
		
		$userPermission = $signinUser['permission'];
		
		return $userPermission;
	}
	
	/* 清除登入資訊|Menu
	 * @params: 
	 * @return: boolean
	 */
	public function removeSigninUserInfo()
	{
		session()->forget(self::SESS_AUTH_USER);
		return TRUE;
	}
	
	/* 內建Supervisor (RoleGroup)
	 * @params:  
	 * @return: boolean
	 */
	public function isSupervisor()
	{
		$currentUser = $this->getSigninUserInfo();
		
		if (empty($currentUser))
			return FALSE;
		
		return ($currentUser['roleGroup'] == RoleGroup::SUPERVISOR->value);
	}
	
	/* 取已授權的Menu (登入驗後) : AppServiceProvider
	 * @params: 
	 * @return: array
	 */
	public function getAuthorizedMenu()
	{
		$authMenu = [];
		
		$refresh = (env('APP_ENV') == 'local') ? TRUE : FALSE; #for develop
		
		#1.若有取過, 直接取Session
		if (! $refresh && session()->has(self::SESS_AUTH_MENU))
			return session()->get(self::SESS_AUTH_MENU);
		
		#2.取功能選單-ALL
		$menuConfig = MenuLib::all();
		
		#3.驗證使用者有權限的選單, 只要驗證到功能即可
		foreach($menuConfig as $key => $group)
		{
			#$authMenu[$key] = $group;
			$authItems = [];
				
			foreach($group['items'] as $functions)
			{
				if ($this->hasFunctionPermission($functions['code']))
					$authItems[] = $functions;
			}
			
			if (! empty($authItems))
			{
				$authMenu[$key] = $group;
				$authMenu[$key]['items'] = $authItems;
			}
		}
		
		session()->put(self::SESS_AUTH_MENU, $authMenu);
		
		return $authMenu;
	}
	
	/* 登入授權Menu
	 * @params:  
	 * @return: boolean
	 */
	public function removeAuthMenu()
	{
		session()->forget(self::SESS_AUTH_MENU);
		return TRUE;
	}
	
	/* Auth permission of function by current user
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
	
	/* Auth permission of CRUD by current user
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
	
	##todo: 應該可廢棄
	/* 取使用者在此頁面的CRUD權限清單
	 * @params: int
	 * @return: array
	 *
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
	 */
	 
}