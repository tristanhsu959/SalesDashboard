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
		$db = $this->connectSaleDashboard('role');
			
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
		$roleData['roleName']		= $name;
		$roleData['roleGroup'] 		= $group;
		$roleData['rolePermission'] = $permission;
		$roleData['roleArea'] 		= $area;
		$roleData['createAt'] 		= now()->format('Y-m-d H:i:s');
		$roleData['updateAt'] 		= $roleData['createAt'];
		
		$db = $this->connectSaleDashboard('role');
		$id = $db->insertGetId($roleData);
		
		return TRUE;
	}
	
	/* Get Role Data
	 * @params: 
	 * @return: array
	 */
	public function getRoleById($id)
	{
		$db = $this->connectSaleDashboard('role');
			
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
		
		$roleData['roleName']		= $name;
		$roleData['roleGroup'] 		= $group;
		$roleData['rolePermission']	= $permission;
		$roleData['roleArea'] 		= $area;
		$roleData['updateAt'] 		= now()->format('Y-m-d H:i:s');
		
		$db = $this->connectSaleDashboard('role');
		$db->where('roleId', '=', $id)->update($roleData);
		
		return TRUE;
	}
	
	/* Remove Role
	 * @params: int
	 * @return: boolean
	 */
	public function RemoveRole($roleId)
	{
		$db = $this->connectSaleDashboard('role');
		$db->where('roleId', '=', $roleId)->delete();
		
		return TRUE;
	}
}
