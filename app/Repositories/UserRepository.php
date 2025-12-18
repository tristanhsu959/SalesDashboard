<?php

namespace App\Repositories;

use App\Enums\RoleGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
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
			->select('roleId', 'roleName')
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
		$db = $this->connectSaleDashboard('User as a');
			
		$db->select('a.userId', 'a.userAd', 'a.userDisplayName', 'a.userRoleId', 'a.updateAt', 'b.roleGroup', 'b.roleArea')
			->join('Role as b', 'b.RoleId', '=', 'a.UserRoleId');
		
		#query conditions
		if (! is_null($searchAd))
			$db->where('a.UserAd', 'like', "%{$searchAd}%");
		if (! is_null($searchName))
			$db->where('a.UserDisplayName', 'like', "%{$searchName}%");
		if (! is_null($searchArea))
			$db->whereJsonContains('b.RoleArea', $searchArea);
		
		$result = $db->get()->toArray();
		
		return $result;
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: int
	 * @params: int
	 * @return: boolean
	 */
	public function insertUser($adAccount, $displayName, $areaIds, $roleId)
	{
		$db = $this->connectSaleDashboard('User');
		
		$data['UserAd']			= $adAccount;
		$data['UserDisplayName']= $displayName;
		$data['UserAreaId']		= json_encode($areaIds);
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
		
		$result['UserAreaId'] = empty($result['UserAreaId']) ? [] : json_decode($result['UserAreaId']);
		
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
	public function updateUser($adAccount, $displayName, $areaIds, $roleId, $userId)
	{
		$db = $this->connectSaleDashboard('User');
		
		$data['UserAd']			= $adAccount;
		$data['UserDisplayName']= $displayName;
		$data['UserAreaId']		= json_encode($areaIds);
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
