<?php

namespace App\Repositories;

use App\Repositories\Traits\OrderTrait;
use App\Libraries\Purchase\AreaLib;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;


class MonthlyFillingRepository extends Repository
{
	use OrderTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取Product id
	 * @params: string
	 * @return: array
	 */
	public function getProductIdByCode($brandId, $codes)
	{
		$db = $this->connectNewOrder();
		$result = $db
			->table('Product as a')
			->join('Stocks as st', 'st.ProductId', '=', 'a.Id')
			->select('a.Id')
			->distinct()
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
			->whereIn('a.OldNo', $codes)
			->get()
			->pluck('Id')
			->toArray();
		
		return $result;
	}
	
	/* 取主資料 By store 
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getOrderDataByStore($brand, $stDate, $endDate, $productIds, $userAreaIds)
	{
		#to UTC Time
		$stDate	= (new Carbon($stDate))->utc()->format('Y-m-d H:i:s');
		$endDate= (new Carbon($endDate))->utc()->format('Y-m-d H:i:s');
		
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Order as a')
			->join('OrderSub as b', 'b.OrderId', '=', 'a.Id')
			->join('Product as p', 'p.Id', '=', 'b.ProductId')
			->join('Store as s', 's.Id', '=', 'a.StoreId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 'a.StoreId')
			->selectRaw('LEFT(CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE), 7) as expectedDate')
			->selectRaw('sum(b.Quantity) as qty')
			->addSelect('s.Id as storeId', 's.No as storeNo', 'p.OldNo as shortCode')
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
			->where('a.ExpectedDate', '<=', $endDate)
			->where('a.State', '=', 'functionalized')
			->where('b.Money', '>', 0)
			->whereIn('s.AreaId', $authAreaIds)
			->whereIn('b.ProductId', $productIds)
			->groupBy(DB::RAW('LEFT(CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE), 7)'), 's.Id', 's.No', 'p.OldNo')
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
	public function getOrderDataByFactory($brand, $stDate, $endDate, $productIds, $userAreaIds)
	{
		#to UTC Time
		$stDate	= (new Carbon($stDate))->utc();
		$endDate= (new Carbon($endDate))->utc();
		
		$brandId = $brand->value;
		$authAreaIds = AreaLib::toPurchaseAreaId($brand, $userAreaIds);
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Order as a')
			->join('OrderSub as b', 'b.OrderId', '=', 'a.Id')
			->join('Product as p', 'p.Id', '=', 'b.ProductId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 'a.StoreId')
			->join('Store as s', 's.Id', '=', 'sc.StoreId')
			->join('Factory as f', 'f.Id', '=', 'sc.FactoryId')
			->selectRaw('LEFT(CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE), 7) as expectedDate')
			->selectRaw('sum(b.Quantity) as qty')
			->addSelect('f.No as factoryNo', 'p.OldNo as shortCode')
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
			->where('a.ExpectedDate', '<=', $endDate)
			->where('a.State', '=', 'functionalized')
			->where('b.Money', '>', 0)
			->whereIn('s.AreaId', $authAreaIds)
			->whereIn('b.ProductId', $productIds)
			->groupBy(DB::RAW('LEFT(CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE), 7)'), 'f.No', 'p.OldNo')
			->get()
			->toArray();
		
		return $result;
	}
}
