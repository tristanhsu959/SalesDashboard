<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Exceptions\DBException;
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
	public function getList()
	{
		$db = $this->connectSaleDashboard('User');
			
		$result = $db
			->select('UserId', 'UserAd', 'UserDisplayName', 'UserAreaId', 'UserRoleId')
			->get()
			->toArray();
				
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
	
	/* Get Role Data
	 * @params: 
	 * @return: array
	 */
	public function getRoleById($id)
	{
		$db = $this->connectSaleDashboard();
			
		$result = $db->table('Role')->selectRaw("RoleId, RoleName, RoleGroup, UpdateAt, 
					permission = stuff((select  ',' + Permission from SaleDashboard.dbo.RolePermission where RoleId=? FOR XML PATH('')), 1, 1, '')", [$id])
					->where('RoleId', '=', $id)
					->get()->first();
		
		$result['Permission'] = explode(',', $result['permission']);
		
		return $result;
	}
	
	/* Update Role
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: int
	 * @return: boolean
	 */
	public function updateRole($roleName, $roleGroup, $permissionList, $roleId)
	{
		#只能用手動transaction寫法
		try 
		{
			#只能用facade
			$db = $this->connectSaleDashboard();
		
			$db->beginTransaction();

			$roleData['RoleName']	= $roleName;
			$roleData['RoleGroup'] 	= $roleGroup;
			$roleData['UpdateAt'] 	= now()->format('Y-m-d H:i:s');
			
			$db->table('Role')->where('RoleId', '=', $roleId)->update($roleData);
			
			$permissionData = $this->_buildPermissionData($roleId, $permissionList);
			#Remove all setting by role id
			$db->table('RolePermission')->where('RoleId', '=', $roleId)->delete();
			$db->table('RolePermission')->insert($permissionData);
			
			$db->commit();
			
			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw $e;
			return FALSE;
		}
	}
	
	/* Remove Role
	 * @params: int
	 * @return: boolean
	 */
	public function RemoveRole($roleId)
	{
		#只能用手動transaction寫法
		try 
		{
			#只能用facade
			$db = $this->connectSaleDashboard();
		
			$db->beginTransaction();

			#Remove all setting by role id
			$db->table('Role')->where('RoleId', '=', $roleId)->delete();
			$db->table('RolePermission')->where('RoleId', '=', $roleId)->delete();
			
			$db->commit();
			
			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw $e;
			return FALSE;
		}
	}
}
