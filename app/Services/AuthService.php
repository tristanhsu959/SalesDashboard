<?php

namespace App\Services;

use App\Facades\AppManager;
use App\Repositories\AuthRepository;
use App\Libraries\ResponseLib;
use App\Enums\Functions;
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
	
	/* 登入驗證(登人Log統一都寫在weblog)
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function signin($account, $password)
	{
		try
		{
			Log::channel('webSysLog')->info("使用者[{$account}]登入系統：Auth start", [ __class__, __function__, __line__]);
			
			#1. Clear old auth session : CurrentUserTrait
			AppManager::removeCurrentUser();
			
			#2. Get user data
			$userInfo = $this->_authAccountRegister($account);
			
			#3.Auth account
			if ($userInfo === FALSE)
				throw new Exception('此帳號尚未在系統註冊');
			
			#4.Auth password or AD(併行)
			$isPass = $this->_authPassword($account, $userInfo['userPassword'], $password);
			if ($isPass === FALSE)
				throw new Exception('登入失敗，帳號密碼錯誤');
			
			#5.Validate user status
			if (boolval($userInfo['isActive']) === FALSE)
				throw new Exception('登入失敗，此帳號已停用');
			
			#6. Save to session
			AppManager::saveCurrentUser($userInfo);
			
			Log::channel('webSysLog')->info("使用者[{$account}]登入成功", [ __class__, __function__, __line__]);
			
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize()->fail($e->getMessage());
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
			{
				Log::channel('webSysLog')->error("驗證帳號[{$account}]註冊狀態失敗", [ __class__, __function__, __line__]);
				return FALSE;
			}
			
			if ($userInfo['roleGroup'] == RoleGroup::SUPERVISOR->value)
			{
				$userInfo['rolePermission'] = Functions::getAll();
				$userInfo['roleArea'] 		= Area::getAll();
			}
			
			Log::channel('webSysLog')->info("驗證帳號[{$account}]註冊狀態成功", [ __class__, __function__, __line__]);
			
			return $userInfo;
		}
		catch(Exception $e)
		{
			$msg = $e->getMessage();
			Log::channel('webSysLog')->error("驗證帳號[{$account}]註冊狀態，發生錯誤：{$msg}", [ __class__, __function__, __line__]);
			
			throw new Exception('驗證帳號註冊狀態，發生錯誤');
		}
	}
	
	/* 驗證系統密碼或AD
	 * @params: string
	 * @return: mixed
	 */
	private function _authPassword($account, $hashedPassword, $password)
	{
		#驗證系統密碼, 失敗才驗證AD, 只要有一個Pass即可
		if (Hash::check($password, $hashedPassword))
		{
			Log::channel('webSysLog')->info("使用者[{$account}]系統驗證成功", [ __class__, __function__, __line__]);
			return TRUE;
		}
		
		Log::channel('webSysLog')->info("使用者[{$account}]系統密碼驗證失敗", [ __class__, __function__, __line__]);
			
		#驗證AD
		$adInfo = $this->_authAD($account, $password);
		if ($adInfo !== FALSE)
			return TRUE;
		
		return FALSE;
	}
	
	/* AD登入驗證
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function _authAD($account, $password)
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
		
		$domain = config('web.auth.adDomain');
		$connectionConfig = config('web.auth.adConnection');
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
			Log::channel('webSysLog')->error("使用者[{$account}]AD驗證失敗：{$msg}", [ __class__, __function__, __line__]);
			
			return FALSE;
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
			
		$user = $currentUser->account;
		AppManager::removeCurrentUser();
		
		#使用者驗證/登入/登出, 統一寫在syslog
		Log::channel('webSysLog')->info("使用者[{$user}]登出系統", [ __class__, __function__, __line__]);
			
		return TRUE;
	}
}
