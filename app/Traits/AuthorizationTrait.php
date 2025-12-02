<?php

namespace App\Traits;

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
	
	/* 取All Menu List (權限設定用)
	 * @params: 
	 * @return: array
	 */
	public function getMenuFromConfig()
	{
		return config('web.menu');
	}
	
	/* 取有授權的Menu (登入驗後)
	 * @params: 
	 * @return: array
	 */
	public function getMenuByPermission()
	{
		$authMenu = [];
		
		if (session()->has(self::SESS_AUTH_MENU))
			return session()->get(self::SESS_AUTH_MENU);
		
		#1.取登入User Permission
		$signinUser = $this->getSigninUserInfo();
		
		if ($signinUser == FALSE)
			return $authMenu;
		
		$userPermission = $signinUser['Permission'];
		
		#2.取功能選單-ALL
		$menuConfig = $this->getMenuFromConfig();
		
		#3.驗證有權限的選單, 只要驗證到功能即可
		
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
		
		session()->put(self::SESS_AUTH_MENU, $authMenu);
		
		return $authMenu;
	}
	
	public function removeAuthMenu()
	{
		session()->forget(self::SESS_AUTH_MENU);
		return TRUE;
	}
	
	/* Auth Group Permission
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasGroupPermission($hexGroup, $hexUserPermissions)
	{
		$hexAction = '00';
		$hexOperation = '0000';
		
		return $this->_authPermission($hexGroup, $hexAction, $hexOperation, $hexUserPermissions);
	}
	
	/* Auth Function Permission
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasActionPermission($hexGroup, $hexAction, $hexUserPermissions)
	{
		$hexOperation = '0000';
		
		return $this->_authPermission($hexGroup, $hexAction, $hexOperation, $hexUserPermissions);
	}
	
	/* Auth CRUD Permission
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasOperationPermission($hexGroup, $hexAction, $hexOperation, $hexUserPermissions)
	{
		return $this->_authPermission($hexGroup, $hexAction, $hexOperation, $hexUserPermissions);
	}
	
	/* Auth Permission - Group/Action/Operation共用邏輯
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	private function _authPermission($hexGroupe, $hexAction, $hexOperation, $hexUserPermissions)
	{
		$decGroup = hexdec($hexGroupe) << 24;
		$decAction = hexdec($hexAction) << 16;
		$decOperation = hexdec($hexOperation);
		$decMask = $decGroup | $decAction | $decOperation;
		
		#$authPermission格式為DB內容
		foreach($hexUserPermissions as $permission)
		{
			$decPermission = hexdec($permission);
			
			#有一個符合即可
			if (($decMask & $decPermission) == $decMask)
				return TRUE;
		}
		
		return FALSE;
	}
	
	/* CRUD Permission Check for Page
	 * @params: int
	 * @return: boolean
	 */
	 public function allowOperationPermissionList($groupKey, $actionKey)
	 {
		$canPermission = [];
		
		$userPermission = $this->getSigninUserPermission();
		$menuConfig =  $this->getMenuFromConfig();
		$group = data_get($menuConfig, "{$groupKey}");
		$action = data_get($group, "items.{$actionKey}");
		$operations = data_get($action, 'operation');
		
		foreach($operations as $enumOperation)
		{
			if ($this->hasOperationPermission($group['groupCode'], $action['actionCode'], $enumOperation->value, $userPermission))
				$canPermission[] = $enumOperation->name;
		}
		
		return $canPermission;
	 }
}