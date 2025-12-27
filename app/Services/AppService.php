<?php

namespace App\Services;

use App\Repositories\SigninRepository;
use App\Libraries\ResponseLib;
use App\Traits\AuthenticationTrait;
use App\Traits\AuthorizationTrait;
use App\Traits\MenuTrait;
use Illuminate\Support\Facades\Log;
use Exception;

class AppService
{
	use AuthenticationTrait, AuthorizationTrait, MenuTrait;
	
	private $_title = '登入驗證';
	
	public function __construct(protected SigninRepository $_repository)
	{
	}
	
	/* 登入驗證(由trait決定驗證方式)
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function siginIn($adAccount, $adPassword)
	{
		try
		{
			#1. Clear old auth session
			$this->removeCurrentUser();
			$this->removeAuthMenu();
			
			#2. Auth by AD
			$adInfo = $this->authenticationAD($adAccount, $adPassword);
			
			#3. Auth account register of system
			$userInfo = $this->_authRegister($adAccount);
			
			if ($userInfo === FALSE)
				throw new Exception('登入失敗，帳號尚未在系統註冊');
			
			#4. Save to session
			$this->saveCurrentUser($adInfo, $userInfo);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	
	/* 驗證帳號是否有在系統註冊
	 * @params: string
	 * @return: mixed
	 */
	private function _authRegister($adAccount)
	{
		try
		{
			$userInfo = $this->_repository->getByAccount($adAccount);
			
			if (empty($userInfo))
				return FALSE;
			
			return $userInfo;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('驗證帳號註冊狀態，發生錯誤');
		}
	}
	
	/* 登出
	 * @params: 
	 * @return: boolean
	 */
	public function signout()
	{
		$this->removeCurrentUser();
		$this->removeAuthMenu();
		Log::channel('webSysLog')->info('使用者登出系統', [ __class__, __function__]);
			
		return TRUE;
	}
}
