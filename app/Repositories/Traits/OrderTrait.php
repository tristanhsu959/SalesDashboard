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
	
	/* 取門店清單
	 * @params: int
	 * @return: array
	 */
	public function getStoreList($brandId)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->select('ar.Name as area', 's.Id', 's.No as storeNo', 's.Name as storeName')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 's.OperationCenterId')
					->whereIn('oc.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->where('bd.No',  $this->getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->whereNull('s.CloseDate')->toRawSql();
			/* ->get()
			->toArray(); */
		dd($result);
		return $result;
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
			->join('ProductType as pt', 'pt.Id', '=', 'a.ProductTypeId')
			->join('Stocks as st', 'st.ProductId', '=', 'a.Id')
			->select('a.OldNo as productNo', 'a.Name as productName', 'pt.No as catNo', 'pt.Name as catName')
			->where('a.OldNo', '!=', '')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as op')
					->whereColumn('op.Id', 'a.OperationCenterId')
					->whereIn('op.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 'st.BrandId')
					->where('bd.No',  $this->getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'st.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->whereNotIn('pt.No', config("web.shipments.productType.{$brandId}.except"))
			->groupBy('a.OldNo', 'a.Name', 'pt.No', 'pt.Name')
			->orderBy('pt.No')
			->orderBy('a.OldNo')
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取Product id
	 * @params: string
	 * @return: array
	 */
	public function getProductIdByName($brandId, $name)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Product as a')
			->join('Stocks as st', 'st.ProductId', '=', 'a.Id')
			->select('a.Id')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'a.OperationCenterId')
					->whereIn('oc.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'st.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->where('a.IsStop', '=', 0)
			->where('a.Name', 'like', "%{$name}%")
			->groupBy('a.Id')
			->get()
			->pluck('Id')
			->toArray();
		
		return $result;
	}
}