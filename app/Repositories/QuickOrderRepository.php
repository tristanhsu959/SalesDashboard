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


class QuickOrderRepository extends Repository
{
	public function __construct()
	{
		
	}
	
	/* 取Product setting
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function getOrders($brandCode, $stDate, $endDate)
	{
		$db = $this->connectQuickOrder();
		
		$result = $db
			->table('Orders')
			->fromRaw('Orders as o WITH(NOLOCK)')
			->join('Stores as s', 's.storeId', '=', 'o.storeId')
			->select('o.storeId')
			->selectRaw('count(o.storeId) as customerCount, sum(o.price) as amount')
			->selectRaw('DATEADD(month, DATEDIFF(month, 0, o.orderTime), 0) as orderDate')
			->where('o.orderTime', '>=', $stDate)
			->where('o.orderTime', '<', $endDate)
			->where('o.isComplete', '=', 1)
			->where('o.isRefund', '=', 0)
			->where('s.brand', '=', $brandCode)
			->whereNotIn('o.storeId', config('web.quick_order.store.except'))
			->groupBy('o.storeId')
			->groupBy(DB::raw('DATEADD(month, DATEDIFF(month, 0, o.orderTime), 0)'))#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
	
	/* 取主資料 By records 
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getOrderDataByProductId($brand, $stDate, $endDate, $productIds, $userAreaIds)
	{
		#to UTC Time
		$stDate		= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate	= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		$brandId 	= $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Order as a')
			->fromRaw('[Order] as a WITH(NOLOCK)')
			->join(DB::raw('OrderSub as b WITH(NOLOCK)'), 'b.OrderId', '=', 'a.Id')
			->join(DB::raw('Product as p WITH(NOLOCK)'), 'p.Id', '=', 'b.ProductId')
			->join(DB::raw('Store as s WITH(NOLOCK)'), 's.Id', '=', 'a.StoreId')
			->join(DB::raw('Area as ar WITH(NOLOCK)'), 'ar.Id', '=', 's.AreaId')
			->join(DB::raw('StoreCar as sc WITH(NOLOCK)'), 'sc.StoreId', '=', 'a.StoreId')
			->join(DB::raw('Factory as f WITH(NOLOCK)'), 'f.Id', '=', 'sc.FactoryId')
			->selectRaw('CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE) as expectedDate')
			->addSelect('ar.id as areaId', 's.Id as storeId', 's.No as storeNo')
			->addSelect('f.No as factoryNo', 'f.Name as factoryName')
			->addSelect('b.Quantity as qty', 'b.Money as amount')
			->addSelect('p.Name as productName', 'p.ErpNo as erpNo', 'p.OldNo as shortCode', 'p.Memo as memo')
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('OperationCenter as oc')
					->whereColumn('oc.Id', 'a.OperationCenterId')
					->whereIn('oc.No', $this->getOpCenterNo($brandId));
			})
			->whereExists(function ($query) use($brandId) {
				$query->select(DB::raw(1))
					->from('Factory as ft')
					->whereColumn('ft.Id', 'sc.FactoryId')
					->whereIn('ft.No',  $this->getFactoryNo($brandId));
			})
			->where('a.ExpectedDate', '>=', $stDate)
			->where('a.ExpectedDate', '<', $endDate)
			#->where('a.State', '=', 'functionalized')
			->where('b.Money', '>', 0)
			->where('p.ErpNo', '!=', '')
			->whereIn('s.AreaId', $authAreaIds)
			->whereIn('b.ProductId', $productIds)#->ddRawSql();
			->get()
			->toArray();
		
		return $result;
	}
}
