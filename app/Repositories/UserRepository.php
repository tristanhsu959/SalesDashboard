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
		$db = $this->connectSaleDashboard('role');
			
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
		$db = $this->connectSaleDashboard('user as a');
			
		$db->select('a.userId', 'a.userAd', 'a.userDisplayName', 'a.userRoleId', 'a.updateAt', 'b.roleGroup', 'b.roleArea')
			->join('role as b', 'b.roleId', '=', 'a.userRoleId');
		
		#query conditions
		if (! is_null($searchAd))
			$db->where('a.userAd', 'like', "%{$searchAd}%");
		if (! is_null($searchName))
			$db->where('a.userDisplayName', 'like', "%{$searchName}%");
		if (! is_null($searchArea))
			$db->whereJsonContains('b.roleArea', $searchArea);
		
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
		$db = $this->connectSaleDashboard('user');
		
		$data['userAd']			= $adAccount;
		$data['userDisplayName']= $displayName;
		$data['userRoleId'] 	= $roleId;
		$data['createAt'] 		= now()->format('Y-m-d H:i:s');
		$data['updateAt'] 		= $data['createAt'];
			
		$db->insert($data);
		return TRUE;
	}
	
	/* Get User Data
	 * @params: 
	 * @return: array
	 */
	public function getUserById($id)
	{
		$db = $this->connectSaleDashboard('user');
			
		$result = $db->select('userId', 'userAd', 'userDisplayName', 'userRoleId')
					->where('userId', '=', $id)
					->get()->first();
		
		return $result;
	}
	
	/* Update User
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: boolean
	 */
	public function updateUser($userId, $adAccount, $displayName, $roleId)
	{
		$db = $this->connectSaleDashboard('user');
		
		$data['userAd']			= $adAccount;
		$data['userDisplayName']= $displayName;
		$data['userRoleId'] 	= $roleId;
		$data['updateAt'] 		= now()->format('Y-m-d H:i:s');
			
		$db->where('userId', '=', $userId)->update($data);
		return TRUE;
	}
	
	/* Remove User
	 * @params: int
	 * @return: boolean
	 */
	public function RemoveUser($userId)
	{
		$db = $this->connectSaleDashboard('user');
		$db->where('userId', '=', $userId)->delete();

		return FALSE;
	}
}
