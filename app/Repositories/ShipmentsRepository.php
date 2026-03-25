<?php

namespace App\Repositories;

use App\Repositories\Traits\OrderTrait;
use App\Enums\OpCenter;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;


class ShipmentsRepository extends Repository
{
	use OrderTrait;
	
	public function __construct()
	{
		
	}
	
	/* 取主資料 By records 
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @return: array
	 */
	public function getOrderDataById($brandId, $stDate, $endDate, $productIds)
	{
		#to UTC Time
		$stDate	= (new Carbon($stDate))->utc();
		$endDate= (new Carbon($endDate))->utc();
		
		$db = $this->connectNewOrder();
		$result = $db
			->table('Order as a')
			->join('OrderSub as b', 'b.OrderId', '=', 'a.Id')
			->join('Product as p', 'p.Id', '=', 'b.ProductId')
			->join('Store as s', 's.Id', '=', 'a.StoreId')
			->join('Area as ar', 'ar.Id', '=', 's.AreaId')
			->join('StoreCar as sc', 'sc.StoreId', '=', 'a.StoreId')
			->join('Factory as f', 'f.Id', '=', 'sc.FactoryId')
			->selectRaw('CAST(DATEADD(HOUR, 8, a.ExpectedDate) AS DATE) as expectedDate')
			->addSelect('ar.Name as area', 's.No as storeNo', 's.Name as storeName')
			->addSelect('f.No as factoryNo', 'f.Name as factoryName')
			->addSelect('b.Quantity as qty', 'b.Money as amount')
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
			->where('b.Quantity', '>', 0)
			->whereIn('b.ProductId', $productIds)
			->get()
			->toArray();
		
		return $result;
	}
}
