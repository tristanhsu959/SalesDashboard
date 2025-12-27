<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
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
	public function getByAccount($account)
	{
		$user = $this->_getUserByAccount($account);
		
		#Parsing
		$user['permission'] = empty($user['rolePermission']) ? [] : json_decode($user['rolePermission'], TRUE);
		$user['area'] 		= empty($user['roleArea']) ? [] : json_decode($user['roleArea'], TRUE);
			
		return $user;
	}
	
	/* Get permission of the user
	 * @params: int
	 * @return: boolean
	 */
	private function _getUserByAccount($account)
	{
		$db = $this->connectSalesDashboard('user');
			
		$result = $db->select('userId', 'userAd', 'userRoleId', 'roleGroup', 'rolePermission', 'roleArea')
					->join('role', 'userRoleId', '=', 'roleId')
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
		$db = $this->connectSalesDashboard('role');
		
		$result = $db->select('roleGroup', 'rolePermission', 'roleArea')
					->where('roleId', '=', $roleId)
					->get()->first();
						
		return $result;
	}
}
