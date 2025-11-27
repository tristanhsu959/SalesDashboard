<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Exceptions\DBException;
use Illuminate\Support\Carbon;
use Exception;

class RoleRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Get Roles Data from DB
	 * @params: 
	 * @return: collection
	 */
	public function getList()
	{
		$db = $this->connectSaleDashboard('Role');
			
		$result = $db
			->select('RoleId', 'RoleName', 'RoleGroup')
			->get();
				
		return $result;
	}
	
	/* Create Role
	 * @params: string
	 * @params: string
	 * @params: array
	 * @return: boolean
	 */
	public function insertRole($roleName, $roleGroup, $permissionList)
	{
		#只能用手動transaction寫法
		try 
		{
			#只能用facade
			$db = $this->connectSaleDashboard();
		
			$db->beginTransaction();

			$roleData['RoleName']	= $roleName;
			$roleData['RoleGroup'] 	= $roleGroup;
			$roleData['CreateAt'] 	= now()->format('Y-m-d H:i:s');
			$roleData['UpdateAt'] 	= $roleData['CreateAt'];
			
			$id = $db->table('Role')->insertGetId($roleData);
			
			$permissionData = $this->_buildPermissionData($id, $permissionList);
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
	 * @return: collection
	 */
	public function getRoleById($id)
	{
		$db = $this->connectSaleDashboard();
			
		$result = $db->table('Role')->selectRaw("RoleId, RoleName, RoleGroup, UpdateAt, 
					permission = stuff((select  ',' + Permission from SaleDashboard.dbo.RolePermission where RoleId=? FOR XML PATH('')), 1, 1, '')", [$id])
					->where('RoleId', '=', $id)
					->get()->first();
		
		$result->permission = explode(',', $result->permission);
		
		return $result;
	}
}
