<?php

namespace App\Repositories\Traits;

use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Facades\DB;

/* nOrder DB Common */
trait OrderTrait
{
	/* 取對應nOrder的設定值
	 * @params: int
	 * @params: array
	 * @return: array
	 */
	public function getOpCenterNo($brandId)
	{
		#台北/高雄
		if ($brandId == Brand::BAFANG->value OR $brandId == Brand::BUYGOOD->value)
			return OpCenter::toValueArray();
		
		return [];
	}
	
	public function getBrandNo($brandId)
	{
		$brand = Brand::tryFrom($brandId);
		return $brand->shortCode();
	}
	
	public function getFactoryNo($brandId)
	{
		$brand = Brand::tryFrom($brandId);
		if ($brandId == Brand::BAFANG->value)
			return [Factory::TP->value, Factory::KH->value];
		else
			return [Factory::TS->value, Factory::RL->value];
	}
	
	/* 取產品分類
	 * @params: int
	 * @return: array
	 */
	public function getProductTypes($brandId)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('ProductType as a')
			->select('a.No', 'a.Name')
			->where('a.OperationCenterId', '=', 1) #取op=1
			->where('a.IsEnable', '=', 1)
			->whereNotIn('a.No', config("web.shipments.productType.{$brandId}.except"))
			->groupBy('a.No', 'a.Name')
			->orderBy('a.No')
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取產品設定及分類
	 * @params: int
	 * @return: array
	 */
	public function getProductWithType($brandId)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Product as a')
			->join('ProductType as b', 'b.Id', '=', 'a.ProductTypeId')
			->join('Stocks as c', 'c.ProductId', '=', 'a.Id')
			->select('a.OldNo as productNo', 'a.Name as productName', 'b.No as catNo', 'b.Name as catName')
			->where('a.OldNo', '!=', '')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as a1')
					->whereColumn('a1.Id', 'a.OperationCenterId')
					->whereIn('a1.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as c1')
					->whereColumn('c1.Id', 'c.BrandId')
					->where('c1.No',  $this->getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as c2')
					->whereColumn('c2.Id', 'c.FactoryId')
					->whereIn('c2.No',  $this->getFactoryNo($brandId));
			})
			->whereNotIn('b.No', config("web.shipments.productType.{$brandId}.except"))
			->groupBy('a.OldNo', 'a.Name', 'b.No', 'b.Name')
			->orderBy('b.No')
			->orderBy('a.OldNo')
			->get()
			->toArray(); 
		
		return $result;
	}
}