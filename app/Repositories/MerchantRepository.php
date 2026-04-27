<?php

namespace App\Repositories;

use App\Repositories\Traits\OrderTrait;
use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Purchase\AreaLib;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;


class MerchantRepository extends Repository
{
	use OrderTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取門店資訊, 不使用共用的trait
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getStoreInfoList($brand, $userAreaIds)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->leftJoin('User as u', 'u.Id', '=', 's.SuperviseUserId')
			->leftJoin('Factory as f', 'f.Id', '=', 'sc.FactoryId')
			->leftJoin('Car as c', 'c.Id', '=', 'sc.CarId')
			->select('ar.Id as areaId', 's.Id as storeId', 's.No as storeNo', 's.Name as storeName', 's.PosId as postId')
			->addSelect('s.StorePhone as storePhone', 's.Address as address', 's.VATNumber as vatNumber', 'u.Name as salesName')
			->addSelect('f.Name as factoryName', 'c.Name as carNo')
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
				// 只有當 $role 為 true（或非空值）時，才會執行這裡
				return $query->whereIn('s.AreaId', $authAreaIds);
			})
			#->whereIn('s.AreaId', $authAreaIds)
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->toRawSql();
			#->orderBy('s.OperationCenterId')
			#->orderBy('ar.Id')
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取店休資訊, 不使用共用的trait共
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getDayoffList($brand, $stDate, $endDate, $userAreaIds)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		#To utc
		$stDate		= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate	= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->leftJoin('Order as o', function ($join) use($stDate, $endDate) {
				$join->on('o.StoreId', '=', 's.Id')
					->where('o.ExpectedDate', '>=', $stDate)
					->where('o.ExpectedDate', '<=', $endDate);
			})
			->leftJoin('Factory as f', 'f.Id', '=', 'sc.FactoryId')
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
			->whereNull('s.CloseDate') #只取有效門店
			->when($authAreaIds, function ($query, $authAreaIds) {
				return $query->whereIn('s.AreaId', $authAreaIds);
			})
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->toRawSql();
			->whereNull('o.Money')
			#->orderBy('s.OperationCenterId')
			#->orderBy('ar.Id')
			->get()
			->toArray(); 
		
		return $result;
	}
}
