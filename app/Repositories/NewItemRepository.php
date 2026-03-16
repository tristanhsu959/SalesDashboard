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
	public function getList()
	{
		$db = $this->connectSalesDashboard('new_item');
			
		$result = $db
			->select('newItemId', 'newItemBrand', 'newItemProductId', 'newItemName', 'newItemSaleDate', 'newItemTaste', 'newItemStatus', 'updateAt')
			->get();
		
		$result = $result->map(function($item, $key){
			$item['newItemTaste'] = json_decode( $item['newItemTaste'], TRUE);
			return $item;
		})->toArray();
		
		return $result;
	}
	
	/* Get product settings for options
	 * @params: 
	 * @return: array
	 */
	public function getProductSettings()
	{
		#不判別product status, 是否啟用由新品設定決定
		$db = $this->connectSalesDashboard('product');
			
		$result = $db
			->select('productId', 'productName', 'productBrand')
			->get()
			->toArray();
			
		return $result;
	}
	
	/* Create new item
	 * @params: int
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: boolean
	 * @return: array
	 */
	public function insert($brand, $productId, $name, $saleDate, $tastes, $status)
	{
		$data['newItemBrand']		= $brand;
		$data['newItemProductId'] 	= $productId;
		$data['newItemName'] 		= $name;
		$data['newItemSaleDate'] 	= (new Carbon($saleDate))->format('Y-m-d');
		$data['newItemTaste'] 		= json_encode($tastes);
		$data['newItemStatus'] 		= $status;
		$data['createAt'] 			= now()->format('Y-m-d H:i:s');
		$data['updateAt'] 			= $data['createAt'];
		
		$db = $this->connectSalesDashboard();
		$db->table('new_item')->insert($data);
		
		return TRUE;
	}
	
	/* Get product by id
	 * @params: int
	 * @return: array
	 */
	public function getById($id)
	{
		$db = $this->connectSalesDashboard('new_item');
			
		$result = $db->select('newItemId', 'newItemBrand', 'newItemProductId', 'newItemName', 'newItemSaleDate', 'newItemTaste', 'newItemStatus', 'updateAt')
					->where('newItemId', '=', $id)
					->get()
					->first();
		$result['newItemTaste'] = json_decode($result['newItemTaste'], TRUE);
		
		return $result;
	}
	
	/* Update new item
	 * @params: int
	 * @params: int
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: array
	 * @params: boolean
	 * @return: boolean
	 */
	public function update($id, $brand, $productId, $name, $saleDate, $tastes, $status)
	{
		$data['newItemBrand']		= $brand;
		$data['newItemProductId'] 	= $productId;
		$data['newItemName'] 		= $name;
		$data['newItemSaleDate'] 	= (new Carbon($saleDate))->format('Y-m-d');
		$data['newItemTaste'] 		= json_encode($tastes);
		$data['newItemStatus'] 		= $status;
		$data['updateAt'] 			= now()->format('Y-m-d H:i:s');
		
		$db = $this->connectSalesDashboard();
		$db->table('new_item')
			->where('newItemId', '=', $id)
			->update($data);
		
		return TRUE;
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
