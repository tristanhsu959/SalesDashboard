<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class SalesSettingRepository extends Repository
{
	
	public function __construct()
	{
		
	}
	
	/* Get sales product settings
	 * @params: 
	 * @return: array
	 */
	public function getSettings()
	{
		$db = $this->connectSalesDashboard('sales_setting');
			
		$result = $db
			->select('salesSettingProductId as productId', 'salesSettingBrand as brandId')
			->get()
			->toArray();
			
		return $result;
	}
	
	/* Get product settings for options
	 * @params: 
	 * @return: array
	 */
	public function getProductList()
	{
		#不判別product status, 是否啟用由新品設定決定
		$db = $this->connectSalesDashboard('product');
			
		$result = $db
			->select('productId', 'productName', 'productBrand')
			->get()
			->toArray();
			
		return $result;
	}
	
	/* Remove new item
	 * @params: array
	 * @return: boolean
	 */
	public function update($settings)
	{
		$db = $this->connectSalesDashboard();
		$db->beginTransaction();
		
		try 
		{
			$this->_removeSetting();
			$this->_updateSetting($settings);
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
	private function _removeSetting()
	{
		$db = $this->connectSalesDashboard();
		$db->table('sales_setting')
			->delete();
		
		return TRUE;
	}
	
	/* Create product no
	 * @params: int
	 * @return: array
	 */
	private function _updateSetting($settings)
	{
		$data = [];
		foreach($settings as $brand => $productIds)
		{
			foreach($productIds as $id)
			{
				$row['salesSettingProductId'] 	= $id;
				$row['salesSettingBrand'] 		= $brand;
				$data[] = $row;
			}
		}
		
		$db = $this->connectSalesDashboard();
		$db->table('sales_setting')
			->insert($data);
		
		return TRUE;
	}
}
