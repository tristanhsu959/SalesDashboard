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
			$db = $this->connectItPortal('user');
				
			$result = $db->select('userId', 'userAd', 'userPassword', 'userRoleId', 'roleGroup', 'rolePermission')
						->join('role', 'roleId', '=', 'userRoleId')
						->where('userAd', '=', $account)
						->get()->first();
			
			return $result;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return FALSE;
		}
	}
	
	/* Get permission of the user
	 * @params: int
	 * @params: array
	 * @return: boolean
	 */
	public function syncAdInfo($userId, $adInfo)
	{
		try
		{
			$data = [];
			
			#有資料才更新
			if (! empty(data_get($adInfo, 'company', '')))
				$data['adCompany'] = data_get($adInfo, 'company');
			
			if (! empty(data_get($adInfo, 'department', '')))
				$data['adDepartment'] = data_get($adInfo, 'department');
			
			if (! empty(data_get($adInfo, 'employeeId', '')))
				$data['adEmployeeId'] = data_get($adInfo, 'employeeId');
			
			if (! empty(data_get($adInfo, 'displayName', '')))
				$data['adDisplayName'] = data_get($adInfo, 'displayName');
			
			if (! empty(data_get($adInfo, 'mail', '')))
				$data['adMail'] = data_get($adInfo, 'mail');
			
			if (empty($data))
				return TRUE;
			
			$db = $this->connectItPortal('user_ad_info');
			$result = $db->updateOrInsert(['adUserId' => $userId], $data);
					
			return TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return FALSE;
		}
	}
}
