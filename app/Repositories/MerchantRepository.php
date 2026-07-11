<?php

namespace App\Repositories;

#use App\Repositories\Traits\PurchaseReposTrait;
use App\Facades\PurchaseManager;
use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Purchase\AreaLib;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;


class MerchantRepository extends Repository
{
	#use PurchaseReposTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取門店資訊, 不使用共用的trait
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getStoreInfoList($brand, $allowBrandIds, $allowAreaIds)
	{
		$brandId = $brand->value;
		$allowAreaIds = AreaLib::toPurchaseAreaId($brand, $allowAreaIds);
		$excepts = config("web.purchase.store.except.{$brandId}");
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->fromRaw('Store as s WITH(NOLOCK)')
			->join(DB::raw('Brand as b WITH(NOLOCK)'), 'b.Id', '=', 's.BrandId')
			->join(DB::raw('Area as ar WITH(NOLOCK)'), 'ar.Id', '=', 's.AreaId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 's.Id')
			->leftJoin(DB::raw('[User] as u WITH(NOLOCK)'), 'u.Id', '=', 's.SuperviseUserId')
			->leftJoin(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sc.FactoryId')
			->leftJoin(DB::raw('Car as c WITH(NOLOCK)'), 'c.Id', '=', 'sc.CarId')
			->leftJoin(DB::raw('Warehouse as w WITH(NOLOCK)'), 'w.Id', '=', 'sc.WarehouseId')
			->select('b.Id as brandId', 'ar.Id as areaId', 's.Id as storeId', 's.No as storeNo', 's.Name as storeName', 's.PosId as posId')
			->addSelect('s.StorePhone as storePhone', 's.Address as address', 's.VATNumber as vatNumber', 'u.Name as salesName')
			->addSelect('f.Name as factoryName', 'w.Name as warehouse', 'c.Name as carNo')
			->whereExists(function ($query) use($allowBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->whereIn('bd.Id',  $allowBrandIds);
			})
			->whereNull('s.CloseDate')
			->when($allowAreaIds, function ($query, $allowAreaIds) {
				// 只有當 $role 為 true（或非空值）時，才會執行這裡
				return $query->whereIn('s.AreaId', $allowAreaIds);
			})
			->whereNotIn('s.No', $excepts)#->ddRawSql();
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取店休資訊, 不使用共用的trait共
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getDayoffList($brand, $allowBrandIds, $allowAreaIds, $stDate, $endDate, $productIds)
	{
		$brandId = $brand->value;
		$brandNo = $brand->shortCode();
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		#To utc
		$stDate		= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate	= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		
		dd();
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->fromRaw('Store as s WITH(NOLOCK)')
			->join(DB::raw('Area as ar WITH(NOLOCK)'), 'ar.Id', '=', 's.AreaId')
			->select('s.No as storeNo')
			->addSelect(['money' => $db->table('Order as o')
				->select('o.Money')
				->whereColumn('o.StoreId', 's.Id')
				->where('o.ExpectedDate', '>=', $stDate)
				->where('o.ExpectedDate', '<', $endDate)
				#->whereIn('o.State', $orderStatus)
				->limit(1)
			])
			->whereExists(function ($query) use($allowBrandIds) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->whereIn('bd.Id',  allowBrandIds);
			})
			#有判別brand不會抓到蘿蔔店
			/* ->whereExists(function ($query) use($brandNo) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->where('bd.No',  $brandNo);
			}) */
			/* ->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			}) */
			->where('s.OpenDate', '<=', $endDate) #不含未來開店
			->whereNull('s.CloseDate') #只取有效門店
			->when($authAreaIds, function ($query, $authAreaIds) {
				return $query->whereIn('s.AreaId', $authAreaIds);
			})
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->toRawSql();
			->get()
			->toArray();
		
		return $result;
	}

}
