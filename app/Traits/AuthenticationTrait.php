<?php

namespace App\Traits;

#use App\Libraries\LoggerLib;;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use LdapRecord\Connection;
use LdapRecord\Query\Filter\Parser;
use Log;
use Exception;

/* AD認證 : 只負責驗證AD邏輯 */
trait AuthenticationTrait
{
	#Refactor
	/* AD登入驗證(由trait決定驗證方式)
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function authenticationAD($account, $password)
	{
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
			
			return $adInfo;
		} 
		catch (\LdapRecord\Auth\BindException $e) 
		{
			Log::channel('webSysLog')->error('AD驗證失敗:' . $e->getMessage(), [ __class__, __function__]);
			Log::channel('webSysLog')->error('ErrorCode:' . $e->getDetailedError()->getErrorCode());
			Log::channel('webSysLog')->error('DetailError:' . $e->getDetailedError()->getErrorMessage());
			Log::channel('webSysLog')->error('DiagnosticMessage:' .  $e->getDetailedError()->getDiagnosticMessage());
			throw new Exception('AD驗證失敗，帳號或密碼錯誤');
		}
	}
	
	/* AD登入驗證(由trait決定驗證方式)
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	/*public function OldauthenticationAD($account, $password)
	{
		#fake data
		/*return [
			"company" => "八方雲集國際股份有限公司",
			"department" => "資訊處",
			"title" => "經理",
			"displayname" => "Tristan Hsu 許方毓",
			"employeeid" => "T2025098",
			"name" => "許方毓",
			"mail" => "tristan.hsu@8way.com.tw",
		];
		*
 
		#C:\openldap\sysconf\ldap.conf for local dev
		#無法匿名連線(除LDAP外)
		
		$result = [];
		
		$connectionConfig = config('web.auth.ad.connection');
		$connectionConfig['username'] = $account; //"8waytw\$account";
		$connectionConfig['password'] = $password;
		
		try 
		{
			$connection = new Connection($connectionConfig);
			$connection->connect();
			
			/*
			"CN=林 XX,OU=T16000 資訊處,OU=8way_a00 八方雲集國際股份有限公司,OU=八方雲集國際股份有限公司,DC=8way,DC=com,DC=tw"
			cn=中文名, title, ou, displayname=英+中, memof, company, department, employeeid, samaccountname, mail, mobile
			*
			$result = $connection->query()->where('samaccountname', '=', $account)->first();
			
			#只取需要的資訊
			$adInfo['company'] = $result['company'][0];
			$adInfo['department'] = $result['department'][0];
			$adInfo['title'] = $result['title'][0];
			$adInfo['displayname'] = $result['displayname'][0]; #=>FirstName LastName CNName
			$adInfo['employeeid'] = $result['employeeid'][0]; #=>CNName
			$adInfo['name'] = Str::remove(' ', $result['name'][0]); #=>CNName
			$adInfo['mail'] = $result['mail'][0];
			
			return $adInfo;
		} 
		catch (\LdapRecord\Auth\BindException $e) 
		{
			Log::channel('webSysLog')->error('AD驗證失敗:' . $e->getMessage(), [ __class__, __function__]);
			Log::channel('webSysLog')->error('ErrorCode:' . $e->getDetailedError()->getErrorCode());
			Log::channel('webSysLog')->error('DetailError:' . $e->getDetailedError()->getErrorMessage());
			Log::channel('webSysLog')->error('DiagnosticMessage:' .  $e->getDetailedError()->getDiagnosticMessage());
			return FALSE;
		}
	}*/
}