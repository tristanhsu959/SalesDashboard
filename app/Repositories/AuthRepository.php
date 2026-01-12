<?php

namespace App\Repositories;

use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;
use Log;

class AuthRepository extends Repository
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
		try
		{
			$db = $this->connectSalesDashboard('user');
				
			$result = $db->select('userId', 'userAd', 'userRoleId', 'userRoleId', 'roleGroup', 'rolePermission', 'roleArea')
						->join('role', 'roleId', '=', 'userRoleId')
						->where('userAd', '=', $account)
						->get()->first();
			
			$result['rolePermission'] 	= empty($result['rolePermission']) ? [] : json_decode($result['rolePermission'], TRUE);
			$result['roleArea'] 		= empty($result['roleArea']) ? [] : json_decode($result['roleArea'], TRUE);
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return FALSE;
		}
	}
}
