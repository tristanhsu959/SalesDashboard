<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class RoleRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Get Roles Data from DB
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		$db = $this->connectSaleDashboard('Role');
			
		$result = $db
			->select('roleId', 'roleName', 'roleGroup', 'roleArea', 'updateAt')
			->get()
			->toArray();
				
		return $result;
	}
	
	/* Create Role
	 * @params: string
	 * @params: string
	 * @params: array
	 * @return: boolean
	 */
	public function insertRole($name, $group, $permission, $area)
	{
		$roleData['RoleName']		= $name;
		$roleData['RoleGroup'] 		= $group;
		$roleData['RolePermission'] = $permission;
		$roleData['RoleArea'] 		= $area;
		$roleData['CreateAt'] 		= now()->format('Y-m-d H:i:s');
		$roleData['UpdateAt'] 		= $roleData['CreateAt'];
		
		$db = $this->connectSaleDashboard('Role');
		$id = $db->insertGetId($roleData);
		
		return TRUE;
	}
	
	/* Get Role Data
	 * @params: 
	 * @return: array
	 */
	public function getRoleById($id)
	{
		$db = $this->connectSaleDashboard('Role');
			
		$result = $db->select('roleId', 'roleName', 'roleGroup', 'rolePermission', 'roleArea', 'updateAt')
					->where('roleId', '=', $id)
					->get()->first();
		
		return $result;
	}
	
	/* Update Role
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: int
	 * @return: boolean
	 */
	public function updateRole($id, $name, $group, $permission, $area)
	{
		#只能用facade
		
		$roleData['RoleName']		= $name;
		$roleData['RoleGroup'] 		= $group;
		$roleData['RolePermission']	= $permission;
		$roleData['RoleArea'] 		= $area;
		$roleData['UpdateAt'] 		= now()->format('Y-m-d H:i:s');
		
		$db = $this->connectSaleDashboard('Role');
		$db->where('RoleId', '=', $id)->update($roleData);
		
		return TRUE;
	}
	
	/* Remove Role
	 * @params: int
	 * @return: boolean
	 */
	public function RemoveRole($roleId)
	{
		$db = $this->connectSaleDashboard('Role');
		$db->where('RoleId', '=', $roleId)->delete();
		
		return TRUE;
	}
}
