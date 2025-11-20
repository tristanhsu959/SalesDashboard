<?php

namespace App\Services;

use App\Libraries\ResponseLib;
use App\Traits\AdAuthTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;


class AuthService
{
	#保留新增其它登入的彈性
	use AdAuthTrait;
	const SESS_AUTH = 'SessAuthInfo';
	
	public function __construct()
	{
		
	}
	
	/* 登入驗證(由trait決定驗證方式)
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function authUser($account, $password)
	{
		$response = $this->authenticationAD($account, $password);
		
		#Save to session
		if ($response['status'] === TRUE)
			$this->_saveAuthInfo($response['data']);
		
		return $response;
	}
	
	/* 儲存登入資訊
	 * @params: array
	 * @return: 
	 */
	private function _saveAuthInfo($adInfo)
	{
		session()->put(self::SESS_AUTH, $adInfo);
		
		return TRUE;
	}
}
