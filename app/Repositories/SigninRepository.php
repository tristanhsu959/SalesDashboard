<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class SigninRepository extends Repository
{
	#Windows default is case-insensitive, Linex is case-sensitive
	#mysql是系統預設改不了或無效|檔案有分(table name)|Column & data似乎依編碼沒分
	public function __construct()
	{
		
	}
	
	/* Get user by account
	 * @params: string
	 * @return: array
	 */
	public function getUserByAccount($account)
	{
		$db = $this->connectSaleDashboard('user');
			
		$result = $db->select('userId', 'userAd', 'userRoleId')
					->where('userAd', '=', $account)
					->get()->first();
		
		return $result;
	}
	
	/* Get permission of the user
	 * @params: int
	 * @return: boolean
	 */
	public function getUserPermission($roleId)
	{
		$db = $this->connectSaleDashboard('role');
		
		$result = $db->select('roleGroup', 'rolePermission', 'roleArea')
					->where('roleId', '=', $roleId)
					->get()->first();
						
		return $result;
	}
}
