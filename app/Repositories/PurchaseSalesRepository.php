<?php

namespace App\Repositories;

use App\Facades\PurchaseManager;
use App\Libraries\Purchase\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;


class PurchaseSalesRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* 取有效門店清單(取法不同於其它功能)
	 * @params: enum
	 * @params: array
	 * @return: array
	 */
	public function getActiveStoreListFromPurchase($brand, $areaIds, $storeName)
	{
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $areaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 's.Id')
			->select('ar.Id as areaId', 's.Id as storeId', 's.No as storeNo', 's.Name as storeName')
			->addSelect('s.PosId as posId', 's.VATNumber as vatNumber', 's.ErpNo as erpNo')
			->addSelect('s.BossName as bossName', 's.Address as address', 's.OpenDate as openDate')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 's.OperationCenterId')
					->whereIn('oc.No', PurchaseManager::getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Brand as bd')
					->whereColumn('bd.Id', 's.BrandId')
					->where('bd.No',  PurchaseManager::getBrandNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  PurchaseManager::getFactoryNo($brandId));
			})
			->when(!empty($storeName), function($query) use($storeName){
				$query->whereAny(['s.Name'], 'like', DB::raw("N'%$storeName%'"));
			})
			->whereNull('s.CloseDate')
			->whereIn('s.AreaId', $authAreaIds)
			->whereNotIn('s.No', config("web.purchase.store.except.{$brandId}"))#->ddRawSql();
			->get()
			->toArray(); 
		
		return $result;
	}
	
	/* 取有效門店清單By id
	 * @params: enum
	 * @params: int
	 * @return: array
	 */
	public function getPurchaseStoreInfoById($storeId)
	{
		#已過濾area權限, 不用再過濾
		$db = $this->connectNewOrder();
		$result = $db
			->table('Store as s')
			->select('s.Id as storeId', 's.No as storeNo', 's.Name as storeName')
			->addSelect('s.PosId as posId', 's.VATNumber as vatNumber', 's.ErpNo as erpNo')
			->where('s.Id', '=', $storeId)
			->get()
			->first(); 
		
		return $result;
	}
	
	
	/* 取訂貨訂單資料 By records 
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getPurchaseOrderByStore($brand, $stDate, $endDate, $storeId)
	{
		#to UTC Time
		$stDate		= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate	= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		$brandId 	= $brand->value;
		$orderStatus = config('web.purchase.order.status.active');
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Order as a')
			->fromRaw('[Order] as a WITH(NOLOCK)')
			->join(DB::raw('OrderSub as b WITH(NOLOCK)'), 'b.OrderId', '=', 'a.Id')
			->join(DB::raw('Product as p WITH(NOLOCK)'), 'p.Id', '=', 'b.ProductId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 'a.StoreId')
			->join(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sc.FactoryId')
			->selectRaw('CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE) as expectedDate')
			->addSelect('b.Quantity as qty', 'b.Money as amount')
			->addSelect('p.Name as productName', 'p.OldNo as shortCode', 'p.Memo as memo')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'a.OperationCenterId')
					->whereIn('oc.No', PurchaseManager::getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  PurchaseManager::getFactoryNo($brandId));
			})
			->where('a.ExpectedDate', '>=', $stDate)
			->where('a.ExpectedDate', '<', $endDate)
			->where('a.StoreId', '=', $storeId)
			->whereIn('a.State', $orderStatus)
			->where('b.Money', '>', 0)
			->where('p.ErpNo', '!=', '')
			->get()
			->toArray();
		
		return $result;
	}
}
