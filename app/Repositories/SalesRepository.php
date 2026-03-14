<?php

namespace App\Repositories;

use App\Enums\Brand;
use App\Enums\Area;
use Illuminate\Support\Facades\DB;
use Exception;
use Log;

class SalesRepository extends Repository
{
	#MSSQL
	public function __construct()
	{
	}
	
	/* 取門店資料
	 * @params: enums
	 * @params: array
	 * @return: array
	 */
	public function getShopList($brand, $userAreaIds)
	{
		$configCode = $brand->code();
		$excepts = config("web.shop.except.{$configCode}");
		
		if ($brand == Brand::BAFANG)
		{
			$db = $this->connectBFPosErp();
			$authAreaIds = Area::toBafangId($userAreaIds);
		}
		else
		{
			$db = $this->connectBGPosErp();
			$authAreaIds = Area::toBuygoodId($userAreaIds);
		}
			
		$result = $db->table('hptrans_shop as a')
			->join('SHOP00 as b', 'b.SHOP_ID', '=', 'a.hptrs_shop')
			->select('b.SHOP_ID as shopId', 'b.SHOP_NAME as shopName', 'b.gid as areaId')
			->where('b.closedown', '=', 0)
			->whereIn('b.gid', $authAreaIds)
			->whereNotIn('b.SHOP_ID', $excepts)
			->orderBy('b.SHOP_ID')
			->get()->toArray();
	
		return $result;
	}
	
	/* 取新品設定相關條件
	 * @params: int
	 * @return: array
	 */
	public function getProductList($brand)
	{
		$db = $this->connectSalesDashboard('product');
		$result = $db
			->select('productBrand', 'productId', 'productName', 'erpNo', 'isPrimary')
			->join('product_no', 'parentId', '=', 'productId')
			->where('productStatus', '=', 1)
			->where('productBrand', '=', $brand->value)
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
		{
			$db = $this->connectBFPosErp();
			$authAreaIds = Area::toBafangId($userAreaIds);
		}
		else
		{
			$db = $this->connectBGPosErp();
			$authAreaIds = Area::toBuygoodId($userAreaIds);
		}
		
		$query = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join('SHOP00 as s', 's.SHOP_ID', '=', 'a.shopId')
				#->select('a.shopId', 'a.productId', 'a.price', 'a.qty', 'a.discount')
				#->addSelect('s.SHOP_NAME as shopName', 's.gid')
				->select('a.shopId', 'a.productId as erpNo')
				->selectRaw('sum(a.price * a.qty + a.discount) as amount')
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
		$authAreaIds = Area::toBafangId($userAreaIds);
		$dualBrandedShopIds = config('web.shop.dualBrandedId');
				
		$caseShopId = "CASE ";
		foreach ($dualBrandedShopIds as $bfId => $bgId) 
		{
			$caseShopId .= "WHEN a.SHOP_ID = '{$bfId}' THEN '{$bgId}' ";
		}
		$caseShopId .= "ELSE a.SHOP_ID END as shopId";
		
		$query = $db
				->table('zs_sd_order as a')
				->fromRaw('zs_sd_order as a WITH(NOLOCK)')
				->join('SHOP00 as s', 's.SHOP_ID', '=', 'a.shopId')
				#->select('a.shopId', 'a.productId', 'a.price', 'a.qty', 'a.discount')
				->select('a.shopId', 'a.productId as erpNo')
				->selectRaw('sum(a.price * a.qty + a.discount) as amount')
				->addSelect('s.SHOP_NAME as shopName', 's.gid')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<=', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
				->whereIn('a.shopId', array_keys($dualBrandedShopIds))
				->groupByRaw('a.shopId, a.productId, s.SHOP_NAME, s.gid')
				->get();
		
		return $query;
	}
}
