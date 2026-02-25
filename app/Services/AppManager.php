<?php
#目前未用到
namespace App\Services;

use App\Models\CurrentUser;
use App\Enums\Brand;
use Illuminate\Support\Str;

class AppManager
{
	const SESS_AUTH_USER = 'Sess:AuthUser';
	const SESS_AUTH_MENU = 'Sess:AuthMenu';
	
	public function __construct()
	{
		
	}
	
	/* hasAuth
	 * @params: 
	 * @return: boolean
	 */
	public function hasAuth()
	{
		return empty($this->getCurrentUser()) ? FALSE : TRUE;
	}
	
	/* 清除登入資訊|Menu
	 * @params: 
	 * @return: boolean
	 */
	public function removeCurrentUser()
	{
		session()->forget(self::SESS_AUTH_USER);
		session()->forget(self::SESS_AUTH_MENU);
		
		return TRUE;
	}
	
	/* 儲存登入資訊
	 * @params: array
	 * @params: array
	 * @return: boolean
	 */
	public function saveCurrentUser($adInfo, $userInfo)
	{
		$currentUser = new CurrentUser($adInfo, $userInfo);
		session()->put(self::SESS_AUTH_USER, $currentUser);
		
		return TRUE;
	}
	
	/* Get current user
	 * @params: 
	 * @return: array
	 */
	public function getCurrentUser()
	{
		if (session()->missing(self::SESS_AUTH_USER))
			return FALSE;
		
		return session()->get(self::SESS_AUTH_USER);
	}
	
	/* 取已授權的Menu (登入驗後)
	 * @params: 
	 * @return: array
	 */
	public function getAuthMenu()
	{
		$authMenu = [];
		
		#1.若有取過, 直接取Session
		if (env('APP_DEBUG', TRUE) == FALSE && session()->has(self::SESS_AUTH_MENU))
			return session()->get(self::SESS_AUTH_MENU);
		
		#2.取目前登入使用者
		$currentUser = $this->getCurrentUser();
		
		if ($currentUser === FALSE)
			return $authMenu;
		
		#3.取功能選單設定檔
		$menuConfig = config('web.menu');
		
		#4.驗證使用者有權限的選單, 只要驗證到功能即可
		foreach($menuConfig as $brand => $groups)
		{
			$items 	= [];
			
			dd($this->_getMenuGroup($currentUser, $brand, $groups));
			foreach($group['items'] as $functionKey)
			{
				if ($currentUser->hasFunctionPermission($functionKey))
					$items[] = config("web.menu.functions.{$functionKey}");
			}
			
			if (! empty($items))
			{
				$group['items'] = $items;
				$authMenu[] = $group;
			}
		}
		
		session()->put(self::SESS_AUTH_MENU, $authMenu);
		
		return $authMenu;
	}
	
	/* 取已授權的Menu (登入驗後)
	 * @params: 
	 * @return: array
	 */
	private function _getMenuGroup($currentUser, $brand, $groups)
	{
		$authGroups = [];
		
		foreach($groups as $group)
		{
			$items 	= [];
			
			foreach($group['items'] as $functionKey)
			{
				$authKey = Str::contains($brand, [Brand::BAFANG->value, Brand::BUYGOOD->value]) ? "{$brand}-{$functionKey}" : $functionKey;
				
				if ($currentUser->hasFunctionPermission($authKey))
					$items[] = config("web.menu.item.{$functionKey}");
			}
			
			if (! empty($items))
			{
				$group['items'] = $items;
				$authGroups[] = $group;
			}
		}
		
		return $authGroups;
	}
}
