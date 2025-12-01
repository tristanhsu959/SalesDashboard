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
}