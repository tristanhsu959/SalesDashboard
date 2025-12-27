<?php

namespace App\Traits;

use App\Services\Modules\CurrentUser;
use App\Enums\RoleGroup;
use App\Libraries\MenuLib;
use Illuminate\Support\Str;

/* 負責登入後相關授權邏輯判別, 只針對登入使用者 */
trait AuthorizationTrait
{
	const SESS_AUTH_USER = 'Sess:AuthUser';
	const SESS_AUTH_MENU = 'Sess:AuthMenu';
	
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
	
	/* 清除登入資訊|Menu
	 * @params: 
	 * @return: boolean
	 */
	public function removeCurrentUser()
	{
		session()->forget(self::SESS_AUTH_USER);
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
	
	/* Check url link
	 * @params: 
	 * @return: boolean
	 */
	public function hasRoutePermission($segments, $currentUser)
	{
		foreach($segments as $segment)
		{
			$functionCode = config("web.menu.functions.{$segment}.code");
			
			if ($currentUser->hasFunctionPermission($functionCode))
				return TRUE;
		}
		
		return FALSE;
	}
}