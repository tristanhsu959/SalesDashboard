<?php

namespace App\Repositories;

use App\Repositories\Traits\OrderTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class PurchaseProductRepository extends Repository
{
	use OrderTrait;
	
	public function __construct()
	{
		
	}
	
	/* Get product list from DB 
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		$db = $this->connectSalesDashboard('sales_setting');
			
		$result = $db
			->select('salesId', 'salesBrandId', 'salesName', 'salesStatus', 'updateAt')
			->get()
			->toArray();
			
		return $result;
	}
	
	
	/* Create new item
	 * @params: int
	 * @params: string
	 * @params: boolean
	 * @params: array
	 * @return: array
	 */
	public function insert($brandId, $name, $status, $productIds)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$data['salesBrandId']	= $brandId;
			$data['salesName'] 		= $name;
			$data['salesStatus'] 	= $status;
			$data['createAt'] 		= now()->format('Y-m-d H:i:s');
			$data['updateAt'] 		= $data['createAt'];
			
			$db = $this->connectSalesDashboard();
			$insertId = $db->table('sales_setting')->insertGetId($data);
			
			$this->_insertProducts($insertId, $productIds);
			
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
	}
	
	/* Insert product ids
	 * @params: int
	 * @return: array
	 */
	private function _insertProducts($parentId, $productIds)
	{
		$items = [];
		
		foreach($productIds as $id)
		{
			$data['parentId']	= $parentId;
			$data['productId']	= $id;
			
			$items[] = $data;
		}
		
		$db = $this->connectSalesDashboard();
		$db->table('sales_product')
			->where('parentId', '=', $parentId)
			->delete();
			
		$db->table('sales_product')->insert($items);
		
		return TRUE;
	}
	
	/* Get product by id
	 * @params: int
	 * @return: array
	 */
	public function getById($id)
	{
		$db = $this->connectSalesDashboard('sales_setting');
			
		$result = $db->select('salesId', 'salesBrandId', 'salesName', 'salesStatus', 'updateAt', 'productId')
					->leftJoin('sales_product', 'parentId', '=', 'salesId')
					->where('salesId', '=', $id)
					->get()
					->toArray();
		
		return $result;
	}
	
	/* Update new item
	 * @params: int
	 * @params: int
	 * @params: string
	 * @params: boolean
	 * @params: array
	 * @return: boolean
	 */
	public function update($id, $brandId, $name, $status, $productIds)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$data['salesBrandId']	= $brandId;
			$data['salesName'] 		= $name;
			$data['salesStatus'] 	= $status;
			$data['updateAt'] 		= now()->format('Y-m-d H:i:s');
			
			$db = $this->connectSalesDashboard();
			$db->table('sales_setting')
					->where('salesId', '=', $id)
					->update($data);
					
			$this->_insertProducts($id, $productIds);
			
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
	}
	
	/* Remove new item
	 * @params: int
	 * @return: boolean
	 */
	public function remove($id)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$db = $this->connectSalesDashboard();
			$db->table('sales_setting')
				->where('salesId', '=', $id)
				->delete();
			
			$db->table('sales_product')
				->where('parentId', '=', $id)
				->delete();
					
			$db->commit();

			return TRUE;
		} 
		catch (Exception $e) 
		{
			$db->rollBack();
			throw new Exception($e->getMessage());
		}
	}
	
	/* Update status when product removed
	 * @params: int
	 * @return: array
	 */
	public function updateStatus($productId)
	{
		$db = $this->connectSalesDashboard();
		$db->reconnect(); 
		$result = $db->table('sales_product')
			->where('productId', '=', $productId)
			->delete();
		 
		return TRUE;
	}
}
