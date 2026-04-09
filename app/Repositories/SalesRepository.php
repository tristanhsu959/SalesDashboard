<?php

namespace App\Repositories;

use App\Repositories\Traits\PosTrait;
use App\Enums\Brand;
use App\Enums\Area;
use App\Libraries\Sales\AreaLib;
use Illuminate\Support\Facades\DB;
use Exception;
use Log;

class SalesRepository extends Repository
{
	use PosTrait;
	
	public function __construct()
	{
	}
	
	/* 取產品設定相關條件
	 * @params: int
	 * @return: array
	 */
	public function getProductList($brandId)
	{
		$db = $this->connectSalesDashboard();
		$result = $db
			->table('sales_setting as a')
			->select('a.salesId', 'a.salesBrandId', 'a.salesName', 'c.erpNo', 'c.isPrimary')
			->join('sales_product as b', 'b.parentId', '=', 'a.salesId')
			->join('product_no as c', 'c.parentId', '=', 'b.productId')
			->where('a.salesBrandId', '=', $brandId)
			->where('a.salesStatus', '=', TRUE)
			->get()
			->toArray();
		
		return $result;
	}
	
	/* Sale data | 新品:八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	public function getSaleData($brand, $stDate, $endDate, $primaryIds, $secondaryIds, $userAreaIds)
	{
		$primaryData 		= $this->_getPrimaryData($brand, $stDate, $endDate, $primaryIds, $userAreaIds);
		$dualBrandedData	= $this->_getDualBrandedData($brand, $stDate, $endDate, $secondaryIds, $userAreaIds);
		
		#合併查詢(gid在八方及御廚定義不同, 這裏不處理)
		$result = $primaryData->merge($dualBrandedData)->toArray();
		
		return $result;
	}
	
	/* Build query string | 八方,御廚
	 * @params: enums
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getPrimaryData($brand, $stDate, $endDate, $erpNos, $userAreaIds)
	{
		if ($brand == Brand::BAFANG)
			$db = $this->connectBFPosErp();
		else if ($brand == Brand::BUYGOOD)
			$db = $this->connectBGPosErp();
		else
			return [];
		
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		
		$query = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join('SHOP00 as s', 's.SHOP_ID', '=', 'a.shopId')
				#->select('a.shopId', 'a.productId', 'a.price', 'a.qty', 'a.discount')
				#->addSelect('s.SHOP_NAME as shopName', 's.gid')
				->select('a.shopId', 'a.productId as erpNo')
				->selectRaw('sum(a.price * a.qty + a.discount) as price_sum')
				->selectRaw('sum(a.qty) as qty_sum')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<=', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
				->groupByRaw('a.shopId, a.productId')
				->get();
		
		return $query;
	}
	
	/* Build query string | 八方:只有複合店才有的情境
	 * @params: connection
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _getDualBrandedData($brand, $stDate, $endDate, $erpNos, $userAreaIds)
	{
		if ($brand == Brand::BAFANG)
			return [];
		
		
		$db = $this->connectBFPosErp();
		$authAreaIds = AreaLib::toSalesAreaId($brand, $userAreaIds);
		$dualBrandedShopIds = config('web.sales.shop.dualBrandedId');
				
		$query = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join('SHOP00 as s', 's.SHOP_ID', '=', 'a.shopId')
				#->select('a.shopId', 'a.productId', 'a.price', 'a.qty', 'a.discount')
				#->addSelect('s.SHOP_NAME as shopName', 's.gid')
				->select('a.shopId', 'a.productId as erpNo')
				->selectRaw('sum(a.price * a.qty + a.discount) as price_sum')
				->selectRaw('sum(a.qty) as qty_sum')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<=', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
				->whereIn('a.shopId', array_keys($dualBrandedShopIds))
				->groupByRaw('a.shopId, a.productId, s.SHOP_NAME, s.gid')
				->get();
		
		#轉換shop id
		$query = $query->map(function($item, $key) use($dualBrandedShopIds) {
			$item['shopId'] = $dualBrandedShopIds[$item['shopId']];
			return $item;
		});
		
		return $query;
	}
}
