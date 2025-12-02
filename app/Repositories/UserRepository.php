<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class UserRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Get Roles Data from DB
	 * @params: 
	 * @return: array
	 */
	public function getRoleList()
	{
		$db = $this->connectSaleDashboard('Role');
			
		$result = $db
			->select('RoleId', 'RoleName')
			->get()
			->toArray();
				
		return $result;
	}
	
	/* Get User Data from DB
	 * @params: 
	 * @return: array
	 */
	public function getList($searchAd = NULL, $searchName = NULL, $searchArea = NULL)
	{
		$db = $this->connectSaleDashboard('User');
			
		$db->select('UserId', 'UserAd', 'UserDisplayName', 'UserAreaId', 'UserRoleId');
		
		#query conditions
		if (! is_null($searchAd))
			$db->where('UserAd', 'like', "%{$searchAd}%");
		if (! is_null($searchName))
			$db->where('UserDisplayName', 'like', "%{$searchName}%");
		if (! is_null($searchArea))
			$db->where('UserAreaId', '=', $searchArea);
		
		$result = $db->get()->toArray();
		
		// $result = $db
			// ->select('UserId', 'UserAd', 'UserDisplayName', 'UserAreaId', 'UserRoleId')
			// ->get()
			// ->toArray();
			
		return $result;
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: int
	 * @params: int
	 * @return: boolean
	 */
	public function insertUser($adAccount, $displayName, $areaId, $roleId)
	{
		$db = $this->connectSaleDashboard('User');
		
		$data['UserAd']			= $adAccount;
		$data['UserDisplayName']= $displayName;
		$data['UserAreaId']		= $areaId;
		$data['UserRoleId'] 	= $roleId;
		$data['CreateAt'] 		= now()->format('Y-m-d H:i:s');
		$data['UpdateAt'] 		= $data['CreateAt'];
			
		$db->insert($data);
		return TRUE;
	}
	
	/* Create Role Permission data
	 * @params: 
	 * @return: boolean
	 */
	private function _buildPermissionData($roleId, $permissionList)
	{
		#create insert data array
		$permissions = [];
		
		foreach($permissionList as $permissionCode)
		{
			$permissions[] = ['RoleId' => $roleId, 'Permission' => $permissionCode];
		}
		
		return $permissions;
	}
	
	/* Get User Data
	 * @params: 
	 * @return: array
	 */
	public function getUserById($id)
	{
		$db = $this->connectSaleDashboard('User');
			
		$result = $db->select('UserId', 'UserAd', 'UserDisplayName', 'UserAreaId', 'UserRoleId')
					->where('UserId', '=', $id)
					->get()->first();
		
		return $result;
	}
	
	/* Update User
	 * @params: string
	 * @params: string
	 * @params: int
	 * @params: int
	 * @params: int
	 * @return: boolean
	 */
	public function updateUser($adAccount, $displayName, $areaId, $roleId, $userId)
	{
		$db = $this->connectSaleDashboard('User');
		
		$data['UserAd']			= $adAccount;
		$data['UserDisplayName']= $displayName;
		$data['UserAreaId']		= $areaId;
		$data['UserRoleId'] 	= $roleId;
		$data['UpdateAt'] 		= now()->format('Y-m-d H:i:s');
			
		$db->where('UserId', '=', $userId)->update($data);
		return TRUE;
	}
	
	/* Remove User
	 * @params: int
	 * @return: boolean
	 */
	public function RemoveUser($userId)
	{
		$db = $this->connectSaleDashboard('User');
		$db->where('UserId', '=', $userId)->delete();

		return FALSE;
	}
}
