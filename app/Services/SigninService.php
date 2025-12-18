<?php

namespace App\Services;

use App\Repositories\SigninRepository;
use App\Libraries\ResponseLib;
use App\Traits\AuthenticationTrait;
use App\Traits\AuthorizationTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;

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
			#1. Clear old auth session
			$this->removeSigninUserInfo();
			$this->removeAuthMenu();
		
			#2. auth by AD
			$adInfo = $this->authenticationAD($adAccount, $adPassword);
			
			#3. auth DB account permission
			$userInfo = $this->_authAccountRegister($adAccount);
			
			if ($userInfo === FALSE)
				throw new Exception('登入失敗，帳號尚未在系統註冊');
			
			#4. Save to session
			$this->saveUserToSession($adInfo, $userInfo);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	
	/* 驗證帳號是否有在系統註冊
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	private function _authAccountRegister($adAccount)
	{
		try
		{
			$userInfo = $this->_repository->getUserByAccount($adAccount);
			
			if (empty($userInfo))
				return FALSE;
			
			#允許有帳號無Permission, 故不檢查
			$roleData = $this->_repository->getUserPermission($userInfo['UserRoleId']);
			$userInfo['RoleGroup']	= $roleData['RoleGroup'];
			$userInfo['Permission'] = empty($roleData['RolePermission']) ? [] : json_decode($roleData['RolePermission'], TRUE);
			$userInfo['Area'] 		= empty($roleData['RoleArea']) ? [] : json_decode($roleData['RoleArea'], TRUE);
			
			return $userInfo;
		}
		catch(Exception $e)
		{
			throw new Exception('驗證帳號註冊狀態，發生錯誤');
		}
	}
	
	/* 登出
	 * @params: 
	 * @return: boolean
	 */
	public function signout()
	{
		$this->removeSigninUserInfo();
		$this->removeAuthMenu();
		Log::channel('webSysLog')->info('使用者登出系統', [ __class__, __function__]);
			
		return TRUE;
	}
}
