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
	
	/* Get user list by query conditions
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function getList()
	{
		$db = $this->connectSalesDashboard('user as a');
			
		$result = $db->select('a.userId', 'a.userAccount', 'a.userDisplayName', 'a.department')
			->addSelect('a.email', 'b.roleGroup', 'a.isActive', 'a.updateAt')
			->leftJoin('role as b', 'b.roleUserId', '=', 'a.userId')
			->get()
			->toArray();
		
		$result = Arr::map($result, function ($item, string $key) {
			$item['rolePermission']	= empty($item['rolePermission']) ? [] : json_decode($item['rolePermission'], TRUE);
			$item['roleArea'] 		= empty($item['roleArea']) ? [] : json_decode($item['roleArea'], TRUE);
			return $item;
		});
			
		return $result;
	}
	
	/* Get user by account
	 * @params: string
	 * @return: array
	 */
	public function getIdByAccount($account, $exceptId)
	{
		$db = $this->connectSalesDashboard('user');
			
		$result = $db->select('userId')
					->where('userAccount', '=', $account)
					->when($exceptId, function($query, $exceptId){
						return $query->where('userId', '!=', $exceptId);
					})
					->get()
					->first();
		
		return data_get($result, 'userId', 0);
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: boolean
	 */
	public function insert($account, $password, $displayName, $department, $email, $isActive, $roleGroupId, $permission, $area)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$insertId = $this->_insertUser($account, $password, $displayName, $department, $email, $isActive);
			
			$this->_insertRole($insertId, $roleGroupId, $permission, $area);
			
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
		
		return TRUE;
	}
	
	/* Create user
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: boolean
	 * @return: boolean
	 */
	private function _insertUser($account, $password, $displayName, $department, $email, $isActive)
	{
		$data['userAccount']	= $account;
		$data['userPassword'] 	= $password;
		$data['userDisplayName']= $displayName;
		$data['department']		= $department;
		$data['email']			= $email;
		$data['isActive']		= $isActive;
		$data['createAt'] 		= now()->format('Y-m-d H:i:s');
		$data['updateAt'] 		= $data['createAt'];
		
		$db = $this->connectSalesDashboard();
		$insertId = $db->table('user')
			->insertGetId($data);
		
		return $insertId;
	}
	
	/* Create user
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: boolean
	 * @return: boolean
	 */
	private function _insertRole($userId, $roleGroupId, $permission, $area)
	{
		$data['roleUserId']		= $userId;
		$data['roleGroup'] 		= $roleGroupId;
		$data['rolePermission']	= json_encode($permission);
		$data['roleArea']		= json_encode($area);
		
		$db = $this->connectSalesDashboard();
		$insertId = $db->table('role')
			->insert($data);
		
		return TRUE;
	}
	
	/* Get user by id
	 * @params: int
	 * @return: array
	 */
	public function getById($id)
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
	public function update($userId, $adAccount, $displayName, $roleId)
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
	public function remove($userId)
	{
		$db = $this->connectSalesDashboard('user');
		$db->where('userId', '=', $userId)->delete();

		return FALSE;
	}
}
