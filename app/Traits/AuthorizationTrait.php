<?php

namespace App\Traits;

use App\Libraries\LoggerLib;
use Illuminate\Support\Str;

/* 授權 */
trait AuthorizationTrait
{
	const SESS_AUTH_USER = 'SessAuthUserInfo';
	
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
		return session()->get(self::SESS_AUTH_USER);
	}
	
	/* 取All Menu List (權限設定用)
	 * @params: 
	 * @return: array
	 */
	public function getMenu()
	{
		return config('web.menu');
	}
	
	
}