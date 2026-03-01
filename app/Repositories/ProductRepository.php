<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class ProductRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	/* Case-sensitive in ubuntu */
	/* Get role list from DB 
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		$db = $this->connectSalesDashboard('role');
			
		$result = $db
			->select('roleId', 'roleName', 'roleGroup', 'roleArea', 'updateAt')
			->get()
			->toArray();
				
		return $result;
	}
	
	/* Create Role
	 * @params: string
	 * @params: string
	 * @params: json string
	 * @params: json string
	 * @return: boolean
	 */
	public function insert($brand, $name, $primaryNo, $secondaryNo, $tasteNo, $status)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$this->_insertProduct($brand, $name, $tasteNo, $status);

			DB::connection('mysql_log')
				->table('system_status')
				->where('id', 1)
				->update(['last_sync' => now()]);

			// 3. 全部成功則提交
			DB::connection('mysql_log')->commit();

			return "Transaction Successful";

		} catch (Exception $e) {
			// 4. 發生錯誤則回滾
			DB::connection('mysql_log')->rollBack();

			// 依需求處理錯誤或拋出
			return "Transaction Failed: " . $e->getMessage();
		}
	
		
		
		return TRUE;
	}
	
	/* Get Role Data
	 * @params: int
	 * @return: array
	 */
	private function _insertProduct($brand, $name, $tasteNo, $status)
	{
		$data['productBrand']		= $brand;
		$data['productName'] 		= $name;
		$data['productTaste'] 		= $tasteNo;
		$data['productStatus'] 		= $status;
		
		$db = $this->connectSalesDashboard();
		$insertId = $db->table('product')
			->insertGetId($data);
		
		return $insertId;
	}
	
	/* Get Role Data
	 * @params: int
	 * @return: array
	 */
	private function _insertProductNo($primaryNo, $secondaryNo)
	{
		$data['productBrand']		= $brand;
		$data['productName'] 		= $name;
		$data['productTaste'] 		= $tasteNo;
		$data['productStatus'] 		= $status;
		
		$db = $this->connectSalesDashboard();
		$insertId = $db->table('product')
			->insertGetId($data);
		
		return $insertId;
	}
	
	/* Get Role Data
	 * @params: int
	 * @return: array
	 */
	public function getRoleById($id)
	{
		$db = $this->connectSalesDashboard('role');
			
		$result = $db->select('roleId', 'roleName', 'roleGroup', 'rolePermission', 'roleArea', 'updateAt')
					->where('roleId', '=', $id)
					->get()->first();
		
		return $result;
	}
	
	/* Update Role
	 * @params: int
	 * @params: string
	 * @params: int
	 * @params: json string
	 * @params: json string
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
		
		$db = $this->connectSalesDashboard('role');
		$db->where('roleId', '=', $id)->update($roleData);
		
		return TRUE;
	}
	
	/* Remove Role
	 * @params: int
	 * @return: boolean
	 */
	public function RemoveRole($roleId)
	{
		$db = $this->connectSalesDashboard('role');
		$db->where('roleId', '=', $roleId)->delete();
		
		return TRUE;
	}
}
