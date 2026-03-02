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
	/* Get product list from DB 
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		$db = $this->connectSalesDashboard('product');
			
		$result = $db
			->select('productId', 'productName', 'productBrand', 'productStatus')
			->get()
			->toArray();
			
		return $result;
	}
	
	/* Create product
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
			$insertId = $this->_insertProduct($brand, $name, $tasteNo, $status);
			
			$isPrimary = TRUE;
			$this->_insertProductNo($insertId, $primaryNo, $isPrimary);
			
			$isPrimary = FALSE;
			$this->_insertProductNo($insertId, $secondaryNo, $isPrimary);
			
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
	
	/* Create product
	 * @params: int
	 * @return: array
	 */
	private function _insertProduct($brand, $name, $tasteNo, $status)
	{
		$data['productBrand']		= $brand;
		$data['productName'] 		= $name;
		$data['productTaste'] 		= json_encode($tasteNo);
		$data['productStatus'] 		= $status;
		
		$db = $this->connectSalesDashboard();
		$insertId = $db->table('product')
			->insertGetId($data);
		
		return $insertId;
	}
	
	/* Create product no
	 * @params: int
	 * @return: array
	 */
	private function _insertProductNo($parentId, $erpNos, $isPrimary)
	{
		$items = [];
		
		foreach($erpNos as $no)
		{
			$data['parentId']	= $parentId;
			$data['erpNo'] 		= $no;
			$data['isPrimary'] 	= $isPrimary;
			
			$items[] = $data;
		}
		
		$db = $this->connectSalesDashboard();
		$db->table('product_no')->insert($items);
		
		return TRUE;
	}
	
	/* Get product by id
	 * @params: int
	 * @return: array
	 */
	public function getById($id)
	{
		$db = $this->connectSalesDashboard('product');
			
		$result = $db->select('productId', 'productBrand', 'productName', 'productTaste', 'productStatus')
					->addSelect('erpNo', 'isPrimary')
					->leftJoin('product_no', 'parentId', '=', 'productId')
					->where('productId', '=', $id)
					->get();
		
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
