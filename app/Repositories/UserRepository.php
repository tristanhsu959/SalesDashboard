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
	
	/* Get role list
	 * @params: 
	 * @return: array
	 */
	public function getRoleList()
	{
		$db = $this->connectSalesDashboard('role');
			
		$result = $db
			->select('roleId', 'roleName')
			->get()
			->toArray();
				
		return $result;
	}
	
	/* Get user list by query conditions
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function getList($searchAd = NULL, $searchName = NULL, $searchRoleId = NULL)
	{
		$db = $this->connectSalesDashboard('user as a');
			
		$db->select('a.userId', 'a.userAd', 'a.userDisplayName', 'a.userRoleId', 'a.updateAt', 'b.roleName', 'b.roleGroup', 'b.roleArea')
			->join('role as b', 'b.roleId', '=', 'a.userRoleId');
		
		#query conditions
		if (! is_null($searchAd))
			$db->where('a.userAd', 'like', "%{$searchAd}%");
		if (! is_null($searchName))
			$db->where('a.userDisplayName', 'like', "%{$searchName}%");
		if (! is_null($searchRoleId))
			$db->where('a.userRoleId', $searchRoleId);
		
		$result = $db->get()->toArray();
		
		return $result;
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: boolean
	 */
	public function insertUser($adAccount, $displayName, $roleId)
	{
		$db = $this->connectSalesDashboard('user');
		
		$data['userAd']			= $adAccount;
		$data['userDisplayName']= $displayName;
		$data['userRoleId'] 	= $roleId;
		$data['createAt'] 		= now()->format('Y-m-d H:i:s');
		$data['updateAt'] 		= $data['createAt'];
			
		$db->insert($data);
		return TRUE;
	}
	
	/* Get user by id
	 * @params: int
	 * @return: array
	 */
	public function getUserById($id)
	{
		$db = $this->connectSalesDashboard('user');
			
		$result = $db->select('userId', 'userAd', 'userDisplayName', 'userRoleId', 'updateAt')
					->where('userId', '=', $id)
					->get()->first();
		
		return $result;
	}
	
	/* Update user data by id
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: boolean
	 */
	public function updateUser($userId, $adAccount, $displayName, $roleId)
	{
		$db = $this->connectSalesDashboard('user');
		
		$data['userAd']			= $adAccount;
		$data['userDisplayName']= $displayName;
		$data['userRoleId'] 	= $roleId;
		$data['updateAt'] 		= now()->format('Y-m-d H:i:s');
			
		$db->where('userId', '=', $userId)->update($data);
		return TRUE;
	}
	
	/* Remove user by id
	 * @params: int
	 * @return: boolean
	 */
	public function RemoveUser($userId)
	{
		$db = $this->connectSalesDashboard('user');
		$db->where('userId', '=', $userId)->delete();

		return FALSE;
	}
}
