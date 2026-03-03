<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class NewItemRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Get product list from DB 
	 * @params: 
	 * @return: array
	 */
	public function getProucts()
	{
		$db = $this->connectSalesDashboard('product');
			
		$result = $db
			->select('productId', 'productName', 'productBrand')
			->get()
			->toArray();
			
		return $result;
	}
	
	/* Get product list from DB 
	 * @params: 
	 * @return: array
	 */
	public function getSettings()
	{
		$db = $this->connectSalesDashboard('new_item');
			
		$result = $db
			->select('newItemProductId', 'saleDate', 'saleEndDate')
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
	public function update($id, $brand, $name, $primaryNo, $secondaryNo, $tasteNo, $status)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$this->_updateProduct($id, $brand, $name, $tasteNo, $status);
			
			$this->_removeProductNo($id);
			
			$isPrimary = TRUE;
			$this->_insertProductNo($id, $primaryNo, $isPrimary);
			
			$isPrimary = FALSE;
			$this->_insertProductNo($id, $secondaryNo, $isPrimary);
			
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
	private function _updateProduct($id, $brand, $name, $tasteNo, $status)
	{
		$data['productBrand']		= $brand;
		$data['productName'] 		= $name;
		$data['productTaste'] 		= json_encode($tasteNo);
		$data['productStatus'] 		= $status;
		
		$db = $this->connectSalesDashboard();
		$db->table('product')
			->where('productId', '=', $id)
			->update($data);
		
		return TRUE;
	}
	
	/* Remove Role
	 * @params: int
	 * @return: boolean
	 */
	public function remove($id)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$this->_removeProduct($id);
			$this->_removeProductNo($id);
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
	
	/* Create product no
	 * @params: int
	 * @return: array
	 */
	private function _removeProduct($id)
	{
		$db = $this->connectSalesDashboard();
		$db->table('product')
			->where('productId', '=', $id)
			->delete();
		
		return TRUE;
	}
	
	/* Create product no
	 * @params: int
	 * @return: array
	 */
	private function _removeProductNo($id)
	{
		$db = $this->connectSalesDashboard();
		$db->table('product_no')
			->where('parentId', '=', $id)
			->delete();
		
		return TRUE;
	}
}
