<?php

namespace App\Traits;

use App\Libraries\LoggerLib;;
use Illuminate\Support\Str;
use LdapRecord\Connection;
use LdapRecord\Query\Filter\Parser;

/* 認證 */
trait AuthenticationTrait
{
	/* AD登入驗證(由trait決定驗證方式)
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function authenticationAD($account, $password)
	{
		#fake data
		return [
			"company" => "八方雲集國際股份有限公司",
			"department" => "資訊處",
			"title" => "經理",
			"displayname" => "Tristan Hsu 許方毓",
			"employeeid" => "T2025098",
			"name" => "許方毓",
			"mail" => "tristan.hsu@8way.com.tw",
		];

 
 
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
			*/
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
			LoggerLib::initialize('AD驗證')->sysLog($e->getMessage(), __class__, __function__);
			return FALSE;
		}
	}
}