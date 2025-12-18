<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class SigninRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Get Signin User Data
	 * @params: string
	 * @return: array
	 */
	public function getUserByAccount($account)
	{
		$db = $this->connectSaleDashboard('User');
			
		$result = $db->select('UserId', 'UserAd', 'UserRoleId')
					->where('UserAd', '=', $account)
					->get()->first();
		
		return $result;
	}
	
	/* Get Signin User Permission
	 * @params: 
	 * @return: boolean
	 */
	public function getUserPermission($roleId)
	{
		$db = $this->connectSaleDashboard('Role');
		
		$result = $db->select('RoleGroup', 'RolePermission', 'RoleArea')
					->where('RoleId', '=', $roleId)
					->get()->first();
						
		return $result;
	}
	
	
	
	
}
