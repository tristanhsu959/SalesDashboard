<?php

namespace App\Repositories\Traits;

use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use App\Libraries\Purchase\AreaLib;
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
	
	/* 取工廠清單
	 * @params: int
	 * @return: array
	 */
	public function getFactoryList($brandId)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Factory as f')
			->select('f.No as factoryNo', 'f.Name as factoryName')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'f.OperationCenterId')
					->whereIn('oc.No', $this->getOpCenterNo($brandId));
			})
			->whereIn('f.No', $this->getFactoryNo($brandId))
			->where('f.IsEnable', '=', 1)
			->orderBy('f.Id')
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取門店清單
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getStoreList($brand, $userAreaIds)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->select('ar.Id as areaId', 's.Id as storeId', 's.No as storeNo', 's.Name as storeName', 's.PosId as posId')
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
			#->whereNull('s.CloseDate')
			->whereIn('s.AreaId', $authAreaIds)
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->toRawSql();
			#->orderBy('s.OperationCenterId')
			#->orderBy('ar.Id')
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取有效門店清單(only id:計算用)
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getActiveStoreId($brand, $userAreaIds)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->select('ar.Id as areaId', 's.Id as storeId')
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
			->whereNull('s.CloseDate')
			->when($authAreaIds, function ($query, $authAreaIds) {
				return $query->whereIn('s.AreaId', $authAreaIds);
			})
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))
			->get()
			->toArray(); 
		
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
			->whereNotIn('a.No', config("web.purchase.product_type.typeNo.except.{$brandId}"))
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
			->whereNotIn('pt.No', config("web.purchase.product_type.typeNo.except.{$brandId}"))
			->groupBy('a.OldNo', 'a.Name', 'pt.No', 'pt.Name')
			->orderBy('pt.No')
			->orderBy('a.OldNo')
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取產品設定及代碼
	 * @params: int
	 * @return: array
	 */
	public function getProductShortCode($brandId)
	{
		$enableCodes = config('web.purchase.product_type.shortCode.enabled');
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Stocks as a')
			->join('Product as p', 'p.Id', '=', 'a.ProductId')
			->select('p.OldNo as productNo', 'p.Name as productName')
			->where('a.ShelfStatus', '=', 1)
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as op')
					->whereColumn('op.Id', 'p.OperationCenterId')
					->whereIn('op.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 'a.BrandId')
					->where('bd.No',  $this->getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'a.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			#先全抓
			/* ->where(function ($query) use ($enableCodes) {
				foreach ($enableCodes as $pattern) 
				{
					$query->orWhere('p.OldNo', 'like', $pattern);
				}
			}) */
			->where('p.OldNo', '!=', '')
			->groupBy('p.OldNo', 'p.Name')
			->orderBy('p.OldNo')
			->get()
			->toArray(); 
		
		return $result;
	}
}