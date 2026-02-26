<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\AuthRepository;
use App\Libraries\ResponseLib;
use App\Enums\RoleGroup;
use App\Enums\Area;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;
use LdapRecord\Connection;
use LdapRecord\Query\Filter\Parser;
use Illuminate\Support\Facades\Hash;

class AuthService
{
	public function __construct(protected AuthRepository $_repository)
	{
	}
	
	/* 登入驗證
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function signin($account, $password)
	{
		try
		{
			#1. Clear old auth session : CurrentUserTrait
			AppManager::removeCurrentUser();
			
			#2. auth by AD
			$adInfo = $this->_authenticationByAD($account, $password);
			
			#3. auth DB account permission
			$userInfo = $this->_authAccountRegister($account);
			
			#4. Save to session
			AppManager::saveCurrentUser($adInfo, $userInfo);
			
			Log::channel('webSysLog')->info("使用者[{$account}]登入成功", [ __class__, __function__, __line__]);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->info("使用者[{$account}]登入失敗", [ __class__, __function__, __line__]);
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	
	/* AD登入驗證
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function _authenticationByAD($account, $password)
	{
		#for local non ad env
		if (env('APP_ENV_TYPE', '') == 'nonad') 
		{
			return [
				"company" => "八方雲集國際股份有限公司",
				"department" => "資訊處",
				"title" => "Machine #9",
				"displayName" => "Akh",
				"employeeId" => "T9099999",
				"name" => "Akh",
				"mail" => "machine.akh@8way.com.tw",
			];
		}
		
		#C:\openldap\sysconf\ldap.conf for local dev
		#無法匿名連線(除LDAP外)
		
		$result = [];
		
		$domain = config('web.auth.domain');
		$connectionConfig = config('web.auth.ad');
		#必須是Distinquished Name:cn= , base dn
		$connectionConfig['username'] = "{$account}@{$domain}"; //"cn={$account},{$connectionConfig['base_dn']}"; 
		$connectionConfig['password'] = $password;
		
		try 
		{
			$connection = new Connection($connectionConfig);
			$connection->connect();
			
			/*
			"CN=林 XX,OU=T16000 資訊處,OU=8way_a00 八方雲集國際股份有限公司,OU=八方雲集國際股份有限公司,DC=8way,DC=com,DC=tw"
			cn=中文名, title, ou, displayname=英+中, memof, company, department, employeeid, samaccountname, mail, mobile
			*/
			$result = $connection->query()->where('samaccountname', '=', $account)->first();
			
			#只取需要的資訊
			$adInfo['company'] 		= data_get($result, 'company.0', '');
			$adInfo['department'] 	= data_get($result, 'department.0', '');
			$adInfo['title'] 		= data_get($result, 'title.0', '');
			$adInfo['displayName'] 	= data_get($result, 'displayname.0', ''); #=>FirstName LastName CNName
			$adInfo['employeeId'] 	= data_get($result, 'employeeid.0', ''); #=>CNName
			$adInfo['name'] 		= Str::remove(' ', data_get($result, 'name.0', '')); #=>CNName
			$adInfo['mail'] 		= data_get($result, 'mail.0', '');
			
			Log::channel('webSysLog')->info("使用者[{$account}]AD驗證成功", [ __class__, __function__, __line__]);
			
			return $adInfo;
		} 
		catch (\LdapRecord\Auth\BindException $e) 
		{
			$msg = 'AD Error：?|?|?|?';
			$msg = Str::replaceArray('?', [
				$e->getDetailedError()->getErrorCode(),
				$e->getMessage(), 
				$e->getDetailedError()->getErrorMessage(),
				$e->getDetailedError()->getDiagnosticMessage()
			], $msg);
			
			#AD單獨記錄Log
			Log::channel('appServiceLog')->error($msg, [ __class__, __function__, __line__]);
			
			throw new Exception('AD驗證失敗，帳號或密碼錯誤');
		}
	}
	
	/* 驗證帳號是否有在系統註冊
	 * @params: string
	 * @return: mixed
	 */
	private function _authAccountRegister($account)
	{
		try
		{
			$userInfo = $this->_repository->getUserByAccount($account);
			
			if (empty($userInfo))
				throw new Exception('驗證帳號註冊狀態失敗');
			
			if ($userInfo['roleGroup'] == RoleGroup::SUPERVISOR->value) # OR $userInfo['roleGroup'] == RoleGroup::SUPERUSER->value)
				$userInfo['roleArea'] = Area::getAll();
			
			Log::channel('webSysLog')->info("驗證帳號[{$account}]註冊狀態成功", [ __class__, __function__, __line__]);
			
			return $userInfo;
		}
		catch(Exception $e)
		{
			Log::channel('webSysLog')->info("驗證帳號[{$account}]註冊狀態，發生錯誤", [ __class__, __function__, __line__]);
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('驗證帳號註冊狀態，發生錯誤');
		}
	}
	
	/* 登出
	 * @params: 
	 * @return: boolean
	 */
	public function signout()
	{
		$currentUser = AppManager::getCurrentUser();
		
		if (empty($currentUser)) #已登出
			return TRUE;
			
		$user = $currentUser->userAd;
		AppManager::removeCurrentUser();
		
		#使用者驗證/登入/登出, 統一寫在syslog
		Log::channel('webSysLog')->info("使用者[{$user}]登出系統", [ __class__, __function__, __line__]);
			
		return TRUE;
	}
}
