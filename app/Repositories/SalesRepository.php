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
	
	/* Sale data | 新品:八方/梁社漢共用
	 * @params: query builder
	 * @params: datetime
	 * @params: datetime
	 * @params: array
	 * @params: string
	 * @params: array
	 * @return: array
	 */
	public function getSaleData($brand, $stDate, $endDate, $userAreaIds)
	{
		
		
		#Log::channel('appServiceLog')->info($query->toRawSql(), [ __class__, __function__, __line__]);
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
				->join('PRODUCT00 as p', 'p.PROD_ID', '=', 'a.productId')
				->select('a.shopId', 'a.productId', 'a.price', 'a.qty', 'a.discount')
				->addSelect('s.SHOP_NAME as shopName', 's.gid', 'p.PROD_NAME1 as productName')
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<=', $endDate)
				->whereIn('s.gid', $authAreaIds)
				->whereIn('a.productId', $erpNos)
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
	private function _getDualBrandedData($brand, $stDate, $endDate, $erpNos, $tastes, $userAreaIds)
	{
		if ($brand == Brand::BAFANG)
			return FALSE;
		
		
		$db = $this->connectBGPosErp();
		$authAreaIds = Area::toBuygoodId($userAreaIds);
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
				->join('SHOP00 as c', 'c.SHOP_ID', '=', 'a.shopId')
				->select('a.shopId', 'a.qty')
				->selectRaw('CAST(a.saleDate AS DATE) as saleDate')
				
				->where('a.saleDate', '>=', $stDate)
				->where('a.saleDate', '<=', $endDate)
				->whereIn('c.gid', $authAreaIds)
				->whereIn('a.shopId', array_keys($dualBrandedShopIds))
				->where(function ($db) use ($erpNos, $tastes){
					#(product in (...) or taste_memo like ...)
					$db->whereIn('a.productId', $erpNos)
						->when(! empty($tastes), function ($db) use ($tastes) {
							$db->orWhereAny(['a.taste'], 'like', $tastes);
						});
				})->get();
		
		return $query;
	}
}
