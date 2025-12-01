<?php

namespace App\Services;

use App\Repositories\SigninRepository;
#use App\Libraries\LoggerLib;
use App\Traits\AuthenticationTrait;
use App\Traits\AuthorizationTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class SigninService
{
	use AuthenticationTrait, AuthorizationTrait;
	
	private $_title = '登入驗證';
	private $_repository;
	
	public function __construct(SigninRepository $signinRepository)
	{
		$this->_repository = $signinRepository;
	}
	
	/* 登入驗證(由trait決定驗證方式)
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function authSiginIn($adAccount, $adPassword)
	{
		try
		{
			#1. auth by AD
			$adInfo = $this->authenticationAD($adAccount, $adPassword);
			
			#記錄Log, 以便分辨錯誤點
			if ($adInfo === FALSE)
				throw new Exception('登入失敗，AD帳號或密碼錯誤');
			
			#2. auth DB account permission
			$userInfo = $this->_authAccountRegister($adAccount);
			
			if ($userInfo === FALSE)
				throw new Exception('登入失敗，帳號尚未註冊');
			
			#3. Save to session
			$this->saveUserToSession($adInfo, $userInfo);
			
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return FALSE;
		}
	}
	
	/* 驗證帳號是否有在系統註冊
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	private function _authAccountRegister($adAccount)
	{
		$userInfo = $this->_repository->getUserByAccount($adAccount);
		
		if (empty($userInfo))
			return FALSE;
		
		#若是2維要再toArray, 允許有帳號無Permission, 故不檢查
		$permission = $this->_repository->getUserPermission($userInfo['UserRoleId'])->toArray();
		$userInfo['Permission'] = Arr::flatten($permission);
		
		return $userInfo;
	}
	
	/* 登出
	 * @params: 
	 * @return: boolean
	 */
	public function signout()
	{
		$this->removeSigninUserInfo();
		Log::channel('webSysLog')->info('使用者登出系統', [ __class__, __function__]);
			
		return TRUE;
	}
}
