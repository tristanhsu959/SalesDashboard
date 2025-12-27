<?php

namespace App\Traits;


/* 負責登入後相關授權邏輯判別, 只針對登入使用者 */
trait MenuTrait
{
	const SESS_AUTH_MENU = 'Sess:AuthMenu';
	
	/* 儲存登入資訊
	 * @params: array
	 * @return: boolean
	 */
	public function saveAuthMenu($menu)
	{
		session()->put(self::SESS_AUTH_MENU, $menu);
		
		return TRUE;
	}
	
	/* 清除登入資訊|Menu
	 * @params: 
	 * @return: boolean
	 */
	public function removeAuthMenu()
	{
		session()->forget(self::SESS_AUTH_MENU);
		return TRUE;
	}
	
	/* Get Menu Groups & Function
	 * @params: 
	 * @return: array
	 */
    public function getMenu()
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
	
	/* Get functions
	 * @params: string
	 * @return: array
	 */
    public function getFunctionByKey($key = NULL)
    {
		if (empty($key))
			return config('web.menu.functions');
		else
			return config("web.menu.functions.{$key}");
    }
	
	/* 取已授權的Menu (登入驗後) : Called by AppServiceProvider
	 * @params: object
	 * @return: array
	 */
	public function getAuthorizedMenu($currentUser)
	{
		$authMenu = [];
		
		#1.若有取過, 直接取Session
		if (session()->has(self::SESS_AUTH_MENU))
			return session()->get(self::SESS_AUTH_MENU);
		
		#2.取功能選單-ALL
		$menuConfig = $this->getMenu();
		
		#3.驗證使用者有權限的選單, 只要驗證到功能即可
		foreach($menuConfig as $key => $group)
		{
			#$authMenu[$key] = $group;
			$authItems = [];
				
			foreach($group['items'] as $functions)
			{
				if ($currentUser->hasFunctionPermission($functions['code']))
					$authItems[] = $functions;
			}
			
			if (! empty($authItems))
			{
				$authMenu[$key] = $group;
				$authMenu[$key]['items'] = $authItems;
			}
		}
		
		$this->saveAuthMenu($authMenu);
		
		return $authMenu;
	}
	
	
}