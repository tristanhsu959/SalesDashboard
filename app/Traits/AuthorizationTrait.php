<?php

namespace App\Traits;

use App\Libraries\LoggerLib;;
use Illuminate\Support\Str;

/* 授權 */
trait AuthorizationTrait
{
	const SESS_AUTH_USER = 'SessAuthUserInfo';
	
	/* 儲存登入資訊
	 * @params: array
	 * @return: 
	 */
	public function saveAuthUser($adInfo)
	{
		session()->put(self::SESS_AUTH_USER, $adInfo);
		
		return TRUE;
	}
	
	public function getAuthUser()
	{
		return session()->get(self::SESS_AUTH_USER);
	}
}