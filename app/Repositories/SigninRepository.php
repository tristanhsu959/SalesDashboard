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
			
		$result = $db->select('UserId', 'UserAd', 'UserAreaId', 'UserRoleId')
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
		$db = $this->connectSaleDashboard('RolePermission');
		
		$result = $db->select('Permission')
					->where('RoleId', '=', $roleId)
					->get();
						
		return $result;
	}
	
	
	
	
}
